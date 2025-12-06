<?php
namespace Bookster\Models\Database;

use Bookster\Models\Database\ModelTraits\DataTrait;
use Bookster\Models\Database\ModelTraits\CastAttributesTrait;
use Bookster\Models\Database\ModelTraits\ToArrayTrait;

/**
 * Base WP Custom Data Model.
 * Inspired by Ten Quality's WP Data Model.
 *
 * @link https://github.com/10quality/wp-query-builder
 *
 * @property string $tablename Alias for: wpdb->prefix . static::TABLE
 */
abstract class DataModel {
    use DataTrait;
    use CastAttributesTrait;
    use ToArrayTrait;

    /**
     * Concrete DataModel is required to override this constant.
     * The Table Name without prefix. Hooks Name.
     */
    const TABLE = '';

    /** Reference to primary key column name. */
    protected $primary_key = 'ID';
    /** @var array string[] List of properties used for keyword search. */
    protected static $keywords = [];
    /** @var array string[] List of properties not saved to DB. */
    protected $protected_properties;

    /** The Table Name with prefix. */
    public static function get_tablename() {
        if ( empty( static::TABLE ) ) {
            throw new \Exception( 'Table name is required.' );
        }
        global $wpdb;
        return $wpdb->prefix . static::TABLE;
    }
    /** The Table Name with prefix. */
    public function getTablenameAlias() { // phpcs:ignore
        return $this->get_tablename();
    }

    /**
     * Default constructor.
     * With filter to allow extending the model.
     *
     * @param array $attributes
     * @param mixed $id
     */
    public function __construct( $attributes = [], $id = null ) {
        $this->attributes = $attributes;
        if ( ! empty( $id ) ) {
            $this->attributes[ $this->primary_key ] = $id;
        }

        $this->properties = apply_filters( 'bookster_data_model_' . static::TABLE . '_properties', $this->properties );
        if ( empty( $this->protected_properties ) ) {
            $this->protected_properties = [ $this->primary_key, 'created_at', 'updated_at' ];
        }
        $this->protected_properties = apply_filters( 'bookster_data_model_' . static::TABLE . '_excluded_save_fields', $this->protected_properties );
    }

    /**
     * Cast attributes.
     * With filter to allow extending the model.
     */
    public function init_model() {
        $this->cast_attributes();
        return apply_filters( 'bookster_data_model_' . static::TABLE . '_init', $this );
    }
    /**
     * Static constructor that initializes model from database query result.
     *
     * @param mixed[] $data
     * @return DataModel
     */
    public static function init_from_data( $data ) {
        $model = new static( $data );
        return $model->init_model();
    }

    /**
     * Loads attributes from database.
     *
     * @return DataModel|null
     */
    public function load() {
        $builder          = new QueryBuilder( static::TABLE . '_load' );
        $this->attributes = $builder->select( '*' )
            ->from( static::TABLE )
            ->where( [ $this->primary_key => $this->attributes[ $this->primary_key ] ] )
            ->first( ARRAY_A );
        return ! empty( $this->attributes )
            ? $this->init_model()
            : null;
    }
    /**
     * Loads attributes from database based on custom where statements
     *
     * @param array $args Query arguments.
     * @return DataModel|null
     */
    public function load_where( $args ) {
        if ( empty( $args ) ) {
            return null;
        }
        if ( ! is_array( $args ) ) {
            throw new \Exception( 'Arguments parameter must be an array.', 10100 );
        }
        $builder          = new QueryBuilder( static::TABLE . '_load_where' );
        $this->attributes = $builder->select( '*' )
            ->from( static::TABLE )
            ->where( $args )
            ->first( ARRAY_A );
        return ! empty( $this->attributes )
            ? $this->init_model()
            : null;
    }

