<?php
namespace Bookster\Features\Email\DTOs;

/**
 * TemplateOptions DTO
 * Allow changing each Email options
 */
class TemplateOptions {

    /** @var string */
    public $notice_event;
    /** @var string */
    public $subject;
    /** @var string */
    public $heading;
    /** @var string */
    public $message;

    public static function from_json( $data, $notice_event = 'preview' ) {
        return new self(
            $data['subject'],
            $data['heading'],
            $data['message'],
            $notice_event
        );
    }

    public function __construct(
        $subject,
        $heading,
        $message,
        $notice_event
    ) {
        $this->subject      = $subject;
        $this->heading      = $heading;
        $this->message      = $message;
        $this->notice_event = $notice_event;
    }
}
