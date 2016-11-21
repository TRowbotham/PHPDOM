<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#inuseattributeerror
 */
class InUseAttributeError extends DOMException
{
    public function __construct($message = '', $previous = null)
    {
        if ($message === '') {
            $message = 'The attribute is in use.';
        }

        parent::__construct($message, 10, $previous);
    }
}
