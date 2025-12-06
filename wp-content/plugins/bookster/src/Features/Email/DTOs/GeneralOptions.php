<?php
namespace Bookster\Features\Email\DTOs;

/**
 * GeneralOptions DTO
 * Allow changing each Email options
 */
class GeneralOptions {

    /** @var string */
    public $sender_name;
    /** @var string */
    public $sender_email;
    /** @var string */
    public $mail_footer;

    /** @var string */
    public $primary_color;
    /** @var string */
    public $text_color;
    /** @var string */
    public $mail_background;
    /** @var string */
    public $body_background;

    public static function from_json( $data ) {
        return new self(
            $data['sender_name'],
            $data['sender_email'],
            $data['mail_footer'],
            $data['primary_color'],
            $data['text_color'],
            $data['mail_background'],
            $data['body_background']
        );
    }

    public function __construct(
        $sender_name,
        $sender_email,
        $mail_footer,
        $primary_color,
        $text_color,
        $mail_background,
        $body_background
    ) {
        $this->sender_name  = $sender_name;
        $this->sender_email = $sender_email;
        $this->mail_footer  = $mail_footer;

        $this->primary_color   = $primary_color;
        $this->text_color      = $text_color;
        $this->mail_background = $mail_background;
        $this->body_background = $body_background;
    }
}
