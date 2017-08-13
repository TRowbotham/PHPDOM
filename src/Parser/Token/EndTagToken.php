<?php
namespace Rowbot\DOM\Parser\Token;

class EndTagToken extends TagToken
{
    public function __construct($aTagName = null)
    {
        parent::__construct($aTagName);
    }

    public function getType()
    {
        return self::END_TAG_TOKEN;
    }
}
