<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#invalidnodetypeerror
 */
class InvalidNodeTypeError extends DOMException
{
    public function __construct($message = '', $previous = null)
    {
        if ($message === '') {
            $message = 'The supplied node is incorrect or has an incorrect ' .
                'ancestor for this operation.';
        }

        parent::__construct($message, 24, $previous);
    }
}
