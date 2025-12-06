<?php
namespace Bookster\Engine\FEBlocks;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Scripts\EnqueueLogic;

/**
 * Customer Dashboard Shortcode
 */
class CustomerDashboardShortcode {
    use SingletonTrait;

    /** @var EnqueueLogic */
    private $enqueue_logic;

    /** Hooks Initialization */
    protected function __construct() {
        $this->enqueue_logic = EnqueueLogic::get_instance();

        add_shortcode( 'bookster_customer_dashboard', [ $this, 'render_customer_dashboard_block' ] );
    }

    /**
     * Render block.
     *
     * @param array $block_attributes
     */
    public function render_customer_dashboard_block( $block_attributes ) {
        $block_attributes = shortcode_atts(
            $this->get_default_attributes(),
            $this->cast_block_attributes( $block_attributes ),
            'bookster_customer_dashboard'
        );

        $content = '<div class="bookster-root shortcode-bookster-customer-dashboard"
            style="min-height: 640px; min-width: 800px; max-width: 1152px; margin-left: auto; margin-right: auto;"
            data-id="' . esc_attr( uniqid( '', true ) ) . '"
            data-attributes="' . esc_attr( wp_json_encode( $block_attributes ) ) . '">
        </div>';

        $this->enqueue_scripts();
        return $content;
    }

    private function get_default_attributes() {
        return [
            'service_ids' => [],
            'agent_ids'   => [],
        ];
    }

    /**
     * @param array $block_attributes
     */
    private function cast_block_attributes( $block_attributes = [] ) {
        if ( '' === $block_attributes ) {
            return [];
        }
        if ( isset( $block_attributes['service_ids'] ) ) {
            $block_attributes['service_ids'] = array_map( 'intval', explode( ',', $block_attributes['service_ids'] ) );
        }
        if ( isset( $block_attributes['agent_ids'] ) ) {
            $block_attributes['agent_ids'] = array_map( 'intval', explode( ',', $block_attributes['agent_ids'] ) );
        }

        return $block_attributes;
    }

    public function enqueue_scripts() {
        $this->enqueue_logic->enqueue_block_customer_dashboard();
    }
}
