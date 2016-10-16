<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#wrongdocumenterror
 */
class WrongDocumentError extends DOMException
{
    public function __construct($aMessage = '')
    {
        $this->code = 4;
        $this->message = $aMessage ?: 'The object is in the wrong document.';
    }
}
