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

$details = $this->details;
$appt    = $this->appointment;
?>

<div>
    <table style="border: 0; border-spacing: 0; width: 100%;">
        <tr>
            <td colspan="2" style="font-size: 14px; font-weight: 500; text-align: center; color: <?php echo esc_attr( $text60 ); ?>;">
                <?php esc_html_e( 'Booking Details', 'bookster' ); ?>:
            </td>
        </tr>

        <tr>
            <td colspan="2" style="padding: 16px 0 0 0; border-top: 1px dashed <?php echo esc_attr( $text60 ); ?>;"></td>
        </tr>

        <?php
        $booking_length = count( $details->booking->items );
        for ( $i = 0; $i < $booking_length; $i++ ) :
            $item = $details->booking->items[ $i ];
            ?>
            <tr>
                <td style="padding: 0; margin: 0;">
                    <?php echo esc_html( $item->title ); ?> ( x<?php echo esc_html( $item->quantity ); ?> )
                </td>
                <td style="padding: 0; margin: 0; text-align: right;">
                    <?php echo esc_html( $this->payment_service->format_price( $item->amount->to_string() ) ); ?>
                </td>
            </tr>
            <?php
        endfor;

        $adjustment_length = count( $details->adjustment->items );
        for ( $i = 0; $i < $adjustment_length; $i++ ) :
            $item = $details->adjustment->items[ $i ];
            ?>
            <tr>
                <td style="padding: 0; margin: 0;">
                    <?php echo esc_html( $item->title ); ?>
                </td>
                <td style="padding: 0; margin: 0; text-align: right;">
                    <?php echo esc_html( $this->payment_service->format_price( $item->amount->to_string() ) ); ?>
                </td>
            </tr>
            <?php
        endfor;

        $tax_length = count( $details->tax->items );

        if ( $tax_length > 0 ) :
            ?>
            <tr>
                <td style="padding: 8px 0; margin: 0; font-size: 17px; font-weight: 600;">
                    <?php echo esc_html_e( 'Subtotal', 'bookster' ); ?>
                </td>
                <td style="padding: 8px 0; margin: 0; font-size: 17px; font-weight: 600; text-align: right;">
                    <?php echo esc_html( $this->payment_service->format_price( $details->adjustment->subtotal->to_string() ) ); ?>
                </td>
            </tr>
            <?php
        endif;

        for ( $i = 0; $i < $tax_length; $i++ ) :
            $item = $details->tax->items[ $i ];
            ?>
            <tr>
                <td style="padding: 0; margin: 0;">
                    <?php echo esc_html( $item->title ); ?>
                </td>
                <td style="padding: 0; margin: 0; text-align: right;">
                    <?php echo esc_html( $this->payment_service->format_price( $item->amount->to_string() ) ); ?>
                </td>
            </tr>
            <?php
        endfor;
        ?>

        <tr>
            <td colspan="2" style="padding: 16px 0 0 0; border-bottom: 1px dashed <?php echo esc_attr( $text60 ); ?>;"></td>
        </tr>

        <tr>
            <td style="padding: 16px 0 0 0; margin: 0; font-size: 20px; font-weight: 700;">
                <?php echo esc_html_e( 'Total', 'bookster' ); ?>
            </td>
            <td style="padding: 16px 0 0 0; margin: 0; font-size: 20px; font-weight: 700; text-align: right;">
                <?php echo esc_html( $this->payment_service->format_price( $details->tax->total->to_string() ) ); ?>
            </td>
        </tr>
    </table>
</div>
