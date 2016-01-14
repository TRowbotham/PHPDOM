<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#notsupportederror
 */
class NotSupportedError extends \Exception
{
    public function __construct()
    {
        $this->code = 9;
        $this->message = 'The operation is not supported.';
    }
}
