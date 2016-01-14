<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#invalidcharactererror
 */
class InvalidCharacterError extends \Exception
{
    public function __construct()
    {
        $this->code = 5;
        $this->message = 'The string contains invalid characters.';
    }
}
