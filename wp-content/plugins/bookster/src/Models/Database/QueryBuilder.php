<?php
namespace Bookster\Models\Database;

use Exception;

/**
 * Database query builder.
 * Builds complex queries using a chainable interface.
 * Inspired by Ten Quality's WP Query Builder.
 *
 * @link https://github.com/10quality/wp-query-builder
 */
class QueryBuilder {

    /** @var string Builder ID for hook references. */
    protected $id;
    /** @var array Builder statements. */
    protected $builder;
    /** @var array Builder options. */
    protected $options;

    /**
     * @param string|null $id
     */
    public function __construct( $id = null ) {
        $this->id      = ! empty( $id ) ? $id : uniqid();
        $this->builder = [
            'select' => [],
            'from'   => null,
            'join'   => [],
            'where'  => [],
            'order'  => [],
            'group'  => [],
            'having' => null,
            'limit'  => null,
            'offset' => 0,
            'set'    => [],
        ];
        $this->options = [
            'wildcard'         => '{%}',
            'default_wildcard' => '{%}',
        ];
    }

    /**
     * Static constructor.
     *
     * @param string $id
     */
    public static function create( $id = null ) {
        $builder = new self( $id );
        return $builder;
    }

    /**
     * Adds select statement.
     *
     * @param array|string $statement
     * @return QueryBuilder $this for chaining.
     */
    public function select( $statement ) {
        $this->builder['select'][] = $statement;
        return $this;
    }
    /**
     * Adds from statement.
     *
     * @param string $from
     * @param bool   $add_prefix Should DB prefix be added.
     * @return QueryBuilder $this for chaining.
     */
    public function from( $from, $add_prefix = true ) {
        global $wpdb;
        $this->builder['from'] = ( $add_prefix ? $wpdb->prefix : '' ) . $from;
        return $this;
    }
    /**
     * Adds keywords search statement.
     *
     * @param string $keywords  Searched keywords.
     * @param array  $columns   Column or fields where to search.
     * @param string $separator Keyword separator within keywords string.
     * @return QueryBuilder $this for chaining.
     */
    public function keywords( $keywords, $columns, $separator = ' ' ) {
        if ( ! empty( $keywords ) ) {
            global $wpdb;
            foreach ( explode( $separator, $keywords ) as $keyword ) {
                $keyword                  = '%' . $this->sanitize_value( true, $keyword ) . '%';
                $this->builder['where'][] = [
                    'joint'     => 'AND',
                    'condition' => '(' . implode(
                        ' OR ',
                        array_map(
                            function( $column ) use( &$wpdb, &$keyword ) {
                                    return $wpdb->prepare( $column . ' LIKE %s', $keyword );
                            },
                            $columns
                        )
                    ) . ')',
                ];
            }
        }
        return $this;
    }
    /**
     * Adds where statement.
     *
     * @param array $args Multiple where arguments.
     * @return QueryBuilder $this for chaining.
     */
    public function where( $args ) {
        global $wpdb;
        foreach ( $args as $key => $value ) {
            // Options - set
            if ( is_array( $value ) && array_key_exists( 'wildcard', $value ) && ! empty( $value['wildcard'] ) ) {
                $this->options['wildcard'] = trim( $value['wildcard'] );
            }
            // Value
            $arg_value = is_array( $value ) && array_key_exists( 'value', $value ) ? $value['value'] : $value;
            if ( is_array( $value ) && array_key_exists( 'min', $value ) ) {
                $arg_value = $value['min'];
            }
            $sanitize_callback = is_array( $value ) && array_key_exists( 'sanitize_callback', $value )
                ? $value['sanitize_callback']
                : true;
            if ( $sanitize_callback
                && 'raw' !== $key
                && ( ! is_array( $value ) || ! array_key_exists( 'key', $value ) )
            ) {
                $arg_value = $this->sanitize_value( $sanitize_callback, $arg_value );
            }
            $statement = 'raw' === $key
                ? [ $arg_value ]
                : [
                    $key,
                    is_array( $value ) && isset( $value['operator'] ) ? strtoupper( $value['operator'] ) : ( null === $arg_value ? 'is' : '=' ),
                    is_array( $value ) && array_key_exists( 'key', $value )
                        ? $value['key']
                        : ( is_array( $arg_value )
                            ? ( '(\'' . implode( '\',\'', $arg_value ) . '\')' )
                            : ( null === $arg_value
                                ? 'null'
                                : $wpdb->prepare( ( ! is_array( $value ) || ! array_key_exists( 'force_string', $value ) || ! $value['force_string'] ) && is_numeric( $arg_value ) ? '%d' : '%s', $arg_value ) // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
                            )
                        ),
                ];
            // Between?
            if ( is_array( $value ) && isset( $value['operator'] ) ) {
                $value['operator'] = strtoupper( $value['operator'] );
                if ( strpos( $value['operator'], 'BETWEEN' ) !== false ) {
                    if ( array_key_exists( 'max', $value ) || array_key_exists( 'key_b', $value ) ) {
                        if ( array_key_exists( 'max', $value ) ) {
                            $arg_value = $value['max'];
                        }
                        if ( array_key_exists( 'sanitize_callback2', $value ) ) {
                            $sanitize_callback = $value['sanitize_callback2'];
                        }
                        if ( $sanitize_callback && ! array_key_exists( 'key_b', $value ) ) {
                            $arg_value = $this->sanitize_value( $sanitize_callback, $arg_value );
                        }
                        $statement[] = 'AND';
                        $statement[] = array_key_exists( 'key_b', $value )
                            ? $value['key_b']
                            : ( is_array( $arg_value )
                                ? ( '(\'' . implode( '\',\'', $arg_value ) . '\')' )
                                : $wpdb->prepare( ( ! array_key_exists( 'force_string', $value ) || ! $value['force_string'] ) && is_numeric( $arg_value ) ? '%d' : '%s', $arg_value ) // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
                            );
                    } else {
                        throw new Exception( '"max" or "key_b "parameter must be indicated when using the BETWEEN operator.', 10202 );
                    }
                }//end if
            }//end if
            $this->builder['where'][] = [
                'joint'     => is_array( $value ) && isset( $value['joint'] ) ? $value['joint'] : 'AND',
                'condition' => implode( ' ', $statement ),
            ];
            // Options - reset
            if ( is_array( $value ) && array_key_exists( 'wildcard', $value ) && ! empty( $value['wildcard'] ) ) {
                $this->options['wildcard'] = $this->options['default_wildcard'];
            }
        }//end foreach
        return $this;
    }
    /**
     * Adds join statement.
     *
     * @throws Exception Invalid join type.
     * @param string      $table      Join table.
     * @param array       $args       Join arguments.
     * @param bool|string $type       Flag that indicates if it is "LEFT or INNER", also accepts direct join string.
     * @param bool        $add_prefix Should DB prefix be added.
     * @return QueryBuilder $this for chaining.
     */
    public function join( $table, $args, $type = false, $add_prefix = true ) {
        $type = is_string( $type ) ? strtoupper( trim( $type ) ) : ( $type ? 'LEFT' : '' );
        if ( ! in_array( $type, [ '', 'LEFT', 'RIGHT', 'INNER', 'CROSS', 'LEFT OUTER', 'RIGHT OUTER' ], true ) ) {
            throw new Exception( 'Invalid join type.', 10201 );
        }
        global $wpdb;
        $join = [
            'table' => ( $add_prefix ? $wpdb->prefix : '' ) . $table,
            'type'  => $type,
            'on'    => [],
        ];
        foreach ( $args as $argument ) {
            // Options - set
            if ( array_key_exists( 'wildcard', $argument ) && ! empty( $argument['wildcard'] ) ) {
                $this->options['wildcard'] = trim( $argument['wildcard'] );
            }
            // Value
            $arg_value = isset( $argument['value'] ) ? $argument['value'] : null;
            if ( array_key_exists( 'min', $argument ) ) {
                $arg_value = $argument['min'];
            }
            $sanitize_callback = array_key_exists( 'sanitize_callback', $argument ) ? $argument['sanitize_callback'] : true;
            if ( $sanitize_callback
                && ! array_key_exists( 'raw', $argument )
                && ! array_key_exists( 'key_b', $argument )
            ) {
                $arg_value = $this->sanitize_value( $sanitize_callback, $arg_value );
            }
            $statement = array_key_exists( 'raw', $argument )
                ? [ $argument['raw'] ]
                : [
                    isset( $argument['key_a'] ) ? $argument['key_a'] : $argument['key'],
                    isset( $argument['operator'] ) ? strtoupper( $argument['operator'] ) : ( null === $arg_value && ! isset( $argument['key_b'] ) ? 'is' : '=' ),
                    array_key_exists( 'key_b', $argument )
                        ? $argument['key_b']
                        : ( is_array( $arg_value )
                            ? ( '(\'' . implode( '\',\'', $arg_value ) . '\')' )
                            : ( null === $arg_value
                                ? 'null'
                                : $wpdb->prepare( ( ! array_key_exists( 'force_string', $argument ) || ! $argument['force_string'] ) && is_numeric( $arg_value ) ? '%d' : '%s', $arg_value ) // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
                            )
                        ),
                ];
            // Between?
            if ( isset( $argument['operator'] ) ) {
                $argument['operator'] = strtoupper( $argument['operator'] );
                if ( strpos( $argument['operator'], 'BETWEEN' ) !== false ) {
                    if ( array_key_exists( 'max', $argument ) || array_key_exists( 'key_c', $argument ) ) {
                        if ( array_key_exists( 'max', $argument ) ) {
                            $arg_value = $argument['max'];
                        }
                        if ( array_key_exists( 'sanitize_callback2', $argument ) ) {
                            $sanitize_callback = $argument['sanitize_callback2'];
                        }
                        if ( $sanitize_callback && ! array_key_exists( 'key_c', $argument ) ) {
                            $arg_value = $this->sanitize_value( $sanitize_callback, $arg_value );
                        }
                        $statement[] = 'AND';
                        $statement[] = array_key_exists( 'key_c', $argument )
                            ? $argument['key_c']
                            : ( is_array( $arg_value )
                                ? ( '(\'' . implode( '\',\'', $arg_value ) . '\')' )
                                : $wpdb->prepare( ( ! array_key_exists( 'force_string', $argument ) || ! $argument['force_string'] ) && is_numeric( $arg_value ) ? '%d' : '%s', $arg_value ) // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
                            );
                    } else {
                        throw new Exception( '"max" or "key_c" parameter must be indicated when using the BETWEEN operator.', 10203 );
                    }
                }//end if
            }//end if
            $join['on'][] = [
                'joint'     => isset( $argument['joint'] ) ? $argument['joint'] : 'AND',
                'condition' => implode( ' ', $statement ),
            ];
            // Options - reset
            if ( array_key_exists( 'wildcard', $argument ) && ! empty( $argument['wildcard'] ) ) {
                $this->options['wildcard'] = $this->options['default_wildcard'];
            }
        }//end foreach
        $this->builder['join'][] = $join;
        return $this;
    }
    /**
     * Adds limit statement.
     *
     * @param int $limit
     * @return QueryBuilder $this for chaining.
     */
    public function limit( $limit ) {
        $this->builder['limit'] = $limit;
        return $this;
    }
    /**
     * Adds offset statement.
     *
     * @param int $offset
     * @return QueryBuilder $this for chaining.
     */
    public function offset( $offset ) {
        $this->builder['offset'] = $offset;
        return $this;
    }
    /**
     * Adds order by statement.
     *
     * @param string $key
     * @param string $direction
     * @return QueryBuilder $this for chaining.
     */
    public function order_by( $key, $direction = 'ASC' ) {
        $direction = trim( strtoupper( $direction ) );
        if ( 'ASC' !== $direction && 'DESC' !== $direction ) {
            throw new Exception( 'Invalid direction value.', 10200 );
        }
        if ( ! empty( $key ) ) {
            $this->builder['order'][] = $key . ' ' . $direction;
        }
        return $this;
    }
    /**
     * Adds group by statement.
     *
     * @param string $statement
     * @return QueryBuilder $this for chaining.
     */
    public function group_by( $statement ) {
        if ( ! empty( $statement ) ) {
            $this->builder['group'][] = $statement;
        }
        return $this;
    }
    /**
     * Adds having statement.
     *
     * @param string $statement
     * @return QueryBuilder $this for chaining.
     */
    public function having( $statement ) {
        if ( ! empty( $statement ) ) {
            $this->builder['having'] = $statement;
        }
        return $this;
    }
    /**
     * Adds set statement (for update).
     *
     * @param array $args Multiple where arguments.
     * @return QueryBuilder $this for chaining.
     */
    public function set( $args ) {
        global $wpdb;
        foreach ( $args as $key => $value ) {
            // Value
            $arg_value         = is_array( $value ) && array_key_exists( 'value', $value ) ? $value['value'] : $value;
            $sanitize_callback = is_array( $value ) && array_key_exists( 'sanitize_callback', $value )
                ? $value['sanitize_callback']
                : true;
            if ( $sanitize_callback
                && 'raw' !== $key
                && ( ! is_array( $value ) || ! array_key_exists( 'raw', $value ) )
            ) {
                $arg_value = $this->sanitize_value( $sanitize_callback, $arg_value );
            }
            $statement              = 'raw' === $key
                ? [ $arg_value ]
                : [
                    $key,
                    '=',
                    is_array( $value ) && array_key_exists( 'raw', $value )
                        ? $value['raw']
                        : ( is_array( $arg_value )
                            ? ( '\'' . implode( ',', $arg_value ) . '\'' )
                            : ( null === $arg_value
                                ? 'null'
                                : $wpdb->prepare( ( ! is_array( $value ) || ! array_key_exists( 'force_string', $value ) || ! $value['force_string'] ) && is_numeric( $arg_value ) ? '%d' : '%s', $arg_value ) // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
                            )
                        ),
                ];
            $this->builder['set'][] = implode( ' ', $statement );
        }//end foreach
        return $this;
    }

