<?php
namespace Bookster\Engine\Tasks;

use Bookster\Features\Tasks\BaseTask;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Tasks\Dispatcher\AsyncDispatcher;
use Bookster\Services\EmailService;
use Bookster\Services\AppointmentsService;
use Bookster\Services\BookingsService;
use Bookster\Features\Errors\InvalidArgumentException;

/**
 * Sending Emails for a specific Appointment.
 * This is the new version which address more options.
 *
 * @method static SendApptNoticeEmailsTask get_instance()
 */
class SendApptNoticeEmailsTask extends BaseTask {
    use SingletonTrait;

    protected $task_name = 'bookster_task_send_appt_notice_emails';

    /** @var EmailService */
    private $email_service;
    /** @var AppointmentsService */
    private $appointments_service;
    /** @var BookingsService */
    private $bookings_service;

    protected function __construct() {
        parent::init_hooks();

        $this->email_service        = EmailService::get_instance();
        $this->appointments_service = AppointmentsService::get_instance();
        $this->bookings_service     = BookingsService::get_instance();
    }

    public function task_callback( $args ) {
        if ( empty( $args ) || ! isset( $args['appt_id'], $args['booking_id'], $args['notice_event'] ) ) {
            throw new InvalidArgumentException( 'Missing Email Arguments.' );
        }

        $appt_id      = $args['appt_id'];
        $booking_id   = $args['booking_id'];
        $notice_event = $args['notice_event'];

        $appt    = $this->appointments_service->find_by_id_with_info( $appt_id );
        $booking = $this->bookings_service->get_booking( $booking_id );

        switch ( $notice_event ) {
            case 'new_booking_manager':
                return $this->email_service->send_appt_notice_manager( $appt, $booking, $notice_event );

            case 'new_pending_booking_customer':
            case 'approved_appt_customer':
            case 'manual_resend_appt_customer':
                return $this->email_service->send_appt_notice_customer( $appt, $booking, $notice_event );

            case 'approved_appt_agent':
            case 'canceled_appt_agent':
            case 'manual_resend_appt_agent':
                return $this->email_service->send_appt_notice_agent( $appt, $booking, $notice_event );

            default:
                return null;
        }
    }

    /**
     * Dispatch Email
     *
     * @param int    $appt_id
     * @param int    $booking_id
     * @param string $notice_event
     */
    public function dispatch_email( $appt_id, $booking_id, $notice_event ) {
        $args = [
            'appt_id'      => $appt_id,
            'booking_id'   => $booking_id,
            'notice_event' => $notice_event,
        ];

        $this->dispatch( $args );
    }

    protected function create_dispatcher() {
        return new AsyncDispatcher( $this->task_name );
    }
}
