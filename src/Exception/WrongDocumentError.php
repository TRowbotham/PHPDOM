<?php

declare(strict_types=1);

namespace Rowbot\DOM\Exception;

use Throwable;

/**
 * @see https://heycam.github.io/webidl/#wrongdocumenterror
 */
class WrongDocumentError extends DOMException
{
    public function __construct(string $message = '', Throwable $previous = null)
    {
        if ($message === '') {
            $message = 'The object is in the wrong document.';
        }

        parent::__construct($message, 4, $previous);
    }
}
