<?php
namespace Rowbot\DOM\Exception;

/**
 * @see https://heycam.github.io/webidl/#wrongdocumenterror
 */
class WrongDocumentError extends DOMException
{
    public function __construct(string $message = '', $previous = null)
    {
        if ($message === '') {
            $message = 'The object is in the wrong document.';
        }

        parent::__construct($message, 4, $previous);
    }
}
