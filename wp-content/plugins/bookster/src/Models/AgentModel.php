<?php
namespace Bookster\Models;

use Bookster\Models\Database\DataModel;

/**
 * Agent Model
 *
 * @property int $agent_id
 * @property int $wp_user_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 */
class AgentModel extends DataModel {

    const TABLE = 'bookster_agents';

    protected $primary_key          = 'agent_id';
    protected $properties           = [
        'agent_id',
        'wp_user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'activated',
        'visibility',
        'priority',

        'weekly_schedule_enabled',
        'weekly_schedule',
        'dayoff_schedule_enabled',
        'dayoff_schedule',

        'avatar_id',
        'tagline',
        'bio',
        'tags',
        'social_links',
        'gallery_ids',

        'transient_avatar_url',
        'transient_gallery_urls',
        'wp_user_display_name',

        'available_agent_services',
        'created_at',
        'updated_at',
    ];
    protected $protected_properties = [
        'agent_id',
        'created_at',
        'updated_at',

        'transient_avatar_url',
        'transient_gallery_urls',
        'wp_user_display_name',
        '_available_agent_services',
        'available_agent_services',
    ];

    protected static $keywords = [ 'first_name', 'last_name', 'email', 'phone' ];

    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename       = static::get_tablename();

        return "CREATE TABLE $tablename (
            agent_id bigint(20) unsigned NOT NULL auto_increment,
            wp_user_id bigint(20) unsigned,
            first_name varchar(127) NOT NULL,
            last_name varchar(127) NOT NULL,
            email varchar(127) NOT NULL,
            phone varchar(127),
            activated tinyint(1) NOT NULL default true,
            visibility varchar(10) NOT NULL default 'public',
            weekly_schedule_enabled tinyint(1) NOT NULL default false,
            weekly_schedule longtext,
            dayoff_schedule_enabled tinyint(1) NOT NULL default false,
            dayoff_schedule longtext,
            avatar_id bigint(20) unsigned,
            tags text,
            bio text,
            priority smallint(6) NOT NULL default 10,
            tagline varchar(255) default '',
            gallery_ids text,
            social_links text,
            created_at timestamp NOT NULL default CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (agent_id),
            UNIQUE KEY email_idx (email),
            UNIQUE KEY wp_user_idx (wp_user_id),
            KEY activated_idx (activated, visibility, priority)
        ) $charset_collate;";
    }

    protected static $integer_attributes = [
        'agent_id',
        'wp_user_id',
        'avatar_id',
        'priority',
    ];

    protected static $boolean_attributes = [
        'activated',
        'weekly_schedule_enabled',
        'dayoff_schedule_enabled',
    ];

    protected static $jsonarr_attributes = [
        'weekly_schedule',
        'dayoff_schedule',
        'tags',
        'gallery_ids',
        'social_links',
        '_available_agent_services',
    ];

    protected function getTransient_avatar_urlAlias() {
        if ( isset( $this->attributes['transient_avatar_url'] ) ) {
            return $this->attributes['transient_avatar_url'];
        }
        if ( ! $this->avatar_id ) {
            return '';
        }
        $this->attributes['transient_avatar_url'] = wp_get_attachment_image_url( $this->avatar_id, 'thumbnail' );
        return $this->attributes['transient_avatar_url'];
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
