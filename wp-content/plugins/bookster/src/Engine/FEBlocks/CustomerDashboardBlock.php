<?php
namespace Bookster\Engine\FEBlocks;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Scripts\EnqueueLogic;

/**
 * Customer Dashboard Gutenberg Block
 */
class CustomerDashboardBlock {
    use SingletonTrait;

    /** @var EnqueueLogic */
    private $enqueue_logic;
    /** @var bool */
    private $rendered = false;

    protected function __construct() {
        $this->enqueue_logic = EnqueueLogic::get_instance();

        add_action( 'init', [ $this, 'register_custom_button_block' ] );
        add_action( 'wp_footer', [ $this, 'enqueue_customer_dashboard_block_scripts' ] );
        add_filter( 'bookster_module_handles', [ $this, 'add_editor_script_as_module' ] );
    }

    public function register_custom_button_block() {
        register_block_type(
            BOOKSTER_PLUGIN_PATH . 'assets/dist/blocks/customer-dashboard/block.json',
            [
                'render_callback' => [ $this, 'render_callback' ],
            ]
        );
    }

    public function render_callback( $attributes, $content, $block ) {
        if ( ! $this->rendered ) {
            $this->rendered = true;
        }

        return $content;
    }

    public function enqueue_customer_dashboard_block_scripts() {
        if ( $this->rendered ) {
            $this->enqueue_logic->enqueue_block_customer_dashboard();
        }
    }

    public function add_editor_script_as_module( $module_handles ) {
        $module_handles[] = 'bookster-customer-dashboard-editor-script';
        return $module_handles;
    }
}
