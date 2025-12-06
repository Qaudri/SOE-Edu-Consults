<?php
namespace Bookster\Controllers;

use Bookster\Services\AppointmentsService;
use Bookster\Services\BookingsService;
use Bookster\Services\CustomersService;
use Bookster\Services\TransactionsService;
use Bookster\Services\BookingRequestService;
use Bookster\Services\AppointmentMetasService;
use Bookster\Services\BookingMetasService;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Auth\RestAuth;
use Bookster\Models\TransactionModel;
use Bookster\Features\Utils\ArrayUtils;

/**
 * API Controller for Booking Request from Customer
 *
 * @method static BookingRequestController get_instance()
 */
class BookingRequestController extends BaseRestController {
    use SingletonTrait;

    /** @var AppointmentsService */
    private $appointments_service;
    /** @var BookingsService */
    private $bookings_service;
    /** @var CustomersService */
    private $customers_service;
    /** @var TransactionsService */
    private $transactions_service;
    /** @var AppointmentMetasService */
    private $appointment_metas_service;
    /** @var BookingMetasService */
    private $booking_metas_service;
    /** @var BookingRequestService */
    private $booking_request_service;

    protected function __construct() {
        $this->appointments_service      = AppointmentsService::get_instance();
        $this->bookings_service          = BookingsService::get_instance();
        $this->customers_service         = CustomersService::get_instance();
        $this->transactions_service      = TransactionsService::get_instance();
        $this->appointment_metas_service = AppointmentMetasService::get_instance();
        $this->booking_metas_service     = BookingMetasService::get_instance();
        $this->booking_request_service   = BookingRequestService::get_instance();
        $this->init_hooks();
    }

