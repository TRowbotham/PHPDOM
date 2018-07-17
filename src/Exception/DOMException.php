<?php
declare(strict_types=1);

namespace Rowbot\DOM\Exception;

/**
 * @see https://heycam.github.io/webidl/#dfn-DOMException
 */
class DOMException extends \Exception
{
    public function __construct(string $message = '', $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
