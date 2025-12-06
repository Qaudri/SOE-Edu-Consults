<?php
namespace Bookster\Services;

use Bookster\Features\Booking\Details;
use Bookster\Features\Booking\Details\Booking;
use Bookster\Features\Booking\Details\Adjustment;
use Bookster\Features\Booking\Details\BookingItem;
use Bookster\Features\Booking\Details\Tax;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Services\ServicesService;
use Bookster\Features\Utils\Decimal;
use Bookster\Features\Utils\RandomUtils;
use Bookster\Features\Enums\VisibilityEnum;
use Bookster\Features\Enums\BookStatusEnum;
use Bookster\Features\Enums\PaymentStatusEnum;

/**
 * Logic for Booking Request from Customer
 *
 * @method static BookingRequestService get_instance()
 */
class BookingRequestService extends BaseService {
    use SingletonTrait;

    /** @var ServicesService */
    private $services_service;
    /** @var AgentsService */
    private $agents_service;
    /** @var SettingsService */
    private $settings_service;
    /** @var AppointmentsService */
    private $appointments_service;

    protected function __construct() {
        $this->services_service     = ServicesService::get_instance();
        $this->agents_service       = AgentsService::get_instance();
        $this->settings_service     = SettingsService::get_instance();
        $this->appointments_service = AppointmentsService::get_instance();
    }

    /**
     * Adding transient fields booking details, total amount, book datetime
     *
     * @param array $booking_request_input The booking input from payload.
     * @return array Adding transient fields booking details, total amount, book datetime.
     * @throws \InvalidArgumentException When the booking input is invalid.
     */
    public function validate_and_prepare_transient_booking_request_input( $booking_request_input ): array {
        $appt_input    = $booking_request_input['apptInput'];
        $contact_input = $booking_request_input['contactInput'];

        $service_id = $appt_input['service_id'];
        $service    = $this->services_service->find_by_id_with_info( $service_id );
        $agent_id   = $appt_input['agent_ids'][0];
        $agent      = $this->agents_service->find_by_id( $agent_id );

        // Validate service and agent
        if ( false === $service->activated || VisibilityEnum::PUBLIC !== $service->visibility ) {
            throw new \InvalidArgumentException( __( 'Sorry. The Service is not available for Booking!', 'bookster' ) );
        }
        if ( false === $agent->activated || VisibilityEnum::PUBLIC !== $agent->visibility ) {
            throw new \InvalidArgumentException( __( 'Sorry. The Agent is not available for Booking!', 'bookster' ) );
        }

        // Prepare transient datetime fields
        $booking_duration            = [
            'duration'      => $service->duration,
            'buffer_before' => $service->buffer_before,
            'buffer_after'  => $service->buffer_after,
        ];
        $booking_duration            = apply_filters( 'bookster_booking_duration', $booking_duration, $booking_request_input, $service );
        $appt_input['buffer_before'] = $booking_duration['buffer_before'];
        $appt_input['buffer_after']  = $booking_duration['buffer_after'];

        $datetime_start     = $appt_input['datetime_start'];
        $php_datetime_start = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $datetime_start, wp_timezone() );

        if ( false === $php_datetime_start ) {
            throw new \InvalidArgumentException( __( 'Sorry. Invalid datetime format!', 'bookster' ) );
        }

        $php_datetime_end        = $php_datetime_start->add( new \DateInterval( 'PT' . $booking_duration['duration'] . 'M' ) );
        $php_busy_datetime_start = $php_datetime_start->sub( new \DateInterval( 'PT' . $booking_duration['buffer_before'] . 'M' ) );
        $php_busy_datetime_end   = $php_datetime_end->add( new \DateInterval( 'PT' . $booking_duration['buffer_after'] . 'M' ) );

        if ( $php_busy_datetime_start->format( 'd' ) !== $php_datetime_start->format( 'd' ) ) {
            $php_busy_datetime_start = $php_datetime_start->setTime( 0, 0 );
        }
        if ( $php_busy_datetime_end->format( 'd' ) !== $php_datetime_end->format( 'd' ) ) {
            $php_busy_datetime_end = $php_datetime_end->setTime( 23, 59 );
        }

