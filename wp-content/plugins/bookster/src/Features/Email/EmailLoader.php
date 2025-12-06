<?php
namespace Bookster\Features\Email;

/**
 * An Interface to prepare email data
 */
interface EmailLoader {

    /** @return string[] */
    public function get_recipients(): array;
    public function get_subject(): string;
    public function get_message(): string;
    public function get_headers(): array;
}
