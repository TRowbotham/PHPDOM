<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#invalidcharactererror
 */
class InvalidCharacterError extends DOMException
{
    public function __construct($message = '', $previous = null)
    {
        if ($message === '') {
            $message = 'The string contains invalid characters.';
        }

        parent::__construct($message, 5, $previous);
    }
}