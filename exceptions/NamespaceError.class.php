<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#namespaceerror
 */
class NamespaceError extends \Exception
{
    public function __construct($aMessage = '')
    {
        $this->code = 14;
        $this->message = $aMessage ?: 'The operation is not allowed by
            Namespaces in XML.';
    }
}
