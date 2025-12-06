<?php
namespace Bookster\Services;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Logger;
use Bookster\Features\Email\DTOs\TemplateOptions;
use Bookster\Features\Email\DTOs\GeneralOptions;
use Bookster\Features\Email\EmailLoader\ApptNoticeEmailLoader;
use Bookster\Models\AppointmentModel;
use Bookster\Features\Enums\BookStatusEnum;
use Bookster\Features\Enums\PaymentStatusEnum;
use Bookster\Models\BookingModel;

/**
 * Sending Email Service
 *
 * @method static EmailService get_instance()
 */
class EmailService extends BaseService {
    use SingletonTrait;

    /** @var AppointmentsService */
    private $appointments_service;
    /** @var SettingsService */
    private $settings_service;

    protected function __construct() {
        $this->appointments_service = AppointmentsService::get_instance();
        $this->settings_service     = SettingsService::get_instance();
    }

    public function send_mail( $to, $subject, $message, $headers = [] ) {
        try {
            if ( empty( $headers ) ) {
                $headers[] = 'Content-Type: text/html; charset=UTF-8';
            }
            $status = wp_mail( $to, $subject, $message, $headers );
            return $status;
        } catch ( \Throwable $ex ) {
            Logger::log_error( $ex );
            return false;
        }
    }

    /**
     * @param EmailLoader $mail_loader
     * @return bool
     */
    public function send_mail_loader( $mail_loader ) {
        return $this->send_mail(
            $mail_loader->get_recipients(),
            $mail_loader->get_subject(),
            $mail_loader->get_message(),
            $mail_loader->get_headers()
        );
    }

    /**
     * @param AppointmentModel $appt
     * @param BookingModel     $booking
     * @param string           $notice_event
     */
    public function send_appt_notice_manager( $appt, $booking, $notice_event ) {
        $email_settings    = $this->settings_service->get_manager_data()['emailSettings'];
        $manager_email     = $email_settings['manager_email']['recipient'];
        $manager_cc_emails = $email_settings['manager_email']['cc_emails'];

        $mail_loader = new ApptNoticeEmailLoader(
            $appt,
            $booking,
            GeneralOptions::from_json( $email_settings['general_options'] ),
            TemplateOptions::from_json( $email_settings['notifications'][ $notice_event ], $notice_event ),
            [ $manager_email ],
            $manager_cc_emails
        );
        return $this->send_mail_loader( $mail_loader );
    }

    /**
     * @param AppointmentModel $appt
     * @param BookingModel     $booking
     * @param string           $notice_event
     */
    public function send_appt_notice_customer( $appt, $booking, $notice_event ) {
        $email_settings = $this->settings_service->get_manager_data()['emailSettings'];
        $customer_email = $booking->customer->email;

        $mail_loader = new ApptNoticeEmailLoader(
            $appt,
            $booking,
            GeneralOptions::from_json( $email_settings['general_options'] ),
            TemplateOptions::from_json( $email_settings['notifications'][ $notice_event ], $notice_event ),
            [ $customer_email ]
        );
        return $this->send_mail_loader( $mail_loader );
    }

    /**
     * @param AppointmentModel $appt
     * @param BookingModel     $booking
     * @param string           $notice_event
     */
    public function send_appt_notice_agent( $appt, $booking, $notice_event ) {
        $email_settings = $this->settings_service->get_manager_data()['emailSettings'];
        $agent_emails   = array_map(
            function( $agent ) {
                return $agent->email;
            },
            $appt->agents
        );

        $mail_loader = new ApptNoticeEmailLoader(
            $appt,
            $booking,
            GeneralOptions::from_json( $email_settings['general_options'] ),
            TemplateOptions::from_json( $email_settings['notifications'][ $notice_event ], $notice_event ),
            $agent_emails
        );
        return $this->send_mail_loader( $mail_loader );
    }

    /**
     * Send Preview Email
     *
     * @param string $recipient
     * @param array  $template_options
     * @param array  $general_options
     */
    public function send_preview_email( $recipient, $template_options, $general_options = null ) {
        $email_settings = $this->settings_service->get_manager_data()['emailSettings'];
        if ( null === $general_options ) {
            $general_options = $email_settings['general_options'];
        }

        $mail_loader = new ApptNoticeEmailLoader(
            $this->get_preview_appt(),
            $this->get_preview_booking(),
            GeneralOptions::from_json( $general_options ),
            TemplateOptions::from_json( $template_options ),
            [ $recipient ]
        );

        return $this->send_mail_loader( $mail_loader );
    }

    private function get_preview_appt() {
        $next_week = wp_date( 'Y-m-d', strtotime( '+7 days' ) );

        $appt = new AppointmentModel(
            [
                'appointment_id' => 12345,
                'book_status'    => BookStatusEnum::APPROVED,
                'datetime_start' => $next_week . ' 10:00:00',
                'datetime_end'   => $next_week . ' 10:30:00',

                'service'        => wp_json_encode(
                    [
                        'name' => 'Greatest Service Ever',
                    ]
                ),
                '_agents'        => wp_json_encode(
                    [
                        [
                            'agent_id'   => -1,
                            'first_name' => 'Emma',
                            'last_name'  => 'Harper',
                        ],
                    ]
                ),

            ]
        );
        $appt->init_model();

        return $appt;
    }

    private function get_preview_booking() {
        $booking = new BookingModel(
            [
                'booking_id'      => 123123,
                'payment_status'  => PaymentStatusEnum::COMPLETE,
                'total_amount'    => 189,
                'paid_amount'     => 189,
                'booking_details' => wp_json_encode( $this->get_preview_booking_details() ),
                '_customer'       => wp_json_encode(
                    [
                        'customer_id' => -1,
                        'first_name'  => 'Grace',
                        'last_name'   => 'Stone',
                    ],
                ),
            ]
        );
        $booking->init_model();

        return $booking;
    }

    private function get_preview_booking_details() {
        return [
            'booking'    => [
                'items'    => [
                    [
                        'id'        => 'aaa-bbb-ccc-ddd',
                        'title'     => 'Greatest Service Ever',
                        'quantity'  => 1,
                        'unitPrice' => 199,
                        'amount'    => 199,
                    ],
                ],
                'subtotal' => 199,
            ],
            'adjustment' => [
                'items'    => [
                    [
                        'id'      => 'aaa-ccc-bbb-ddd',
                        'title'   => 'Discount',
                        'formula' => [ 'fixed', -20 ],
                        'amount'  => -20,
                    ],
                ],
                'subtotal' => 179,
            ],
            'tax'        => [
                'items' => [
                    [
                        'id'      => 'ddd-ccc-bbb-aaa',
                        'title'   => 'VAT',
                        'formula' => [ 'fixed', 10 ],
                        'amount'  => 10,
                    ],
                ],
                'total' => 189,
            ],
        ];
    }
}
