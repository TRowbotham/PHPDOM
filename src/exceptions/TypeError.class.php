<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#idl-exceptions
 */
class TypeError extends DOMException
{
    public function __construct($message = '', $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
