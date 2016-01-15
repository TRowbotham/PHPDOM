<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#invalidstateerror
 */
class InvalidStateError extends \Exception
{
    public function __construct($aMessage = '')
    {
        $this->code = 11;
        $this->message = $aMessage ?: 'This object is in an invalid state';
    }
}
