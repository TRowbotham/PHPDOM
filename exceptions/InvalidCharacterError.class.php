<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#invalidcharactererror
 */
class InvalidCharacterError extends DOMException
{
    public function __construct($aMessage = '')
    {
        $this->code = 5;
        $this->message = $aMessage ?: 'The string contains invalid characters.';
    }
}
