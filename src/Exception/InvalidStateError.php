<?php

declare(strict_types=1);

namespace Rowbot\DOM\Exception;

use Throwable;

/**
 * @see https://heycam.github.io/webidl/#invalidstateerror
 */
class InvalidStateError extends DOMException
{
    public function __construct(string $message = '', Throwable $previous = null)
    {
        if ($message === '') {
            $message = 'This object is in an invalid state.';
        }

        parent::__construct($message, 11, $previous);
    }
}
