<?php
namespace Rowbot\DOM\Parser\Token;

class DoctypeToken implements Token
{
    public $publicIdentifier;
    public $name;
    public $forceQuirksMode;
    public $systemIdentifier;

    public function __construct()
    {
        $this->forceQuirksMode = 'off';
        $this->publicIdentifier = null;
        $this->name = null;
        $this->systemIdentifier = null;
    }

    public function getQuirksMode()
    {
        return $this->forceQuirksMode;
    }

    public function setQuirksMode($aMode)
    {
        $this->forceQuirksMode = $aMode;
    }

    public function getType()
    {
        return self::DOCTYPE_TOKEN;
    }
}
