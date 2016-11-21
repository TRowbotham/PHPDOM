<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#notfounderror
 */
class NotFoundError extends DOMException
{
    public function __construct($message = '', $previous = null)
    {
        if ($message === '') {
            $message = 'The object can not be found here.';
        }

        parent::__construct($message, 8, $previous);
    }
}
