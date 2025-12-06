<?php
namespace Bookster\Models;

use Bookster\Models\Database\DataModel;

/**
 * Service Model
 *
 * @property int $service_id
 * @property int $service_category_id
 * @property string $name
 * @property bool $activated
 * @property string $visibility
 */
class ServiceModel extends DataModel {

    const TABLE = 'bookster_services';

    protected $primary_key          = 'service_id';
    protected $properties           = [
        'service_id',
        'service_category_id',
        'name',
        'activated',
        'visibility',
        'position',
        'theme_color',

        'price',
        'duration',
        'buffer_before',
        'buffer_after',

        'cover_id',
        'tagline',
        'description',
        'display_price',
        'tags',
        'gallery_ids',

        'transient_cover_url',
        'transient_gallery_urls',

        'available_agent_services',
        'created_at',
        'updated_at',
    ];
    protected $protected_properties = [
        'service_id',
        'created_at',
        'updated_at',

        'transient_cover_url',
        'transient_gallery_urls',
        '_available_agent_services',
        'available_agent_services',
    ];

    protected static $keywords = [ 'name' ];

    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename       = static::get_tablename();

        return "CREATE TABLE $tablename (
            service_id bigint(20) unsigned NOT NULL auto_increment,
            service_category_id bigint(20) unsigned NOT NULL,
            name varchar(255) NOT NULL default '',
            activated tinyint(1) NOT NULL default true,
            visibility varchar(10) NOT NULL default 'public',
            price decimal(13,2) NOT NULL default 100,
            duration smallint(6) NOT NULL default 0,
            buffer_before smallint(6) NOT NULL default 0,
            buffer_after smallint(6) NOT NULL default 0,
            gallery_ids text,
            cover_id bigint(20) unsigned,
            tags text,
            description text,
            position smallint(6) NOT NULL default 1,
            theme_color varchar(10) NOT NULL default '#66cc8a',
            tagline varchar(255) default '',
            display_price text,
            created_at timestamp NOT NULL default CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (service_id),
            UNIQUE KEY position_idx (service_category_id, position),
            KEY activated_idx (activated, visibility, service_category_id, position)
        ) $charset_collate;";
    }

    protected static $integer_attributes = [
        'service_id',
        'service_category_id',
        'duration',
        'buffer_before',
        'buffer_after',

        'cover_id',
        'position',
    ];

    protected static $boolean_attributes = [
        'activated',
    ];

    protected static $jsonarr_attributes = [
        'tags',
        'gallery_ids',
        'display_price',
        '_available_agent_services',
    ];

    protected function getTransient_cover_urlAlias() {
        if ( isset( $this->attributes['transient_cover_url'] ) ) {
            return $this->attributes['transient_cover_url'];
        }
        if ( ! $this->cover_id ) {
            return '';
        }
        $this->attributes['transient_cover_url'] = wp_get_attachment_image_url( $this->cover_id, 'medium_large' );
        return $this->attributes['transient_cover_url'];
    }

    protected function getTransient_gallery_urlsAlias() {
        if ( isset( $this->attributes['transient_gallery_urls'] ) ) {
            return $this->attributes['transient_gallery_urls'];
        }
        if ( ! $this->gallery_ids ) {
            return [];
        }

        $transient_gallery_urls = [];
        foreach ( $this->gallery_ids as $gallery_id ) {
            $transient_gallery_urls[] = wp_get_attachment_image_url( $gallery_id, 'medium_large' );
        }

        $this->attributes['transient_gallery_urls'] = $transient_gallery_urls;
        return $this->attributes['transient_gallery_urls'];
    }

    protected function getAvailable_agent_servicesAlias() {
        if ( isset( $this->attributes['available_agent_services'] ) ) {
            return $this->attributes['available_agent_services'];
        }
        if ( ! $this->_available_agent_services ) {
            return [];
        }

        $available_agent_service_infos = $this->attributes['_available_agent_services'];
        $available_agent_service_infos = array_filter(
            $available_agent_service_infos,
            function( $available_agent_service_info ) {
                return null !== $available_agent_service_info['available_agent_service_id'];
            }
        );

        $available_agent_service_infos = array_map(
            function( $available_agent_service_info ) {
                return AvailableAgentServiceModel::init_from_data( $available_agent_service_info );
            },
            $available_agent_service_infos
        );

        $this->attributes['available_agent_services'] = $available_agent_service_infos;
        return $this->attributes['available_agent_services'];
    }
}
