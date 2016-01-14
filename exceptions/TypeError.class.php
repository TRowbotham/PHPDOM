<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#idl-exceptions
 */
class TypeError extends \Exception
{
    public function __construct($aMessage = '')
    {
        $this->message = $aMessage;
    }
}
