<?php
defined( 'ABSPATH' ) || exit;

use Bookster\Features\Email\EmailLoader\ApptNoticeEmailLoader;

/** @var ApptNoticeEmailLoader $this */

?>

<table style="border: 0; border-spacing: 0; width: 100%;">
    <tbody>
        <tr>
            <td style="padding: 8px 0; vertical-align: 0; text-align: center; font-size: 16px; color: <?php echo esc_attr( $text_color ); ?>;">
                <?php
                if ( ! empty( $this->general_options->mail_footer ) ) {
                    $this->print_textarea_input( $this->general_options->mail_footer );
                }
                ?>
            </td>
        </tr>
    </tbody>
</table>