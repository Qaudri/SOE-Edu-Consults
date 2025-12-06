<?php
namespace Bookster\Engine\FEBlocks;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Scripts\EnqueueLogic;

/**
 * Booking Button Gutenberg Block
 */
class BookingButtonBlock {
    use SingletonTrait;

    /** @var EnqueueLogic */
    private $enqueue_logic;

    protected function __construct() {
        $this->enqueue_logic = EnqueueLogic::get_instance();

        add_action( 'init', [ $this, 'register_custom_button_block' ] );
        add_action( 'wp_footer', [ $this, 'enqueue_booking_button_block_scripts' ] );
        add_filter( 'bookster_module_handles', [ $this, 'add_editor_script_as_module' ] );
    }

    public function register_custom_button_block() {
        register_block_type(
            BOOKSTER_PLUGIN_PATH . 'assets/dist/blocks/booking-button/block.json',
            [
                'render_callback' => [ $this, 'render_callback' ],
            ]
        );
    }

    public function render_callback( $attributes, $content, $block ) {
        return apply_filters( 'bookster_booking_button_block_content', $content, $attributes, $block );
    }

    public function enqueue_booking_button_block_scripts() {
        // Always enqueue to support manual trigger
        $this->enqueue_logic->enqueue_block_booking_button();
    }

    public function add_editor_script_as_module( $module_handles ) {
        $module_handles[] = 'bookster-booking-button-editor-script';
        return $module_handles;
    }
}