    /**
     * Saves data attributes in database.
     *
     * @param bool $force_insert Flag that indicates if should insert regardless of ID.
     * @return bool
     */
    public function save( $force_insert = false ) {
        global $wpdb;
        $protected = $this->protected_properties;
        if ( ! $force_insert && $this->{$this->primary_key} ) {
            // Update
            $success = $wpdb->update( // phpcs:ignore
                $this->tablename,
                array_filter(
                    $this->attributes,
                    function( $key ) use( $protected ) {
                        return ! in_array( $key, $protected, true );
                    },
                    ARRAY_FILTER_USE_KEY
                ),
                [ $this->primary_key => $this->attributes[ $this->primary_key ] ]
            );
            if ( $success ) {
                do_action( 'bookster_data_model_' . static::TABLE . '_updated', $this );
            }
        } else {
            // Insert
            $success                    = $wpdb->insert( // phpcs:ignore
                $this->tablename,
                array_filter(
                    $this->attributes,
                    function( $key ) use( $protected ) {
                        return ! in_array( $key, $protected, true );
                    },
                    ARRAY_FILTER_USE_KEY
                )
            );
            $this->{$this->primary_key} = $wpdb->insert_id;
            $date                       = wp_date( 'Y-m-d H:i:s' );
            $this->created_at           = $date;
            $this->updated_at           = $date;
            if ( $success ) {
                do_action( 'bookster_data_model_' . static::TABLE . '_inserted', $this );
            }
        }//end if
        do_action( 'bookster_data_model_' . static::TABLE . '_saved', $this, $success );
        return $success;
    }
    /**
     * Updates specific columns of the model (not the whole object like save()).
     *
     * @param array $data Data to update.
     * @return bool
     */
    public function update( $data = [] ) {
        // If not data, let save() handle this
        if ( empty( $data ) || ! is_array( $data ) ) {
            return $this->save();
        }
        global $wpdb;
        $success   = false;
        $protected = $this->protected_properties;
        if ( $this->{$this->primary_key} ) {
            // Update
            $success = $wpdb->update( // phpcs:ignore
                $this->tablename,
                array_filter(
                    $data,
                    function( $key ) use( $protected ) {
                        return ! in_array( $key, $protected, true );
                    },
                    ARRAY_FILTER_USE_KEY
                ),
                [ $this->primary_key => $this->attributes[ $this->primary_key ] ]
            );
            if ( false !== $success ) {
                foreach ( $data as $key => $value ) {
                    $this->$key = $value;
                }
                do_action( 'bookster_data_model_' . static::TABLE . '_updated', $this );
            }
            do_action( 'bookster_data_model_' . static::TABLE . '_saved', $this, $success );
        }//end if
        return $success;
    }

    /**
     * Deletes record.
     *
     * @return bool
     */
    public function delete() {
        global $wpdb;
        $deleted = $this->{$this->primary_key}
            ? $wpdb->delete( $this->tablename, [ $this->primary_key => $this->attributes[ $this->primary_key ] ] ) // phpcs:ignore
            : false;
        if ( $deleted ) {
            do_action( 'bookster_data_model_' . static::TABLE . '_deleted', $this );
        }
        return $deleted;
    }

    /**
     * Static constructor that finds record in database and fills model.
     *
     * @param mixed $id
     * @return DataModel|null
     */
    public static function find( $id ) {
        $model = new static( [], $id );
        return $model->load();
    }
    /**
     * Static constructor that finds record in database and fills model.
     *
     * @param array $args Where query statement arguments. See non-static method.
     * @return DataModel|null
     */
    public static function find_where( $args ) {
        $model = new static();
        return $model->load_where( $args );
    }
    /**
     * Returns count.
     *
     * @param array $args Query arguments.
     * @return int
     */
    public static function count( $args = [] ) {
        // Pull specific data from args
        unset( $args['limit'] );
        unset( $args['offset'] );
        $keywords = isset( $args['keywords'] ) ? sanitize_text_field( $args['keywords'] ) : null;
        unset( $args['keywords'] );
        // Build query and retrieve
        $builder = new QueryBuilder( static::TABLE . '_count' );
        return $builder->from( static::TABLE . ' as `' . static::TABLE . '`' )
            ->keywords( $keywords, static::$keywords )
            ->where( $args )
            ->count();
    }
    /**
     * Static constructor that inserts record in database and fills model.
     *
     * @param array $attributes
     * @return DataModel|null
     */
    public static function insert( $attributes ) {
        $model = new static( $attributes );
        return $model->save( true ) ? $model : null;
    }
    /**
     * Run mass update.
     *
     * @param array $set   Set of column => data to update.
     * @param array $where Where condition.
     * @return bool True if success, false otherwise.
     */
    public static function update_where( $set, $where = [] ) {
        $builder = new QueryBuilder( static::TABLE . '_update_where' );
        return $builder->from( static::TABLE )
            ->set( $set )
            ->where( $where )
            ->update();
    }
    /**
     * Delete where query.
     *
     * @param array $args Query arguments.
     * @return bool
     */
    public static function delete_where( $args ) {
        global $wpdb;
        return $wpdb->delete( static::get_tablename(), $args ); // phpcs:ignore
    }

