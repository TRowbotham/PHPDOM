<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#idl-exceptions
 */
class TypeError extends DOMException
{
    public function __construct($aMessage = '')
    {
        $this->message = $aMessage;
    }
}
