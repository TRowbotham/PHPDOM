<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#dfn-DOMException
 */
class DOMException extends \Exception
{
    public function __construct($aMessage = '')
    {
        $this->message = $aMessage;
    }
}
