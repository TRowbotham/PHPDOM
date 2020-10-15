<?php

declare(strict_types=1);

namespace Rowbot\DOM\Exception;

use Throwable;

/**
 * @see https://heycam.github.io/webidl/#inuseattributeerror
 */
class InUseAttributeError extends DOMException
{
    public function __construct(string $message = '', Throwable $previous = null)
    {
        if ($message === '') {
            $message = 'The attribute is in use.';
        }

        parent::__construct($message, 10, $previous);
    }
}
