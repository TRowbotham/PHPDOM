<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#notfounderror
 */
class NotFoundError extends DOMException
{
    public function __construct($aMessage = '')
    {
        $this->code = 8;
        $this->message = $aMessage ?: 'The object can not be found here.';
    }
}
