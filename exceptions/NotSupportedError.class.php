<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#notsupportederror
 */
class NotSupportedError extends DOMException
{
    public function __construct($aMessage = '')
    {
        $this->code = 9;
        $this->message = $aMessage ?: 'The operation is not supported.';
    }
}
