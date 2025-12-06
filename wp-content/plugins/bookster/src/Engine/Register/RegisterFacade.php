<?php
namespace Bookster\Engine\Register;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Scripts\EnqueueLogic;
use Bookster\Features\Scripts\ScriptName;

/**
 * Register Facade.
 *
 * @method static RegisterFacade get_instance()
 */
class RegisterFacade {
    use SingletonTrait;

    /** Hooks Initialization */
    protected function __construct() {
        $is_prod = EnqueueLogic::get_instance()->is_prod();

        add_filter( 'script_loader_tag', [ $this, 'add_entry_as_module' ], 10, 3 );

        add_action( 'init', [ $this, 'register_all_assets' ] );

        add_filter( 'pre_load_script_translations', [ $this, 'use_mo_file_for_script_translations' ], 10, 4 );

        if ( $is_prod && class_exists( '\Bookster\Engine\Register\RegisterProd' ) ) {
            \Bookster\Engine\Register\RegisterProd::get_instance();
        } elseif ( ! $is_prod && class_exists( '\Bookster\Engine\Register\RegisterDev' ) ) {
            \Bookster\Engine\Register\RegisterDev::get_instance();
        }
    }

    public function add_entry_as_module( $tag, $handle ) {
        $module_handles = apply_filters( 'bookster_module_handles', [] );

        if ( strpos( $handle, ScriptName::MODULE_PREFIX ) !== false || in_array( $handle, $module_handles, true ) ) {
            if ( strpos( $tag, 'type="' ) !== false ) {
                return preg_replace( '/\stype="\S+\s/', ' type="module" ', $tag, 1 );
            } else {
                return str_replace( ' src=', ' type="module" src=', $tag );
            }
        }
        return $tag;
    }

    public function register_all_assets() {
        wp_register_style( ScriptName::STYLE_BOOKSTER, BOOKSTER_PLUGIN_URL . 'assets/dist/bookster/style.css', [], BOOKSTER_VERSION );
        wp_register_style( ScriptName::STYLE_BOOKSTER_FONT, 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700', [], BOOKSTER_VERSION );
        wp_register_style( ScriptName::STYLE_ADMIN_HIDDEN, BOOKSTER_PLUGIN_URL . 'assets/css/admin-hidden.css', [], BOOKSTER_VERSION );
        wp_register_style( ScriptName::STYLE_RESET_THEME, BOOKSTER_PLUGIN_URL . 'assets/css/reset-theme.css', [], BOOKSTER_VERSION );
        wp_register_style( ScriptName::STYLE_ANIMXYZ, BOOKSTER_PLUGIN_URL . 'assets/css/animxyz.min.css', [], BOOKSTER_VERSION );

        wp_register_script( ScriptName::LIB_CORE, BOOKSTER_PLUGIN_URL . 'assets/dist/libs/core.js', [ 'wp-hooks' ], BOOKSTER_VERSION, true );
        wp_register_script( ScriptName::LIB_ICONS, BOOKSTER_PLUGIN_URL . 'assets/dist/libs/icons.js', [ 'react', 'react-dom', 'wp-hooks' ], BOOKSTER_VERSION, true );
        wp_register_script( ScriptName::LIB_COMPONENTS, BOOKSTER_PLUGIN_URL . 'assets/dist/libs/components.js', [ 'react', 'react-dom', 'wp-hooks' ], BOOKSTER_VERSION, true );
        wp_register_script( ScriptName::LIB_BOOKING, BOOKSTER_PLUGIN_URL . 'assets/dist/libs/booking.js', [ 'react', 'react-dom', 'wp-hooks' ], BOOKSTER_VERSION, true );
    }

    /**
     * Bookster Scripts is split into multiple files.
     * Thus it's not possible to use JSON file for translations.
     *
     * @param string $json_translations
     * @param string $file
     * @param string $handle
     * @param string $domain
     */
    public function use_mo_file_for_script_translations( $json_translations, $file, $handle, $domain ) {
        $all_handles = [
            ScriptName::PAGE_MANAGER,
            ScriptName::PAGE_AGENT,
            ScriptName::PAGE_INTRO,
            ScriptName::BLOCK_BOOKING_BUTTON,
            ScriptName::BLOCK_BOOKING_FORM,
            ScriptName::BLOCK_CUSTOMER_DASHBOARD,
        ];

        if ( 'bookster' !== $domain || ! in_array( $handle, $all_handles, true ) || ! is_textdomain_loaded( 'bookster' ) ) {
            return $json_translations;
        }

        $mimic_json_translations = get_transient( 'bookster_mimic_json_translations' . BOOKSTER_VERSION );

        if ( false !== $mimic_json_translations ) {
            return $mimic_json_translations;
        }

        $translations = get_translations_for_domain( 'bookster' );
        $messages     = [
            '' => [
                'domain' => 'messages',
            ],
        ];
        $entries      = $translations->entries;
        foreach ( $entries as $key => $entry ) {
            $messages[ $entry->singular ] = $entry->translations;
        }

        $mimic_json_translations = wp_json_encode(
            [
                'domain'      => 'messages',
                'locale_data' => [
                    'messages' => $messages,
                ],
            ]
        );
        set_transient( 'bookster_mimic_json_translations' . BOOKSTER_VERSION, $mimic_json_translations, 30 * DAY_IN_SECONDS );
        return $mimic_json_translations;
    }
}
