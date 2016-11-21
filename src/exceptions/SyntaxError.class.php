<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#syntaxerror
 */
class SyntaxError extends DOMException
{
    public function __construct($message = '', $previous = null)
    {
        if ($message === '') {
            $message = 'The string did not match the expected pattern.';
        }

        parent::__construct($message, 12, $previous);
    }
}
