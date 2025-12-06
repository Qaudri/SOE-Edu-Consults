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

$appt          = $this->appointment;
$appt_url      = $this->placeholders['{appt_url}'];
$customer_name = $this->placeholders['{customer_name}'];
$agent_name    = $this->placeholders['{agent_name}'];
$appt_datetime = $this->placeholders['{appt_datetime}'];
?>

<p style="margin: 0 0 16px; font-size: 22px; font-weight: 600; text-align: center; color: <?php echo esc_attr( $primary_color ); ?>;">
    <?php esc_html_e( 'Appointment', 'bookster' ); ?> <a href="<?php echo esc_attr( $appt_url ); ?>" target="_blank" style="color: inherit; text-decoration: underline;">
        #<?php echo esc_html( $appt->appointment_id ); ?>
    </a>
</p>
    
<p style="margin: 0; font-size: 18px; font-weight: 500; color: inherit">
    <?php echo esc_html( $appt->service->name ); ?>
</p>

<p style="margin: 0; font-size: inherit; font-weight: inherit; color: <?php echo esc_attr( $primary_color ); ?>;">
    <?php echo esc_html( $appt_datetime ); ?>
</p>

<div>
    <table style="border: 0; border-spacing: 0; width: 100%; margin: 16px 0;">
        <tbody>
            <tr>
                <td style="width: 50%; border-right: 1px dashed <?php echo esc_attr( $text60 ); ?>;">
                    <p style="margin: 0; font-size: 14; font-weight: 500; color: <?php echo esc_attr( $text60 ); ?>;">
                        <?php esc_html_e( 'Customer', 'bookster' ); ?>:
                    </p>
                    <p style="margin: 0; font-size: 18; font-weight: 500; color: inherit;">
                        <?php echo esc_html( $customer_name ); ?>
                    </p>
                </td>
                <td>
                    <p style="margin: 0; font-size: 14; font-weight: 500; text-align: right; color: <?php echo esc_attr( $text60 ); ?>;">
                        <?php esc_html_e( 'Agent', 'bookster' ); ?>:
                    </p>
                    <p style="margin: 0; font-size: 18; font-weight: 500; color: inherit; text-align: right;">
                        <?php echo esc_html( $agent_name ); ?>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>
</div>
