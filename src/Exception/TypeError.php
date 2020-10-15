<?php

declare(strict_types=1);

namespace Rowbot\DOM\Exception;

use Throwable;

/**
 * @see https://heycam.github.io/webidl/#idl-exceptions
 */
class TypeError extends DOMException
{
    public function __construct(string $message = '', Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
