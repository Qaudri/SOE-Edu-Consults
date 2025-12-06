<?php
namespace Bookster\Controllers;

use Bookster\Features\Utils\SingletonTrait;

/**
 * AddonsControllser
 *
 * @method static AddonsController get_instance()
 */
class AddonsController extends BaseRestController {
    use SingletonTrait;

    protected function __construct() {
        $this->init_hooks();
    }

    protected function init_hooks() {
        register_rest_route(
            self::REST_NAMESPACE,
            '/addon/activate',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_activate_addon' ],
                    'permission_callback' => function () {
                        return current_user_can( 'activate_plugins' );
                    },
                ],
            ]
        );
    }

    public function activate_addon( $request ) {
        $arg = $request->get_json_params();

        $addon_slug  = $arg['slug'];
        $plugin_name = $addon_slug . '/' . $addon_slug . '.php';

        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . '/wp-admin/includes/plugin.php';
        }

        if ( is_plugin_active( $plugin_name ) ) {
            return;
        }

        return activate_plugin( $plugin_name );
    }

    public function exec_activate_addon( $request ) {
        return $this->exec_write( [ $this, 'activate_addon' ], $request );
    }
}
