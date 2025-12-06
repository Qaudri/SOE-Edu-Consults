<?php
namespace Bookster\Services;

use Bookster\Features\Utils\SingletonTrait;

/**
 * Email Settings Service
 *
 * @method static EmailSettingsService get_instance()
 */
class EmailSettingsService extends BaseService {
    use SingletonTrait;

    public const CORE_SETTINGS_EMAIL_OPTION = 'bookster_core_settings_email';

    public function get_email_settings() {
        return array_replace_recursive(
            $this->get_default_email_settings(),
            get_option( self::CORE_SETTINGS_EMAIL_OPTION, [] )
        );
    }

    public function update_general_options( $options ) {
        $email_settings                    = $this->get_email_settings();
        $email_settings['general_options'] = $options;

        update_option( self::CORE_SETTINGS_EMAIL_OPTION, $email_settings );
    }

    public function update_notice_options( $notice_event, $notice_options, $manager_email = null ) {
        $email_settings                                   = $this->get_email_settings();
        $email_settings['notifications'][ $notice_event ] = $notice_options;
        if ( $manager_email ) {
            $email_settings['manager_email'] = $manager_email;
        }

        update_option( self::CORE_SETTINGS_EMAIL_OPTION, $email_settings );
    }

    public function update_notice_enabled( $notice_event, $enabled ) {
        $email_settings = $this->get_email_settings();
        $email_settings['notifications'][ $notice_event ]['enabled'] = $enabled;

        update_option( self::CORE_SETTINGS_EMAIL_OPTION, $email_settings );
    }

    private function get_default_email_settings() {
        return [
            'general_options' => [
                'sender_name'     => get_bloginfo( 'name' ),
                'sender_email'    => get_bloginfo( 'admin_email' ),
                'mail_footer'     => '{site_link} - Powered by {Bookster}',

                'primary_color'   => '#2563EB',
                'text_color'      => '#1F2937',
                'mail_background' => '#F3F4F5',
                'body_background' => '#FFFFFF',
            ],
            'notifications'   => [
                'new_booking_manager'          => [
                    'enabled' => true,
                    'subject' => __( '[{site_title}]: New booking #{appt_number}', 'bookster' ),
                    'heading' => __( 'New Booking: #{appt_number}', 'bookster' ),
                    'message' => __( "Hi <strong>Manager</strong>,\nYou have received the following booking from {customer_name}.", 'bookster' ),
                ],

                'new_pending_booking_customer' => [
                    'enabled' => true,
                    'subject' => __( 'Your {site_title} booking has been received!', 'bookster' ),
                    'heading' => __( 'Booking received #{appt_number}', 'bookster' ),
                    'message' => __( "Hi <strong>{customer_name}</strong>,\nThank you for your booking. We are working on your schedule.", 'bookster' ),
                ],
                'approved_appt_customer'       => [
                    'enabled' => true,
                    'subject' => __( 'Your {site_title} appointment has been confirmed!', 'bookster' ),
                    'heading' => __( 'Appointment confirmed #{appt_number}', 'bookster' ),
                    'message' => __( "Hi <strong>{customer_name}</strong>,\nYour {service_name} appointment has been confirmed.", 'bookster' ),
                ],

                'approved_appt_agent'          => [
                    'enabled' => true,
                    'subject' => __( '[{site_title}]: Approved appointment #{appt_number}', 'bookster' ),
                    'heading' => __( 'Approved appointment: #{appt_number}', 'bookster' ),
                    'message' => __( "Hi <strong>{agent_name}</strong>,\nCongratulation on the {service_name} appointment with {customer_name}!", 'bookster' ),
                ],
                'canceled_appt_agent'          => [
                    'enabled' => false,
                    'subject' => __( '[{site_title}]: Canceled appointment #{appt_number}', 'bookster' ),
                    'heading' => __( 'Canceled appointment: #{appt_number}', 'bookster' ),
                    'message' => __( "Hi <strong>{agent_name}</strong>,\nYour {service_name} appointment with {customer_name} has been canceled.", 'bookster' ),
                ],

                'manual_resend_appt_customer'  => [
                    'subject' => __( 'Details for appointment #{appt_number} on {site_title}', 'bookster' ),
                    'heading' => __( 'Details for appointment #{appt_number}', 'bookster' ),
                    'message' => __( "Hi <strong>{customer_name}</strong>,\nYou have a {service_name} appointment with {agent_name}.", 'bookster' ),
                ],
                'manual_resend_appt_agent'     => [
                    'subject' => __( '[{site_title}]: Details for appointment #{appt_number}', 'bookster' ),
                    'heading' => __( 'Details for appointment #{appt_number}', 'bookster' ),
                    'message' => __( "Hi <strong>{agent_name}</strong>,\nYou have a {service_name} appointment with {customer_name}.", 'bookster' ),
                ],
            ],
            'manager_email'   => [
                'recipient' => get_bloginfo( 'admin_email' ),
                'cc_emails' => [],
            ],
        ];
    }
}
