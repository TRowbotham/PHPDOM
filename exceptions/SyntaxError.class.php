<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#syntaxerror
 */
class SyntaxError extends \Exception
{
    public function __construct()
    {
        $this->code = 12;
        $this->message = 'The string did not match the expected pattern.';
    }
}
