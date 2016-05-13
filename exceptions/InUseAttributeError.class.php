<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#inuseattributeerror
 */
class InUseAttributeError extends DOMException
{
    public function __construct($aMessage = '')
    {
        $this->code = 10;
        $this->message = $aMessage ?: 'The attribute is in use.';
    }
}
