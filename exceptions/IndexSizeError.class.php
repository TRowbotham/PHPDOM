<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#indexsizeerror
 */
class IndexSizeError extends DOMException
{
    public function __construct($aMessage = '')
    {
        $this->code = 1;
        $this->message = $aMessage ?: 'The index is not in the allowed range.';
    }
}
