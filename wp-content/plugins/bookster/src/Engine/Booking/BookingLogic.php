<?php
namespace Bookster\Engine\Booking;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Services\PaymentService;
use Bookster\Services\ServicesService;
use Bookster\Features\Enums\PaymentGatewayEnum;
use Bookster\Features\Enums\PaymentStatusEnum;

/**
 * Bookster Core Booking Logic Hooks
 */
class BookingLogic {
    use SingletonTrait;

    /** @var ServicesService */
    private $services_service;
    /** @var PaymentService */
    private $payment_service;

    protected function __construct() {
        $this->payment_service  = PaymentService::get_instance();
        $this->services_service = ServicesService::get_instance();

        add_filter( 'bookster_validate_booking_input', [ $this, 'validate_payment_gateway_enabled' ], 10, 1 );
    }

    public function validate_payment_gateway_enabled( $booking_input ) {
        $transaction_input = $booking_input['transactionInput'];
        $payment_gateway   = $transaction_input['payment_gateway'];

        if ( empty( $payment_gateway ) ) {
            throw new \Exception( 'Payment Gateway is Required' );
        }

        if ( ! $this->payment_service->is_payment_gateway_enabled( $payment_gateway ) ) {
            throw new \Exception( 'Payment Gateway is Not Enabled' );
        }

        if ( PaymentGatewayEnum::FREE === $payment_gateway ) {
            if ( 0 === $booking_input['bookingInput']['booking_details']['tax']['total'] ) {
                $booking_input['bookingInput']['payment_status'] = PaymentStatusEnum::COMPLETE;
            } else {
                throw new \Exception( 'Free Payment can only be selected when total amount is 0' );
            }
        }

        return $booking_input;
    }
}
