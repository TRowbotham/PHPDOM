<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#namespaceerror
 */
class NamespaceError extends \Exception
{
    public function __construct()
    {
        $this->code = 14;
        $this->message = 'The operation is not allowed by Namespaces in XML.';
    }
}
