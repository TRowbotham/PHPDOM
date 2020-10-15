<?php

declare(strict_types=1);

namespace Rowbot\DOM\Exception;

use Throwable;

/**
 * @see https://heycam.github.io/webidl/#invalidcharactererror
 */
class InvalidCharacterError extends DOMException
{
    public function __construct(string $message = '', Throwable $previous = null)
    {
        if ($message === '') {
            $message = 'The string contains invalid characters.';
        }

        parent::__construct($message, 5, $previous);
    }
}
