<?php
defined( 'ABSPATH' ) || exit;

use Bookster\Features\Email\EmailLoader\ApptNoticeEmailLoader;

/** @var string $mail_background */
/** @var string $body_background */
/** @var string $primary_color */
/** @var string $primary_foreground */
/** @var string $text_color */
/** @var string $text60 */
/** @var string $text40 */
/** @var ApptNoticeEmailLoader $this */

?>

<table style="border: 0; border-spacing: 0; width: 100%; border-radius: 0 0 8px 8px; background-color: <?php echo esc_attr( $body_background ); ?>;">
    <tbody>
        <tr>
            <td style="padding: 24px; vertical-align: 0; text-align: left; font-size: 16px; color: <?php echo esc_attr( $text_color ); ?>;">
                <div>
                    <?php
                    if ( ! empty( $this->template_options->message ) ) {
                        $this->print_textarea_input( $this->template_options->message );
                    }
                    ?>
                </div>

                <?php
                $appt_notice_summary_template_file = BOOKSTER_PLUGIN_PATH . 'templates/emails/appt-notification/appt-summary.php';
                $appt_notice_summary_template_file = apply_filters( 'bookster_appt_notice_summary_template_file', $appt_notice_summary_template_file );
                require $appt_notice_summary_template_file;
                ?>

                <?php
                $appt_notice_booking_details_template_file = BOOKSTER_PLUGIN_PATH . 'templates/emails/appt-notification/appt-booking-details.php';
                $appt_notice_booking_details_template_file = apply_filters( 'bookster_appt_notice_booking_details_template_file', $appt_notice_booking_details_template_file );
                require $appt_notice_booking_details_template_file;
                ?>
            </td>
        </tr>
    </tbody>
</table>