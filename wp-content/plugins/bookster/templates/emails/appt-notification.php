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

$appt_notice_email_styles_template_file = BOOKSTER_PLUGIN_PATH . 'templates/emails/appt-notification/email-styles.php';
$appt_notice_email_styles_template_file = apply_filters( 'bookster_appt_notice_email_styles_template_file', $appt_notice_email_styles_template_file );
require $appt_notice_email_styles_template_file;

?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?php echo esc_html( get_bloginfo( 'name', 'display' ) ); ?></title>
</head>
<body  <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0"
    style="padding: 0; background-color: <?php echo esc_attr( $mail_background ); ?>;"
>

<table style="width: 100%; background-color: <?php echo esc_attr( $mail_background ); ?>;">
    <tbody>
        <tr>
            <td></td>
            <td style="width: 576px;">
                <div style="margin: 0 auto; padding: 60px 0; width: 100%; max-width: 576px; -webkit-text-size-adjust: none;">
                    <table style="border: 0; border-spacing: 0; height: 100%; width: 100%;">
                        <tbody>
                            <tr>
                                <td style="padding: 0; vertical-align: 0;">
                                    <table style="border: 0; border-spacing: 0; width: 100%;">
                                        <tbody>
                                            <tr>
                                                <td style="padding: 0; vertical-align: 0;">
                                                <?php
                                                $appt_notice_email_header_template_file = BOOKSTER_PLUGIN_PATH . 'templates/emails/appt-notification/email-header.php';
                                                $appt_notice_email_header_template_file = apply_filters( 'bookster_appt_notice_email_header_template_file', $appt_notice_email_header_template_file );
                                                require $appt_notice_email_header_template_file;
                                                ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 0; vertical-align: 0;">
                                                <?php
                                                $appt_notice_email_body_template_file = BOOKSTER_PLUGIN_PATH . 'templates/emails/appt-notification/email-body.php';
                                                $appt_notice_email_body_template_file = apply_filters( 'bookster_appt_notice_email_body_template_file', $appt_notice_email_body_template_file );
                                                require $appt_notice_email_body_template_file;
                                                ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 0;vertical-align: 0;">
                                <?php
                                $appt_notice_email_footer_template_file = BOOKSTER_PLUGIN_PATH . 'templates/emails/appt-notification/email-footer.php';
                                $appt_notice_email_footer_template_file = apply_filters( 'bookster_appt_notice_email_footer_template_file', $appt_notice_email_footer_template_file );
                                require $appt_notice_email_footer_template_file;
                                ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </td>
            <td></td>
        </tr>
    </tbody>
</table>

</body>
</html>