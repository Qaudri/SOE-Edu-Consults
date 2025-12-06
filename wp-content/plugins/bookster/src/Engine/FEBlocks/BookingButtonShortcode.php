<?php
namespace Bookster\Engine\FEBlocks;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Scripts\EnqueueLogic;

/**
 * Booking Button Shortcode
 */
class BookingButtonShortcode {
    use SingletonTrait;

    /** @var EnqueueLogic */
    private $enqueue_logic;

    /** Hooks Initialization */
    protected function __construct() {
        $this->enqueue_logic = EnqueueLogic::get_instance();

        add_shortcode( 'bookster_booking_button', [ $this, 'render_booking_button_shortcode' ] );
    }

    /**
     * Render shortcode.
     *
     * @param array $shortcode_attributes
     */
    public function render_booking_button_shortcode( $shortcode_attributes ) {
        $shortcode_attributes = shortcode_atts(
            BookingFormShortcode::get_instance()->get_default_attributes(),
            BookingFormShortcode::get_instance()->cast_block_attributes( $shortcode_attributes ),
            'bookster_booking_button'
        );

        $content = '<div class="bookster-root shortcode-bookster-booking-button"
        data-id="' . esc_attr( uniqid( '', true ) ) . '"
        data-attributes="' . esc_attr( wp_json_encode( $shortcode_attributes ) ) . '">
        </div>';

        $this->enqueue_scripts();
        return $content;
    }

    private function enqueue_scripts() {
        $this->enqueue_logic->enqueue_block_booking_button();
    }
}
