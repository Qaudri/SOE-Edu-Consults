<?php
namespace Bookster\Services;

use Bookster\Features\Constants\AddonData;
use Bookster\Services\SettingsService;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Enums\AddonStatusEnum;

/**
 * Addons Service
 *
 * @method static AddonsService get_instance()
 */
class AddonsService extends BaseService {
    use SingletonTrait;

    /** @var SettingsService */
    private $settings_service;

    private $addon_infos = null;

    protected function __construct() {
        $this->settings_service = SettingsService::get_instance();
    }

    public function get_addon_infos() {
        if ( null !== $this->addon_infos ) {
            return $this->addon_infos;
        }

        $addon_infos = AddonData::SUPPORTED_ADDONS;

        $installed_plugins = [];
        if ( function_exists( 'get_plugins' ) ) {
            $installed_plugins = get_plugins();
        }
        $addon_infos = array_map(
            function( $addon_info ) use ( $installed_plugins ) {
                // Initialize the Addon Info
                $addon_info['link']           = null;
                $addon_info['installStatus']  = AddonStatusEnum::NOT_INSTALLED;
                $addon_info['currentVersion'] = null;

                $plugin_name = $addon_info['slug'] . '/' . $addon_info['slug'] . '.php';
                if ( array_key_exists( $plugin_name, $installed_plugins ) ) {
                    $addon_info['installStatus'] = AddonStatusEnum::INSTALLED;
                }

                return $addon_info;
            },
            $addon_infos
        );

        $addon_infos       = apply_filters( 'bookster_addon_infos', $addon_infos );
        $this->addon_infos = $addon_infos;
        return $this->addon_infos;
    }
}
