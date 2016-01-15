<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#hierarchyrequesterror
 */
class HierarchyRequestError extends \Exception
{
    public function __construct($aMessage = '')
    {
        $this->code = 3;
        $this->message = $aMessage ?: 'The operation would yield an incorrect
            node tree.';
    }
}
