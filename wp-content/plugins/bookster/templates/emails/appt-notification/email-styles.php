<?php
defined( 'ABSPATH' ) || exit;

use Bookster\Features\Email\EmailLoader\ApptNoticeEmailLoader;
use Bookster\Features\Utils\HexColorUtils;

/** @var ApptNoticeEmailLoader $this */

$general_options = $this->general_options;

$mail_background = $general_options->mail_background;
$body_background = $general_options->body_background;
$primary_color   = $general_options->primary_color;
$text_color      = $general_options->text_color;

$primary_foreground = HexColorUtils::get_foreground_color( $primary_color );

$is_text_light = HexColorUtils::is_light( $text_color );
$text60        = HexColorUtils::adjust_brightness( $text_color, $is_text_light ? -40 : 40 );
$text40        = HexColorUtils::adjust_brightness( $text_color, $is_text_light ? -60 : 60 );
