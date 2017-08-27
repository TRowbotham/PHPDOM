<?php
namespace Rowbot\DOM\Parser\Token;

class EOFToken implements Token
{
    public function __construct()
    {
    }

    public function getType()
    {
        return self::EOF_TOKEN;
    }
}
