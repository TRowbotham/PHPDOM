<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#invalidnodetypeerror
 */
class InvalidNodeTypeError extends \Exception
{
    public function __construct()
    {
        $this->code = 24;
        $this->message = 'The supplied node is incorrect or has an incorrect ancestor for this operation.';
    }
}
