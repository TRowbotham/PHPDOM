<?php
namespace Rowbot\DOM\Parser;

use Rowbot\DOM\Support\CodePointStream;

abstract class Parser
{
    protected $inputStream;

    public function __construct()
    {
        $this->inputStream = new CodePointStream();
    }

    abstract public function abort();
    abstract public function preprocessInputStream($aInput);
}