    protected function init_hooks() {
        register_rest_route(
            self::REST_NAMESPACE,
            'bookings/request',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'exec_request_booking' ],
                    'permission_callback' => [ $this, 'user_can_book_appointment' ],
                ],
            ]
        );
    }

    public function request_booking( \WP_REST_Request $request ) {
        $booking_request_input = $request->get_json_params();

        $this->validate_payload_allowed_keys( $booking_request_input );
        $booking_request_input = $this->booking_request_service->validate_and_prepare_transient_booking_request_input( $booking_request_input );

        /**
         * Some data need to be prepared in the backend, e.g: total_amount, payment gateway, ...
         */
        $booking_request_input = apply_filters( 'bookster_prepare_booking_input', $booking_request_input );

        /**
         * Validate input before saving to database, e.g: payment is valid, ...
         */
        $booking_request_input = apply_filters( 'bookster_validate_booking_input', $booking_request_input );

        $appt_input         = $booking_request_input['apptInput'];
        $booking_input      = $booking_request_input['bookingInput'];
        $contact_input      = $booking_request_input['contactInput'];
        $booking_meta_input = $booking_request_input['bookingMetaInput'];

        if ( true === $contact_input['isNewCustomer'] ) {
            // check customer exist with email -> create new customer
            $customer                     = $this->update_contact_for_new_customer( $contact_input['values'] );
            $booking_input['customer_id'] = $customer->customer_id;
        } else {
            $customer                     = $this->update_contact_for_current_customer( $contact_input['values'] );
            $booking_input['customer_id'] = $customer->customer_id;
        }

        /**
         * Which appointment to add the new booking
         */
        $appointment = apply_filters( 'bookster_booking_appointment', null, $appt_input, $booking_input );
        if ( null === $appointment ) {
            $appointment = $this->appointments_service->insert( $appt_input );
        }

        $booking_input['appointment_id'] = $appointment->appointment_id;
        $booking                         = $this->bookings_service->insert( $booking_input );

        $customer_name                           = $customer->first_name . ' ' . $customer->last_name;
        $booking_meta_input['displayActivities'] = [
            [
                'title'     => 'Booked by Customer ' . $customer_name,
                'timestamp' => get_date_from_gmt( $appointment->created_at ),
            ],
        ];

        /** @var TransactionModel|null */
        $transaction = apply_filters( 'bookster_create_booking_transaction', null, $booking_request_input, $appointment, $customer );
        if ( null !== $transaction ) {
            $booking_meta_input['displayActivities'][] = [
                'title'     => 'Payment ' . $transaction->transaction_status,
                'timestamp' => $appointment->created_at,
            ];
        }

        $this->booking_metas_service->upsert_multiple( $booking->appointment_id, $booking->customer_id, $booking_meta_input );

        /** Load Model with Joined Property */
        $appointment = $this->appointments_service->find_by_id_with_info( $appointment->appointment_id );
        $booking     = $this->bookings_service->get_booking( $booking->booking_id );

        /**
         * Do something after new booking, blocking the response, e.g: save meta, validate ...
         */
        do_action( 'bookster_request_booking_success', $appointment, $booking, $transaction, $customer, $booking_request_input );

        return [
            'appointment' => $appointment->to_array_for_customer_role( $customer->customer_id ),
            'transaction' => null === $transaction ? $transaction : $transaction->to_client_array(),
            'customer'    => $customer->to_array_for_customer_role(),
        ];
    }

    private function validate_payload_allowed_keys( $booking_input ) {
        $allowed_keys = [
            'apptInput'        => [
                'service_id',
                'agent_ids',
                'datetime_start',
                'customer_note',
            ],
            'bookingMetaInput' => [],
            'transactionInput' => [ 'transactionId', 'payment_gateway' ],
            'contactInput'     => [ 'isNewCustomer', 'values' ],
        ];
        $allowed_keys = apply_filters( 'bookster_booking_payload_allowed_keys', $allowed_keys );

        foreach ( $booking_input as $input_key => $input_values ) {
            if ( ! array_key_exists( $input_key, $allowed_keys ) ) {
                throw new \InvalidArgumentException( esc_html( "Invalid booking input key: $input_key" ) );
            }

            $allowed_fields = $allowed_keys[ $input_key ];
            foreach ( $input_values as $field_key => $field_value ) {
                if ( ! in_array( $field_key, $allowed_fields, true ) ) {
                    throw new \InvalidArgumentException( esc_html( "Invalid booking input field: $input_key.$field_key" ) );
                }
            }
        }

        $allowed_contact_fields = [ 'first_name', 'last_name', 'email', 'phone', 'customer_id', 'customer_note' ];
        $allowed_contact_fields = apply_filters( 'bookster_booking_contact_allowed_fields', $allowed_contact_fields );
        if ( isset( $booking_input['contactInput']['values'] ) ) {
            $contact_values = $booking_input['contactInput']['values'];
            foreach ( $contact_values as $field_key => $field_value ) {
                if ( ! in_array( $field_key, $allowed_contact_fields, true ) ) {
                    throw new \InvalidArgumentException( esc_html( "Invalid booking contact field: $field_key" ) );
                }
            }
        }

        return $booking_input;
    }

    public function user_can_book_appointment( \WP_REST_Request $request ) {
        $args         = $request->get_json_params();
        $contact_data = $args['contactInput'];

        return $contact_data['isNewCustomer'] ? true : RestAuth::require_login();
    }

    private function update_contact_for_new_customer( $values ) {
        $email      = $values['email'];
        $first_name = $values['first_name'];
        $last_name  = $values['last_name'];

        $wp_user_id = $this->customers_service->connect( $email, $first_name, $last_name );
        do_action( 'register_new_user', $wp_user_id );

        $update_args               = ArrayUtils::pick(
            $values,
            [ 'first_name', 'last_name', 'phone' ]
        );
        $update_args['wp_user_id'] = $wp_user_id;

        $customer = $this->customers_service->find_one_with_info( [ 'email' => $email ] );

        if ( null === $customer ) {
            // create new customer record
            $update_args['email'] = $email;
            $customer             = $this->customers_service->insert( $update_args );
        } else {
            // customer record existed
            $customer->update( $update_args );
        }

        return $customer;
    }

    private function update_contact_for_current_customer( $values ) {
        $current_user = wp_get_current_user();
        $customer_id  = $values['customer_id'];

        $update_args               = ArrayUtils::pick(
            $values,
            [ 'first_name', 'last_name', 'phone' ]
        );
        $update_args['wp_user_id'] = $current_user->ID;

        if ( null !== $customer_id ) {
            // update record with customer_id
            $customer = $this->customers_service->require_customer_is_current_user( $customer_id );
            $customer->update( $update_args );

            return $customer;
        }

        $customer = $this->customers_service->find_one_with_info( [ 'email' => $current_user->user_email ] );

        if ( null === $customer ) {
            // create new customer record
            $update_args['email'] = $current_user->user_email;
            $customer             = $this->customers_service->insert( $update_args );
        } else {
            // customer record existed
            $customer->update( $update_args );
        }

        return $customer;
    }

    public function exec_request_booking( $request ) {
        return $this->exec_write( [ $this, 'request_booking' ], $request );
    }
}
