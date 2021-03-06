<?php

declare(strict_types=1);

namespace Rowbot\DOM\Exception;

use Throwable;

/**
 * @see https://heycam.github.io/webidl/#notsupportederror
 */
class NotSupportedError extends DOMException
{
    public function __construct(string $message = '', Throwable $previous = null)
    {
        if ($message === '') {
            $message = 'The operation is not supported.';
        }

        parent::__construct($message, 9, $previous);
    }
}