    /**
     * Build a subquery. The subquery is not executed.
     * The result is a string that can be add to another query.
     *
     * @return string
     */
    public function build_subquery() {
        $subquery = '';
        $this->build_select( $subquery );
        $this->build_from( $subquery );
        $this->build_join( $subquery );
        $this->build_where( $subquery );
        $this->build_group( $subquery );
        $this->build_having( $subquery );
        $this->build_order( $subquery );
        $this->build_limit( $subquery );
        $this->build_offset( $subquery );

        return $subquery;
    }

    /**
     * Retunrs results from builder statements.
     *
     * @param int      $output           WPDB output type.
     * @param callable $callable_mapping Function callable to filter or map results to.
     * @param bool     $calc_rows        Flag that indicates to SQL if rows should be calculated or not.
     * @return array
     */
    public function get( $output = ARRAY_A, $callable_mapping = null, $calc_rows = false ) {
        global $wpdb;
        $this->builder = apply_filters( 'bookster_query_builder_get_builder', $this->builder );
        $this->builder = apply_filters( 'bookster_query_builder_get_builder_' . $this->id, $this->builder );
        // Build Query
        $query = '';
        $this->build_select( $query, $calc_rows );
        $this->build_from( $query );
        $this->build_join( $query );
        $this->build_where( $query );
        $this->build_group( $query );
        $this->build_having( $query );
        $this->build_order( $query );
        $this->build_limit( $query );
        $this->build_offset( $query );
        // Process
        $query   = apply_filters( 'bookster_query_builder_get_query', $query );
        $query   = apply_filters( 'bookster_query_builder_get_query_' . $this->id, $query );
        $results = $wpdb->get_results( $query, $output ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        if ( $callable_mapping ) {
            $results = array_map(
                function( $row ) use( &$callable_mapping ) {
                    return call_user_func_array( $callable_mapping, [ $row ] );
                },
                $results
            );
        }
        return $results;
    }
    /**
     * Returns first row found.
     *
     * @param int $output WPDB output type.
     * @return object|array
     */
    public function first( $output = ARRAY_A ) {
        global $wpdb;
        $this->builder = apply_filters( 'bookster_query_builder_first_builder', $this->builder );
        $this->builder = apply_filters( 'bookster_query_builder_first_builder_' . $this->id, $this->builder );
        // Build Query
        $query = '';
        $this->build_select( $query );
        $this->build_from( $query );
        $this->build_join( $query );
        $this->build_where( $query );
        $this->build_group( $query );
        $this->build_having( $query );
        $this->build_order( $query );
        $query .= ' LIMIT 1';
        $this->build_offset( $query );
        // Process
        $query = apply_filters( 'bookster_query_builder_first_query', $query );
        $query = apply_filters( 'bookster_query_builder_first_query_' . $this->id, $query );
        return $wpdb->get_row( $query, $output ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }
    /**
     * Returns a value.
     *
     * @param int $x Column of value to return. Indexed from 0.
     * @param int $y Row of value to return. Indexed from 0.
     * @return mixed
     */
    public function value( $x = 0, $y = 0 ) {
        global $wpdb;
        $this->builder = apply_filters( 'bookster_query_builder_value_builder', $this->builder );
        $this->builder = apply_filters( 'bookster_query_builder_value_builder_' . $this->id, $this->builder );
        // Build Query
        $query = '';
        $this->build_select( $query );
        $this->build_from( $query );
        $this->build_join( $query );
        $this->build_where( $query );
        $this->build_group( $query );
        $this->build_having( $query );
        $this->build_order( $query );
        $this->build_limit( $query );
        $this->build_offset( $query );
        // Process
        $query = apply_filters( 'bookster_query_builder_value_query', $query );
        $query = apply_filters( 'bookster_query_builder_value_query_' . $this->id, $query );
        return $wpdb->get_var( $query, $x, $y ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }
    /**
     * Returns the count.
     *
     * @param string|int $column       Count column.
     * @param bool       $bypass_limit Flag that indicates if limit + offset should be considered on count.
     * @return int
     */
    public function count( $column = 1, $bypass_limit = true ) {
        global $wpdb;
        $this->builder = apply_filters( 'bookster_query_builder_count_builder', $this->builder );
        $this->builder = apply_filters( 'bookster_query_builder_count_builder_' . $this->id, $this->builder );
        // Build Query
        $query = 'SELECT count(' . $column . ') as `count`';
        $this->build_from( $query );
        $this->build_join( $query );
        $this->build_where( $query );
        $this->build_group( $query );
        $this->build_having( $query );
        if ( ! $bypass_limit ) {
            $this->build_limit( $query );
            $this->build_offset( $query );
        }
        // Process
        $query = apply_filters( 'bookster_query_builder_count_query', $query );
        $query = apply_filters( 'bookster_query_builder_count_query_' . $this->id, $query );
        return intval( $wpdb->get_var( $query ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }
    /**
     * Returns column results from builder statements.
     *
     * @param int  $x Column index number.
     * @param bool $calc_rows        Flag that indicates to SQL if rows should be calculated or not.
     * @return array
     */
    public function col( $x = 0, $calc_rows = false ) {
        global $wpdb;
        $this->builder = apply_filters( 'bookster_query_builder_col_builder', $this->builder );
        $this->builder = apply_filters( 'bookster_query_builder_col_builder_' . $this->id, $this->builder );
        // Build Query
        $query = '';
        $this->build_select( $query, $calc_rows );
        $this->build_from( $query );
        $this->build_join( $query );
        $this->build_where( $query );
        $this->build_group( $query );
        $this->build_having( $query );
        $this->build_order( $query );
        $this->build_limit( $query );
        $this->build_offset( $query );
        // Process
        $query = apply_filters( 'bookster_query_builder_col_query', $query );
        $query = apply_filters( 'bookster_query_builder_col_query_' . $this->id, $query );
        return $wpdb->get_col( $query, $x ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    /**
     * Returns flag indicating if delete query has been executed.
     *
     * @return bool
     */
    public function delete() {
        global $wpdb;
        $this->builder = apply_filters( 'bookster_query_builder_delete_builder', $this->builder );
        $this->builder = apply_filters( 'bookster_query_builder_delete_builder_' . $this->id, $this->builder );
        // Build Query
        $query = '';
        $this->build_delete( $query );
        $this->build_from( $query );
        $this->build_join( $query );
        $this->build_where( $query );
        // Process
        $query = apply_filters( 'bookster_query_builder_delete_query', $query );
        $query = apply_filters( 'bookster_query_builder_delete_query_' . $this->id, $query );
        return $wpdb->query( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }
    /**
     * Returns flag indicating if update query has been executed.
     *
     * @return bool
     */
    public function update() {
        global $wpdb;
        $this->builder = apply_filters( 'bookster_query_builder_update_builder', $this->builder );
        $this->builder = apply_filters( 'bookster_query_builder_update_builder_' . $this->id, $this->builder );
        // Build Query
        $query = '';
        $this->build_update( $query );
        $this->build_join( $query );
        $this->build_set( $query );
        $this->build_where( $query );
        // Process
        $query = apply_filters( 'bookster_query_builder_update_query', $query );
        $query = apply_filters( 'bookster_query_builder_update_query_' . $this->id, $query );
        return $wpdb->query( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }
    /**
     * Retunrs found rows in last query, if SQL_CALC_FOUND_ROWS is used and is supported.
     *
     * @return array
     */
    public function rows_found() {
        global $wpdb;
        $query = 'SELECT FOUND_ROWS()';
        // Process
        $query = apply_filters( 'bookster_query_builder_found_rows_query', $query );
        $query = apply_filters( 'bookster_query_builder_found_rows_query_' . $this->id, $query );
        return $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    /**
     * Builds query's select statement.
     *
     * @param string &$query
     * @param bool   $calc_rows
     */
    private function build_select( &$query, $calc_rows = false ) {
        $query = 'SELECT ' . ( $calc_rows ? 'SQL_CALC_FOUND_ROWS ' : '' ) . (
            is_array( $this->builder['select'] ) && count( $this->builder['select'] )
                ? implode( ',', $this->builder['select'] )
                : '*'
        );
    }
    /**
     * Builds query's from statement.
     *
     * @param string &$query
     */
    private function build_from( &$query ) {
        $query .= ' FROM ' . $this->builder['from'];
    }
    /**
     * Builds query's join statement.
     *
     * @param string &$query
     */
    private function build_join( &$query ) {
        foreach ( $this->builder['join'] as $join ) {
            $query .= ( ! empty( $join['type'] ) ? ' ' . $join['type'] . ' JOIN ' : ' JOIN ' ) . $join['table'];
            $length = count( $join['on'] );
            for ( $i = 0; $i < $length; ++$i ) {
                $query .= ( 0 === $i ? ' ON ' : ' ' . $join['on'][ $i ]['joint'] . ' ' )
                    . $join['on'][ $i ]['condition'];
            }
        }
    }
    /**
     * Builds query's where statement.
     *
     * @param string &$query
     */
    private function build_where( &$query ) {
        $length = count( $this->builder['where'] );
        for ( $i = 0; $i < $length; ++$i ) {
            $query .= ( 0 === $i ? ' WHERE ' : ' ' . $this->builder['where'][ $i ]['joint'] . ' ' )
                . $this->builder['where'][ $i ]['condition'];
        }
    }
    /**
     * Builds query's group by statement.
     *
     * @param string &$query
     */
    private function build_group( &$query ) {
        if ( count( $this->builder['group'] ) ) {
            $query .= ' GROUP BY ' . implode( ',', $this->builder['group'] );
        }
    }
    /**
     * Builds query's having statement.
     *
     * @param string &$query
     */
    private function build_having( &$query ) {
        if ( $this->builder['having'] ) {
            $query .= ' HAVING ' . $this->builder['having'];
        }
    }
    /**
     * Builds query's order by statement.
     *
     * @param string &$query
     */
    private function build_order( &$query ) {
        if ( count( $this->builder['order'] ) ) {
            $query .= ' ORDER BY ' . implode( ',', $this->builder['order'] );
        }
    }
    /**
     * Builds query's limit statement.
     *
     * @param string &$query
     */
    private function build_limit( &$query ) {
        global $wpdb;
        if ( $this->builder['limit'] ) {
            $query .= $wpdb->prepare( ' LIMIT %d', $this->builder['limit'] );
        }
    }
    /**
     * Builds query's offset statement.
     *
     * @param string &$query
     */
    private function build_offset( &$query ) {
        global $wpdb;
        if ( $this->builder['offset'] ) {
            $query .= $wpdb->prepare( ' OFFSET %d', $this->builder['offset'] );
        }
    }
    /**
     * Builds query's delete statement.
     *
     * @param string &$query
     */
    private function build_delete( &$query ) {
        $query .= trim(
            'DELETE ' . ( count( $this->builder['join'] )
            ? preg_replace( '/\s[aA][sS][\s\S]+.*?/', '', $this->builder['from'] )
            : ''
            )
        );
    }
    /**
     * Builds query's update statement.
     *
     * @param string &$query
     */
    private function build_update( &$query ) {
        $query .= trim(
            'UPDATE ' . ( count( $this->builder['join'] )
            ? $this->builder['from'] . ',' . implode(
                ',',
                array_map(
                    function( $join ) {
                        return $join['table'];
                    },
                    $this->builder['join']
                )
            )
            : $this->builder['from']
            )
        );
    }
    /**
     * Builds query's set statement.
     *
     * @param string &$query
     */
    private function build_set( &$query ) {
        $query .= $this->builder['set'] ? ' SET ' . implode( ',', $this->builder['set'] ) : '';
    }
    /**
     * Sanitize value.
     *
     * @param string|bool $callback Sanitize callback.
     * @param mixed       $value
     *
     * @return mixed
     */
    private function sanitize_value( $callback, $value ) {
        if ( true === $callback ) {
            $callback = ( is_numeric( $value ) && strpos( $value, '.' ) !== false )
                ? 'floatval'
                : ( is_numeric( $value )
                    ? 'intval'
                    : ( is_string( $value )
                        ? 'sanitize_text_field'
                        : null
                    )
                );
        }
        if ( strpos( $callback, 'bookster_builder_esc' ) !== false ) {
            // private methods
            $callback = [ &$this, $callback ];
        }
        if ( is_array( $value ) ) {
            for ( $i = count( $value ) - 1; $i >= 0; --$i ) {
                $value[ $i ] = $this->sanitize_value( true, $value[ $i ] );
            }
        }
        return $callback && is_callable( $callback ) ? call_user_func_array( $callback, [ $value ] ) : $value;
    }
    /**
     * Returns value escaped with WPDB `esc_like`,
     *
     * @param mixed $value
     * @return string
     */
    private function bookster_builder_esc_like( $value ) {
        global $wpdb;
        $wildcard = $this->options['wildcard'];
        return implode(
            '%',
            array_map(
                function( $part ) use( &$wpdb ) {
                    return $wpdb->esc_like( $part );
                },
                explode( $wildcard, $value )
            )
        );
    }
    /**
     * Returns escaped value for LIKE comparison and appends wild card at the beggining.
     *
     * @param mixed $value
     * @return string
     */
    private function bookster_builder_esc_like_wild_value( $value ) {
        return '%' . $this->bookster_builder_esc_like( $value );
    }
    /**
     * Returns escaped value for LIKE comparison and appends wild card at the end.
     *
     * @param mixed $value
     * @return string
     */
    private function bookster_builder_esc_like_value_wild( $value ) {
        return $this->bookster_builder_esc_like( $value ) . '%';
    }
    /**
     * Returns escaped value for LIKE comparison and appends wild cards at both ends.
     *
     * @param mixed $value
     * @return string
     */
    private function bookster_builder_esc_like_wild_wild( $value ) {
        return '%' . $this->bookster_builder_esc_like( $value ) . '%';
    }
}
