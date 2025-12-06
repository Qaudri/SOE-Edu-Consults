<?php
namespace Bookster\Engine\FEBlocks;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Scripts\EnqueueLogic;

/**
 * Booking Form Shortcode
 */
class BookingFormShortcode {
    use SingletonTrait;

    /** @var EnqueueLogic */
    private $enqueue_logic;

    /** Hooks Initialization */
    protected function __construct() {
        $this->enqueue_logic = EnqueueLogic::get_instance();

        add_shortcode( 'bookster_booking_form', [ $this, 'render_booking_form_shortcode' ] );
    }

    /**
     * Render shortcode.
     *
     * @param array $shortcode_attributes
     */
    public function render_booking_form_shortcode( $shortcode_attributes ) {
        $shortcode_attributes = shortcode_atts(
            $this->get_default_attributes(),
            $this->cast_block_attributes( $shortcode_attributes ),
            'bookster_booking_form'
        );

        $content = '<div class="bookster-root shortcode-bookster-booking-form"
        style="min-height: 42rem; height: 42rem; margin-left: auto; margin-right: auto;"
        data-id="' . esc_attr( uniqid( '', true ) ) . '"
        data-attributes="' . esc_attr( wp_json_encode( $shortcode_attributes ) ) . '">
        </div>';

        $this->enqueue_scripts();
        return $content;
    }

    public function get_default_attributes() {
        return [
            'include_service_ids' => null,
            'include_agent_ids'   => null,
            'button_text'         => __( 'Book Now', 'bookster' ),
        ];
    }

    /**
     * @param array $block_attributes
     */
    public function cast_block_attributes( $block_attributes = [] ) {
        if ( '' === $block_attributes ) {
            return [];
        }
        if ( isset( $block_attributes['include_service_ids'] ) ) {
            $block_attributes['include_service_ids'] = array_map( 'intval', explode( ',', $block_attributes['include_service_ids'] ) );
        }
        if ( isset( $block_attributes['include_agent_ids'] ) ) {
            $block_attributes['include_agent_ids'] = array_map( 'intval', explode( ',', $block_attributes['include_agent_ids'] ) );
        }

        return $block_attributes;
    }

    private function enqueue_scripts() {
        $this->enqueue_logic->enqueue_block_booking_form();
    }
}
