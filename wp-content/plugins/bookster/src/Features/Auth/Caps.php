<?php
namespace Bookster\Features\Auth;

use Bookster\Services\SettingsService;

/**
 * Bookster Capabilities
 */
class Caps {
    public const MANAGE_SHOP_SETTINGS_CAP = 'bookster_manage_shop_settings';
    public const MANAGE_SHOP_RECORDS_CAP  = 'bookster_manage_shop_records';

    public const MANAGE_AGENT_SETTINGS_CAP = 'bookster_manage_agent_settings';
    public const MANAGE_AGENT_RECORDS_CAP  = 'bookster_manage_agent_records';
    public const MANAGE_AGENT_PROFILE_CAP  = 'bookster_manage_agent_profile';

    public static function get_agent_caps() {
        $caps = [
            self::MANAGE_AGENT_RECORDS_CAP => true,
            self::MANAGE_AGENT_PROFILE_CAP => true,
            'read'                         => true,
        ];

        $settings                                = SettingsService::get_instance()->get_manager_data()['permissionsSettings'];
        $caps[ self::MANAGE_AGENT_SETTINGS_CAP ] = true === $settings['agents_allow_edit_settings'] ? true : false;

        return apply_filters( 'bookster_agent_capabilities', $caps );
    }

    public static function get_manager_additional_caps() {
        return [
            self::MANAGE_SHOP_SETTINGS_CAP => true,
            self::MANAGE_SHOP_RECORDS_CAP  => true,
        ];
    }

    public static function get_manager_caps() {
        $caps = array_merge(
            get_role( 'editor' )->capabilities,
            [
                'manage_categories'  => true,
                'manage_links'       => true,
                'moderate_comments'  => true,
                'upload_files'       => true,
                'export'             => true,
                'import'             => true,
                'list_users'         => true,
                'edit_theme_options' => true,
            ],
            self::get_manager_additional_caps()
        );

        return apply_filters( 'bookster_manager_capabilities', $caps );
    }

    public static function get_current_user_caps() {
        if ( ! is_user_logged_in() ) {
            return [];
        }

        $user     = wp_get_current_user();
        $all_caps = array_keys( $user->allcaps );
        return array_values( $all_caps );
    }
}
