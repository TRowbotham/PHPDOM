<?php
namespace phpjs\parser\tokens;

class CharacterToken implements Token
{
    public $data;

    public function __construct($aData = null)
    {
        $this->data = null;

        if ($aData !== null) {
            $this->data = $aData;
        }
    }

    public function getType()
    {
        return self::CHARACTER_TOKEN;
    }
}
