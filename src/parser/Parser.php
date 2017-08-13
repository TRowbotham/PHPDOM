<?php
namespace Rowbot\DOM\Parser;

use Rowbot\DOM\Support\CodePointStream;

abstract class Parser
{
    const VOID_TAGS = '/^(area|base|basefont|bgsound|br|col|embed|frame|hr|' .
        'img|input|keygen|link|menuitem|meta|param|source|track|wbr)$/';

    protected $inputStream;

    public function __construct()
    {
        $this->inputStream = new CodePointStream();
    }

    abstract public function abort();
    abstract public function preprocessInputStream($aInput);
}
