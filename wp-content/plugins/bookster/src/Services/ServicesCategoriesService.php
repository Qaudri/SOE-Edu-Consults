<?php
namespace Bookster\Services;

use Bookster\Features\Enums\ObjectTypeEnum;
use Bookster\Models\ServiceCategoryModel;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Errors\NotFoundException;

/**
 * Service Service
 *
 * @method static ServicesCategoriesService get_instance()
 */
class ServicesCategoriesService extends BaseService {
    use SingletonTrait;

    /** @var ServicesService */
    private $services_service;

    protected function __construct() {
        $this->services_service = ServicesService::get_instance();
    }

    public function find_where( array $args ): array {
        $categories = ServiceCategoryModel::where( $this->prepare_where_args( $args ) );
        $this->validate_wpdb_query();
        return $categories;
    }

    public function count_where( array $args ): int {
        $count = ServiceCategoryModel::count( $this->prepare_count_args( $args ) );
        $this->validate_wpdb_query();
        return $count;
    }

    public function find_by_id( int $service_category_id ): ServiceCategoryModel {
        $category = ServiceCategoryModel::find( $service_category_id );
        if ( ! $category ) {
            throw new NotFoundException( 'Service Category Not Found', ObjectTypeEnum::SERVICE_CATEGORY, $service_category_id );
        }
        return $category;
    }

    public function insert( array $attributes ): ServiceCategoryModel {
        global $wpdb;
        $tablename = ServiceCategoryModel::get_tablename();
        $this->exec_wpdb_query(
            $wpdb->prepare(
                "INSERT INTO $tablename (name,description,position ) VALUES (%s,%s,(SELECT IFNULL(MAX( position ),0)+1 FROM $tablename description))",
                $attributes['name'],
                $attributes['description']
            )
        );

        $cate_id = $wpdb->insert_id;
        return $this->find_by_id( $cate_id );
    }

    public function find_and_update_position( int $service_category_id, int $position ) {
        $category = $this->find_by_id( $service_category_id );

        $this->update_position(
            $service_category_id,
            $category->position,
            $position
        );
        return $this->find_by_id( $service_category_id );
    }

    private function update_position(
        int $service_category_id,
        int $old_position,
        int $new_position
    ) {

        global $wpdb;
        $category_table = ServiceCategoryModel::get_tablename();
        if ( $old_position < $new_position ) {
            // Adjust new position to account for Closing the gap

            ++$new_position;
        }

        // Make space for the new position
        $this->exec_wpdb_query(
            $wpdb->prepare(
                "UPDATE $category_table
                SET position = position + 1
                WHERE position >= %d
                ORDER BY position DESC",
                $new_position
            )
        );

        // Update the category position
        $this->exec_wpdb_query(
            $wpdb->prepare(
                "UPDATE $category_table,
                (SELECT IFNULL(max(position),0) + 1 as max_position FROM $category_table) as max_position
                SET position = LEAST(%d, max_position.max_position)
                WHERE service_category_id = %d",
                $new_position,
                $service_category_id
            )
        );

        // Close the gap
        $this->exec_wpdb_query(
            $wpdb->prepare(
                "UPDATE $category_table
                SET position = position - 1
                WHERE position > %d
                ORDER BY position ASC",
                $old_position
            )
        );
    }

    public function update( int $service_category_id, array $data ): ServiceCategoryModel {
        $category = $this->find_by_id( $service_category_id );
        unset( $data['position'] );

        $success = $category->update( ServiceCategoryModel::prepare_saved_data( $data ) );
        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Category: ' . $wpdb->last_error ) );
        }
        return $this->find_by_id( $service_category_id );
    }

    public function delete( int $service_category_id ): bool {
        $category = $this->find_by_id( $service_category_id );

        $count = $this->services_service->count_where(
            [ 'service_category_id' => $service_category_id ]
        );
        if ( $count > 0 ) {
            throw new \Exception( 'Cannot Delete Category with Services Existed' );
        }

        global $wpdb;
        $success = $category->delete();
        if ( false === $success ) {
            throw new \Exception( esc_html( 'Error Deleting Category: ' . $wpdb->last_error ) );
        }

        $category_table = ServiceCategoryModel::get_tablename();
        // Close the gap
        $this->exec_wpdb_query(
            $wpdb->prepare(
                "UPDATE $category_table
                SET position = position - 1
                WHERE position > %d
                ORDER BY position ASC",
                $category->position
            )
        );

        return $success;
    }
}
