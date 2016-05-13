<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#syntaxerror
 */
class SyntaxError extends DOMException
{
    public function __construct($aMessage = '')
    {
        $this->code = 12;
        $this->message = $aMessage ?: 'The string did not match the expected
            pattern.';
    }
}
