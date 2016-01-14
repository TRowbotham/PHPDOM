<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#notfounderror
 */
class NotFoundError extends \Exception
{
    public function __construct()
    {
        $this->code = 8;
        $this->message = 'The object can not be found here.';
    }
}