    /**
     * Returns a collection with all models found in the database.
     *
     * @param bool $init Flag that indicates if should initialize models.
     * @return DataModel[]
     */
    public static function all( $init = true ) {
        // Build query and retrieve
        $builder = new QueryBuilder( static::TABLE . '_all' );
        return array_map(
            function( $attributes ) use ( $init ) {
                $model = new static( $attributes );
                return $init ? $model->init_model() : $model;
            },
            $builder->select( '*' )
                ->from( static::TABLE . ' as `' . static::TABLE . '`' )
                ->get( ARRAY_A )
        );
    }
    /**
     * Returns a collection of attributes.
     *
     * @param array $args Query arguments.
     * @param bool  $init Flag that indicates if should initialize models.
     * @return DataModel[]
     */
    public static function where( $args = [], $init = true ) {
        // Pull specific data from args
        $limit = isset( $args['limit'] ) ? $args['limit'] : null;
        unset( $args['limit'] );
        $offset = isset( $args['offset'] ) ? $args['offset'] : 0;
        unset( $args['offset'] );
        $keywords = isset( $args['keywords'] ) ? $args['keywords'] : null;
        unset( $args['keywords'] );
        $keywords_separator = isset( $args['keywords_separator'] ) ? $args['keywords_separator'] : ' ';
        unset( $args['keywords_separator'] );
        $order_by = isset( $args['order_by'] ) ? $args['order_by'] : null;
        unset( $args['order_by'] );
        $order = isset( $args['order'] ) ? $args['order'] : 'ASC';
        unset( $args['order'] );
        // Build query and retrieve
        $builder = new QueryBuilder( static::TABLE . '_where' );
        return array_map(
            function( $attributes ) use ( $init ) {
                $model = new static( $attributes );
                return $init ? $model->init_model() : $model;
            },
            $builder->select( '*' )
                ->from( static::TABLE . ' as `' . static::TABLE . '`' )
                ->keywords( $keywords, static::$keywords, $keywords_separator )
                ->where( $args )
                ->order_by( $order_by, $order )
                ->limit( $limit )
                ->offset( $offset )
                ->get( ARRAY_A )
        );
    }
    /**
     * Construct a QueryBuilder object with where clause.
     * Support In clause.
     *
     * @param array  $args
     * @param string $alias SQL table alias.
     * @return QueryBuilder
     */
    public static function create_where_builder( $args = [], $alias = false ): QueryBuilder {
        // Pull specific data from args
        $limit = isset( $args['limit'] ) ? $args['limit'] : null;
        unset( $args['limit'] );
        $offset = isset( $args['offset'] ) ? $args['offset'] : 0;
        unset( $args['offset'] );
        $keywords = isset( $args['keywords'] ) ? $args['keywords'] : null;
        unset( $args['keywords'] );
        $keywords_separator = isset( $args['keywords_separator'] ) ? $args['keywords_separator'] : ' ';
        unset( $args['keywords_separator'] );
        $order_by = isset( $args['order_by'] ) ? $args['order_by'] : null;
        unset( $args['order_by'] );
        $order = isset( $args['order'] ) ? $args['order'] : 'ASC';
        unset( $args['order'] );
        $in_args = isset( $args['in_args'] ) ? $args['in_args'] : [];
        unset( $args['in_args'] );

        if ( false === $alias ) {
            $alias = static::TABLE;
        }

        // Build query and retrieve
        $builder = new QueryBuilder( static::TABLE . '_where' );
        $builder->from( static::TABLE . ' as `' . $alias . '`' )
            ->keywords( $keywords, static::$keywords, $keywords_separator )
            ->where( $args )
            ->order_by( $order_by, $order )
            ->limit( $limit )
            ->offset( $offset );

        foreach ( $in_args as $column => $in_arg ) {
            $in_query = static::build_in_query( $in_arg['values'], $in_arg['placeholder'], $column, $in_arg['alias'] ?? $alias );

            $builder->where( [ 'raw' => $in_query ] );
        }

        return $builder;
    }

    public static function build_in_query( $values, $placeholder, $column, $table_alias = false ) {
        global $wpdb;
        if ( false === $table_alias ) {
            $table_column = $column;
        } else {
            $table_column = "$table_alias.$column";
        }

        $in_query = call_user_func_array(
            [ $wpdb, 'prepare' ],
            array_merge(
                [ "$table_column IN (" . implode( ', ', array_fill( 0, count( $values ), $placeholder ) ) . ')' ],
                $values
            )
        );

        return $in_query;
    }
}
