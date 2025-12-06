<?php
namespace Bookster\Features\Email\EmailLoader;

use Bookster\Services\PaymentService;
use Bookster\Services\SettingsService;
use Bookster\Services\FormatService;
use Bookster\Features\Email\EmailLoader;
use Bookster\Models\AppointmentModel;
use Bookster\Models\BookingModel;
use Bookster\Models\AgentModel;
use Bookster\Models\CustomerModel;
use Bookster\Features\Booking\Details;
use Bookster\Features\Email\DTOs\GeneralOptions;
use Bookster\Features\Email\DTOs\TemplateOptions;
use Bookster\Engine\BEPages\ManagerPage;
use Bookster\Engine\BEPages\AgentPage;

/**
 * Send Appointment Details Email
 */
class ApptNoticeEmailLoader implements EmailLoader {

    /** @var array */
    protected $placeholders;

    /** @var AppointmentModel */
    protected $appointment;
    /** @var BookingModel */
    protected $booking;
    /** @var Details */
    protected $details;
    /** @var AgentModel */
    protected $agent;
    /** @var CustomerModel */
    protected $customer;

    /** @var GeneralOptions */
    protected $general_options;
    /** @var TemplateOptions */
    protected $template_options;

    /** @var string[] */
    protected $recipients;
    /** @var array */
    protected $headers;

    /** @var PaymentService */
    protected $payment_service;
    /** @var SettingsService */
    protected $settings_service;
    /** @var FormatService */
    protected $format_service;

    /**
     * @param AppointmentModel $appointment
     * @param BookingModel     $booking
     * @param GeneralOptions   $general_options
     * @param TemplateOptions  $template_options
     * @param string[]         $recipients
     * @param array            $copy_recipients
     * @param array            $headers
     */
    public function __construct( $appointment, $booking, $general_options, $template_options, $recipients, $copy_recipients = [], $headers = [] ) {
        $this->payment_service  = PaymentService::get_instance();
        $this->settings_service = SettingsService::get_instance();
        $this->format_service   = FormatService::get_instance();

        $this->appointment = $appointment;
        $this->booking     = $booking;
        $this->details     = Details::from_json( $booking->booking_details );
        $this->agent       = $this->appointment->agents[0];
        $this->customer    = $this->booking->customer;

        $this->general_options  = $general_options;
        $this->template_options = $template_options;
        $this->recipients       = $recipients;

        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $this->general_options->sender_name . ' <' . $this->general_options->sender_email . '>';
        $headers[] = 'Reply-To: ' . $this->general_options->sender_email;
        foreach ( $copy_recipients as $recipient ) {
            $headers[] = 'Cc: ' . $recipient;
        }
        $this->headers = $headers;

        $this->load_placeholders();
        do_action( 'bookster_appt_notice_email_loader', $this );
    }

    protected function load_placeholders() {
        $this->placeholders               = array_merge( $this->get_site_placeholders(), $this->get_appointment_placeholders() );
        $text_placeholders                = $this->placeholders;
        $text_placeholders['{site_link}'] = $this->placeholders['{site_title}'];
        $text_placeholders['{Bookster}']  = 'Bookster';

        $this->template_options->subject    = strtr( $this->template_options->subject, $text_placeholders );
        $this->template_options->heading    = strtr( $this->template_options->heading, $text_placeholders );
        $this->template_options->message    = strtr( $this->template_options->message, $this->placeholders );
        $this->general_options->mail_footer = strtr( $this->general_options->mail_footer, $this->placeholders );
    }

    protected function get_site_placeholders() {
        $site_title = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

        return [
            '{site_title}' => $site_title,
            '{site_link}'  => '<a href="' . home_url() . '" target="_blank" style="font-size: inherit;font-weight: inherit; text-decoration: underline; color: ' . esc_attr( $this->general_options->primary_color ) . '">' . $site_title . '</a>',
            '{Bookster}'   => '<a href="https://wpbookster.com" target="_blank" style="font-size: inherit;font-weight: inherit; text-decoration: underline; color: ' . esc_attr( $this->general_options->primary_color ) . '">Bookster</a>',
        ];
    }

    protected function get_appointment_placeholders() {
        $date_format     = $this->format_service->get_date_format();
        $time_format     = $this->format_service->get_time_format();
        $datetime_format = $date_format . ' ' . $time_format;
        $appt_datetime   = gmdate( $datetime_format, strtotime( $this->appointment->datetime_start ) ) . ' - ' . gmdate( $time_format, strtotime( $this->appointment->datetime_end ) );

        return [
            '{appt_number}'    => $this->appointment->appointment_id,
            '{appt_url}'       => $this->get_appt_url(),

            '{appt_datetime}'  => $appt_datetime,
            '{service_name}'   => $this->appointment->service->name,

            '{customer_name}'  => $this->customer->first_name . ' ' . $this->customer->last_name,
            '{agent_name}'     => $this->agent->first_name . ' ' . $this->agent->last_name,

            '{book_status}'    => $this->appointment->book_status,
            '{payment_status}' => $this->booking->payment_status,
        ];
    }

    /**
     * Get Appointment Url
     *
     * @return string
     */
    protected function get_appt_url() {
        $customer_dashboard_page_id = $this->settings_service->get_permissions_settings()['customer_dashboard_page_id'];

        switch ( $this->template_options->notice_event ) {
            case 'new_booking_manager':
                return admin_url(
                    'admin.php?page=' . ManagerPage::MENU_SLUG . '#/appointments?code=' . $this->appointment->appointment_id
                );

            case 'approved_appt_agent':
            case 'canceled_appt_agent':
            case 'manual_resend_appt_agent':
                return admin_url(
                    'admin.php?page=' . AgentPage::MENU_SLUG . '#/appointments?code=' . $this->appointment->appointment_id
                );

            case 'new_pending_booking_customer':
            case 'approved_appt_customer':
            case 'manual_resend_appt_customer':
            default:
                return ( null !== $customer_dashboard_page_id
                    ? get_permalink( $customer_dashboard_page_id ) . '#/?apptId=' . $this->appointment->appointment_id
                    : home_url() );

        }//end switch
    }

    public function get_recipients(): array {
        return $this->recipients;
    }

    public function get_subject(): string {
        return $this->template_options->subject;
    }

    public function get_headers(): array {
        return $this->headers;
    }

    public function get_message(): string {
        $appt_notice_template_file = BOOKSTER_PLUGIN_PATH . 'templates/emails/appt-notification.php';
        $appt_notice_template_file = apply_filters( 'bookster_appt_notice_template_file', $appt_notice_template_file );

        ob_start();
        require $appt_notice_template_file;
        return ob_get_clean();
    }

    /**
     * Print Text Input
     *
     * @param string $input
     */
    protected function print_text_input( $input ) {
        $input = wptexturize( $input );
        echo wp_kses_post( $input );
    }

    /**
     * Print Textarea Input
     *
     * @param string $input
     */
    protected function print_textarea_input( $input ) {
        $input = wpautop( wptexturize( $input ) );
        $input = str_replace( '<p>', '<p style="margin: 0 0 16px; color: inherit; font-size: inherit; font-weight: inherit;">', $input );
        echo wp_kses_post( $input );
    }
}
