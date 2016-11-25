<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#hierarchyrequesterror
 */
class HierarchyRequestError extends DOMException
{
    public function __construct($message = '', $previous = null)
    {
        if ($message === '') {
            $message = 'The operation would yield an incorrect node tree.';
        }

        parent::__construct($message, 3, $previous);
    }
}
