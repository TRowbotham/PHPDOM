<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#invalidstateerror
 */
class InvalidStateError extends \Exception
{
    public function __construct()
    {
        $this->code = 11;
        $this->message = 'This object is in an invalid state';
    }
}
