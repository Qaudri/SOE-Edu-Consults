<?php
namespace Bookster\Features\Enums;

/**
 * Bookster Payment Gateway Enum
 */
class PaymentGatewayEnum {

    const FREE   = 'payment-free';
    const LATER  = 'payment-later';
    const STRIPE = 'payment-stripe';
    const PAYPAL = 'payment-paypal';
}
