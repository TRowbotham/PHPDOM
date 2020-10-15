<?php

declare(strict_types=1);

namespace Rowbot\DOM\Exception;

use Throwable;

/**
 * @see https://heycam.github.io/webidl/#notfounderror
 */
class NotFoundError extends DOMException
{
    public function __construct(string $message = '', Throwable $previous = null)
    {
        if ($message === '') {
            $message = 'The object can not be found here.';
        }

        parent::__construct($message, 8, $previous);
    }
}
