<?php
declare(strict_types=1);

namespace Rowbot\DOM\Exception;

use Throwable;

/**
 * @see https://heycam.github.io/webidl/#nomodificationallowederror
 */
class NoModificationAllowedError extends DOMException
{
    /**
     * Constructor
     *
     * @param string     $message
     * @param \Throwable $previous
     *
     * @return void
     */
    public function __construct(
        string $message = '',
        Throwable $previous = null
    ) {
        if ($message === '') {
            $message = 'The object cannot be modified.';
        }

        parent::__construct($message, 7, $previous);
    }
}
