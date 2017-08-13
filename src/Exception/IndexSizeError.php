<?php
namespace Rowbot\DOM\Exception;

/**
 * @see https://heycam.github.io/webidl/#indexsizeerror
 */
class IndexSizeError extends DOMException
{
    public function __construct($message = '', $previous = null)
    {
        if ($message === '') {
            $message = 'The index is not in the allowed range.';
        }

        parent::__construct($message, 1, $previous);
    }
}
