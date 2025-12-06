<?php
namespace Bookster\Services;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Enums\PaymentGatewayEnum;

/**
 * Payment Service
 *
 * @method static PaymentService get_instance()
 */
class PaymentService extends BaseService {
    use SingletonTrait;

    /** @var SettingsService */
    private $settings_service;

    protected function __construct() {
        $this->settings_service = SettingsService::get_instance();
    }

    public function get_currency() {
        $public_data = $this->settings_service->get_public_data();
        $currency    = $public_data['paymentSettings']['currency'];

        return $currency;
    }


    public function format_price( string $price ) {
        $payment_settings  = $this->settings_service->get_public_data()['paymentSettings'];
        $currency_position = $payment_settings['currency_position'];
        $currency          = $payment_settings['currency'];
        $symbol            = $payment_settings['currency_symbol'] ?? $currency;

        switch ( $currency_position ) {
            case 'before':
                return "$symbol$price";
            case 'before_with_space':
                return "$symbol $price";
            case 'after':
                return "$price$symbol";
            case 'after_with_space':
                return "$price $symbol";
        }
    }

    public function is_payment_gateway_enabled( string $payment_gateway_key ) {
        if ( PaymentGatewayEnum::FREE === $payment_gateway_key ) {
            return true;
        }

        $payment_gateway = $this->settings_service->get_public_data()['paymentGateway'];

        return $payment_gateway_key === $payment_gateway['now'] || $payment_gateway_key === $payment_gateway['later'];
    }
}
