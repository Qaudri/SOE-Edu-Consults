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

<table style="border: 0; border-spacing: 0; width: 100%; border-radius: 8px 8px 0 0; background-color: <?php echo esc_attr( $primary_color ); ?>;">
    <tbody>
        <tr>
            <td style="padding: 16px 24px; display: block;">
                <h1 style="margin: 0; font-size: 24px; font-weight: 500; text-align: left; background-color: inherit; color: <?php echo esc_attr( $primary_foreground ); ?>;">
                    <?php
                    if ( ! empty( $this->template_options->heading ) ) {
                        $this->print_text_input( $this->template_options->heading );
                    }
                    ?>
                </h1>
            </td>
        </tr>
    </tbody>
</table>