<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#inuseattributeerror
 */
class InUseAttributeError extends \Exception
{
    public function __construct()
    {
        $this->code = 10;
        $this->message = 'The attribute is in use.';
    }
}
