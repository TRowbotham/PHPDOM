<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#wrongdocumenterror
 */
class WrongDocumentError extends \Exception
{
    public function __construct()
    {
        $this->code = 4;
        $this->message = 'The object is in the wrong document.';
    }
}