        $appt_input['datetime_end']        = $php_datetime_end->format( 'Y-m-d H:i:s' );
        $appt_input['busy_datetime_start'] = $php_busy_datetime_start->format( 'Y-m-d H:i:s' );
        $appt_input['busy_datetime_end']   = $php_busy_datetime_end->format( 'Y-m-d H:i:s' );

        $hour                        = $php_datetime_start->format( 'H' );
        $min                         = $php_datetime_start->format( 'i' );
        $abs_min_start               = (int) $hour * 60 + (int) $min;
        $appt_input['abs_min_start'] = $abs_min_start;

        $hour                      = $php_datetime_end->format( 'H' );
        $min                       = $php_datetime_end->format( 'i' );
        $abs_min_end               = (int) $hour * 60 + (int) $min;
        $appt_input['abs_min_end'] = $abs_min_end;

        $hour                             = $php_busy_datetime_start->format( 'H' );
        $min                              = $php_busy_datetime_start->format( 'i' );
        $busy_abs_min_start               = (int) $hour * 60 + (int) $min;
        $appt_input['busy_abs_min_start'] = $busy_abs_min_start;

        $hour                           = $php_busy_datetime_end->format( 'H' );
        $min                            = $php_busy_datetime_end->format( 'i' );
        $busy_abs_min_end               = (int) $hour * 60 + (int) $min;
        $appt_input['busy_abs_min_end'] = $busy_abs_min_end;

        $utc_datetime_start               = $php_datetime_start->setTimezone( new \DateTimeZone( 'UTC' ) );
        $appt_input['utc_datetime_start'] = $utc_datetime_start->format( 'Y-m-d H:i:s' );

        // Validate datetime restrictions and customer booked appointments
        $this->validate_book_restrictions( $php_datetime_start );

        $book_status               = $this->settings_service->get_public_data()['generalSettings']['default_appointment_status'];
        $appt_input['book_status'] = $book_status;

        // Prepare details, amount
        $blueprint_details = $this->make_blueprint_details( $service );
        $details           = $this->extend_booking_details( $blueprint_details, $booking_request_input );

        $booking_input                    = [];
        $booking_input['booking_details'] = $details->to_json();
        $booking_input['total_amount']    = $details->tax->total->to_string();
        $booking_input['paid_amount']     = '0.00';
        $booking_input['payment_status']  = PaymentStatusEnum::INCOMPLETE;
        if ( isset( $contact_input['values']['customer_note'] ) ) {
            $booking_input['customer_note'] = $contact_input['values']['customer_note'];
        }

