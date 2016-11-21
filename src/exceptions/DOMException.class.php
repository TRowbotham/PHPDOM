<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#dfn-DOMException
 */
class DOMException extends \Exception
{
    public function __construct($message = '', $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
