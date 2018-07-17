<?php
declare(strict_types=1);

namespace Rowbot\DOM\Exception;

/**
 * @see https://heycam.github.io/webidl/#hierarchyrequesterror
 */
class HierarchyRequestError extends DOMException
{
    public function __construct(string $message = '', $previous = null)
    {
        if ($message === '') {
            $message = 'The operation would yield an incorrect node tree.';
        }

        parent::__construct($message, 3, $previous);
    }
}