        $booking_request_input['apptInput']    = $appt_input;
        $booking_request_input['bookingInput'] = $booking_input;
        return $booking_request_input;
    }

    /**
     * Generate Booking Details when Customer is making a booking
     *
     * @param array $booking_request_input
     * @return Details
     */
    public function make_booking_details( $booking_request_input ): Details {
        $appt_input = $booking_request_input['apptInput'];

        $service_id = $appt_input['service_id'];
        $service    = $this->services_service->find_by_id( $service_id );

        $blueprint_details = $this->make_blueprint_details( $service );
        $details           = $this->extend_booking_details( $blueprint_details, $booking_request_input );

        return $details;
    }

    private function make_blueprint_details( $service ): Details {
        $service_item = new BookingItem(
            RandomUtils::gen_unique_id(),
            $service->name,
            1,
            Decimal::from_string( $service->price ),
            Decimal::zero()
        );

        $booking    = new Booking( [ $service_item ], Decimal::zero() );
        $adjustment = new Adjustment( [], Decimal::zero() );
        $tax        = new Tax( [], Decimal::zero() );

        $blueprint_details = new Details( $booking, $adjustment, $tax );
        return $blueprint_details;
    }

    private function extend_booking_details( $blueprint_details, $booking_request_input ): Details {
        /** @var Details $details */
        $details = apply_filters( 'bookster_make_booking_details', $blueprint_details, $booking_request_input );
        $details->calculate();
        $details->clean();

        return $details;
    }

    /**
     * Validate datetime_start is between booking restrictions
     *
     * @param \DateTimeImmutable $datetime_start
     */
    private function validate_book_restrictions( $datetime_start ) {
        $book_restriction_earliest = $this->settings_service->get_public_data()['generalSettings']['book_restriction_earliest'];
        $book_restriction_latest   = $this->settings_service->get_public_data()['generalSettings']['book_restriction_latest'];

        $now = new \DateTimeImmutable( 'now', wp_timezone() );

        if ( 'no_restriction' !== $book_restriction_earliest[0] ) {
            $restriction_unit = $book_restriction_earliest[0];
            $number_of        = $book_restriction_earliest[1];

            $datetime_earliest = $this->get_datetime_earliest( $now, $restriction_unit, $number_of );

            if ( $datetime_start < $datetime_earliest ) {
                throw new \InvalidArgumentException( __( 'Sorry. Your Booking Time is no longer Valid. Please select another Appointment Time!', 'bookster' ) );
            }
        }

        if ( 'no_restriction' !== $book_restriction_latest[0] ) {
            $restriction_unit = $book_restriction_latest[0];
            $number_of        = $book_restriction_latest[1];

            $datetime_latest = $this->get_datetime_latest( $now, $restriction_unit, $number_of );

            if ( $datetime_start > $datetime_latest ) {
                throw new \InvalidArgumentException( __( 'Sorry. Your Booking Time is no longer Valid. Please select another Appointment Time!', 'bookster' ) );
            }
        }
    }

    private function get_duration_str( string $restriction_unit, int $number_of ): string {
        switch ( $restriction_unit ) {
            case 'minutes':
                return 'PT' . $number_of . 'M';
            case 'hours':
                return 'PT' . $number_of . 'H';
            case 'days':
                return 'P' . $number_of . 'D';
            case 'months':
                return 'P' . $number_of . 'M';
            case 'years':
                return 'P' . $number_of . 'Y';
            default:
                return 'T0M';
        }
    }

    /**
     * Calulate the earliest datetime based on the restriction unit and number of
     *
     * @param \DateTimeImmutable $now
     * @param string             $restriction_unit
     * @param int                $number_of
     *
     * @return \DateTimeImmutable
     */
    private function get_datetime_earliest( $now, $restriction_unit, $number_of ) {
        $duration_str      = $this->get_duration_str( $restriction_unit, $number_of );
        $datetime_earliest = $now->sub( new \DateInterval( $duration_str ) );

        if ( 'minutes' === $restriction_unit || 'hours' === $restriction_unit ) {
            // Allow 5 minutes room for customer to fill in the form
            $datetime_earliest = $datetime_earliest->sub( new \DateInterval( 'PT5M' ) );
        } elseif ( 'days' === $restriction_unit || 'months' === $restriction_unit || 'years' === $restriction_unit ) {
            // Allow from the start of the day
            $datetime_earliest = $datetime_earliest->setTime( 0, 0 );
        }

        return $datetime_earliest;
    }

    /**
     * Calulate the latest datetime based on the restriction unit and number of
     *
     * @param \DateTimeImmutable $now
     * @param string             $restriction_unit
     * @param int                $number_of
     *
     * @return \DateTimeImmutable
     */
    private function get_datetime_latest( $now, $restriction_unit, $number_of ) {
        $duration_str    = $this->get_duration_str( $restriction_unit, $number_of );
        $datetime_latest = $now->add( new \DateInterval( $duration_str ) );

        if ( 'days' === $restriction_unit || 'months' === $restriction_unit || 'years' === $restriction_unit ) {
            // Allow until the end of the day
            $datetime_latest = $datetime_latest->setTime( 23, 59 );
        }

        return $datetime_latest;
    }
}
