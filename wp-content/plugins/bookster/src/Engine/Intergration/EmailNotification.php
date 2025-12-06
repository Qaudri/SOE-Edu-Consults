<?php
namespace Bookster\Engine\Intergration;

use Bookster\Engine\Tasks\SendApptNoticeEmailsTask;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Services\SettingsService;
use Bookster\Models\AppointmentModel;
use Bookster\Features\Enums\BookStatusEnum;

/**
 * Bookster Notification Hooks
 */
class EmailNotification {
    use SingletonTrait;

    /** @var SettingsService */
    private $settings_service;

    /** Hooks Initialization */
    protected function __construct() {
        $this->settings_service = SettingsService::get_instance();

        add_action( 'bookster_request_booking_success', [ $this, 'send_new_booking_email' ], 10, 2 );
        add_action( 'bookster_manager_create_appointment', [ $this, 'send_create_appointment_email' ], 10, 1 );
        add_action( 'bookster_manager_update_appointment', [ $this, 'send_update_appointment_email' ], 10, 2 );
    }

    /**
     * Send New Booking Email
     *
     * @param AppointmentModel $appt
     * @param BookingModel     $booking
     */
    public function send_new_booking_email( $appt, $booking ) {
        $book_status   = $appt->book_status;
        $notifications = $this->settings_service->get_manager_data()['emailSettings']['notifications'];
        $send_task     = SendApptNoticeEmailsTask::get_instance();

        if ( true === $notifications['new_booking_manager']['enabled'] ) {
            $send_task->dispatch_email(
                $appt->appointment_id,
                $booking->booking_id,
                'new_booking_manager'
            );
        }

        if ( BookStatusEnum::PENDING === $book_status ) {
            if ( true === $notifications['new_pending_booking_customer']['enabled'] ) {
                $send_task->dispatch_email(
                    $appt->appointment_id,
                    $booking->booking_id,
                    'new_pending_booking_customer'
                );
            }
        }

        if ( BookStatusEnum::APPROVED === $book_status ) {
            if ( true === $notifications['approved_appt_customer']['enabled'] ) {
                $send_task->dispatch_email(
                    $appt->appointment_id,
                    $booking->booking_id,
                    'approved_appt_customer'
                );
            }

            if ( true === $notifications['approved_appt_agent']['enabled'] ) {
                $send_task->dispatch_email(
                    $appt->appointment_id,
                    $booking->booking_id,
                    'approved_appt_agent'
                );
            }
        }
    }

    /**
     * Send Create Appointment Email
     *
     * @param AppointmentModel $appt
     */
    public function send_create_appointment_email( $appt ) {
        $book_status   = $appt->book_status;
        $notifications = $this->settings_service->get_manager_data()['emailSettings']['notifications'];
        $send_task     = SendApptNoticeEmailsTask::get_instance();

        if ( BookStatusEnum::APPROVED === $book_status ) {
            if ( true === $notifications['approved_appt_customer']['enabled'] ) {
                foreach ( $appt->bookings as $booking ) {
                    $send_task->dispatch_email(
                        $appt->appointment_id,
                        $booking->booking_id,
                        'approved_appt_customer'
                    );
                }
            }

            if ( true === $notifications['approved_appt_agent']['enabled'] ) {
                $send_task->dispatch_email(
                    $appt->appointment_id,
                    $appt->bookings[0]->booking_id,
                    'approved_appt_agent'
                );
            }
        }

        if ( BookStatusEnum::CANCELED === $book_status ) {
            if ( true === $notifications['canceled_appt_agent']['enabled'] ) {
                $send_task->dispatch_email(
                    $appt->appointment_id,
                    $appt->bookings[0]->booking_id,
                    'canceled_appt_agent'
                );
            }
        }
    }

    /**
     * Send Update Appointment Email
     *
     * @param AppointmentModel $appt
     * @param AppointmentModel $old_appt
     */
    public function send_update_appointment_email( $appt, $old_appt ) {
        if ( $old_appt->book_status === $appt->book_status ) {
            return;
        }
        $book_status   = $appt->book_status;
        $notifications = $this->settings_service->get_manager_data()['emailSettings']['notifications'];
        $send_task     = SendApptNoticeEmailsTask::get_instance();

        if ( BookStatusEnum::APPROVED === $book_status ) {
            if ( true === $notifications['approved_appt_customer']['enabled'] ) {
                foreach ( $appt->bookings as $booking ) {
                    $send_task->dispatch_email(
                        $appt->appointment_id,
                        $booking->booking_id,
                        'approved_appt_customer'
                    );
                }
            }

            if ( true === $notifications['approved_appt_agent']['enabled'] ) {
                $send_task->dispatch_email(
                    $appt->appointment_id,
                    $appt->bookings[0]->booking_id,
                    'approved_appt_agent'
                );
            }
        }

        if ( BookStatusEnum::CANCELED === $book_status ) {
            if ( true === $notifications['canceled_appt_agent']['enabled'] ) {
                $send_task->dispatch_email(
                    $appt->appointment_id,
                    $appt->bookings[0]->booking_id,
                    'canceled_appt_agent'
                );
            }
        }
    }
}
