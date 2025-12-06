<?php
namespace Bookster\Engine\Register;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Scripts\ScriptName;

/** Register in Production Mode */
class RegisterProd {
    use SingletonTrait;

    /** Hooks Initialization */
    protected function __construct() {
        add_action( 'init', [ $this, 'register_all_scripts' ] );
    }

    public function register_all_scripts() {
        $core_deps = [ 'react', 'react-dom', 'wp-hooks', 'wp-i18n', 'wp-date', 'lodash', ScriptName::LIB_CORE, ScriptName::LIB_ICONS, ScriptName::LIB_COMPONENTS, ScriptName::LIB_BOOKING ];

        $deps = apply_filters( 'bookster_scripts_dependencies', $core_deps, ScriptName::PAGE_MANAGER );
        wp_register_script( ScriptName::PAGE_MANAGER, BOOKSTER_PLUGIN_URL . 'assets/dist/bookster/page-manager.js', $deps, BOOKSTER_VERSION, true );
        wp_set_script_translations( ScriptName::PAGE_MANAGER, 'bookster', BOOKSTER_PLUGIN_PATH . 'languages' );

        $deps = apply_filters( 'bookster_scripts_dependencies', $core_deps, ScriptName::PAGE_AGENT );
        wp_register_script( ScriptName::PAGE_AGENT, BOOKSTER_PLUGIN_URL . 'assets/dist/bookster/page-agent.js', $deps, BOOKSTER_VERSION, true );
        wp_set_script_translations( ScriptName::PAGE_AGENT, 'bookster', BOOKSTER_PLUGIN_PATH . 'languages' );

        $deps = apply_filters( 'bookster_scripts_dependencies', $core_deps, ScriptName::PAGE_INTRO );
        wp_register_script( ScriptName::PAGE_INTRO, BOOKSTER_PLUGIN_URL . 'assets/dist/bookster/page-intro.js', $deps, BOOKSTER_VERSION, true );
        wp_set_script_translations( ScriptName::PAGE_INTRO, 'bookster', BOOKSTER_PLUGIN_PATH . 'languages' );

        $deps = apply_filters( 'bookster_scripts_dependencies', $core_deps, ScriptName::BLOCK_BOOKING_BUTTON );
        wp_register_script( ScriptName::BLOCK_BOOKING_BUTTON, BOOKSTER_PLUGIN_URL . 'assets/dist/bookster/block-booking-button.js', $deps, BOOKSTER_VERSION, true );
        wp_set_script_translations( ScriptName::BLOCK_BOOKING_BUTTON, 'bookster', BOOKSTER_PLUGIN_PATH . 'languages' );

        $deps = apply_filters( 'bookster_scripts_dependencies', $core_deps, ScriptName::BLOCK_BOOKING_FORM );
        wp_register_script( ScriptName::BLOCK_BOOKING_FORM, BOOKSTER_PLUGIN_URL . 'assets/dist/bookster/block-booking-form.js', $deps, BOOKSTER_VERSION, true );
        wp_set_script_translations( ScriptName::BLOCK_BOOKING_FORM, 'bookster', BOOKSTER_PLUGIN_PATH . 'languages' );

        $deps = apply_filters( 'bookster_scripts_dependencies', $core_deps, ScriptName::BLOCK_CUSTOMER_DASHBOARD );
        wp_register_script( ScriptName::BLOCK_CUSTOMER_DASHBOARD, BOOKSTER_PLUGIN_URL . 'assets/dist/bookster/block-customer-dashboard.js', $deps, BOOKSTER_VERSION, true );
        wp_set_script_translations( ScriptName::BLOCK_CUSTOMER_DASHBOARD, 'bookster', BOOKSTER_PLUGIN_PATH . 'languages' );
    }
}
