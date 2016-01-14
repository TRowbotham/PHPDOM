<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#indexsizeerror
 */
class IndexSizeError extends \Exception
{
    public function __construct()
    {
        $this->code = 1;
        $this->message = 'The index is not in the allowed range.';
    }
}
