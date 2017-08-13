<?php
namespace Rowbot\DOM\Parser\Token;

interface Token
{
    const ATTRIBUTE_TOKEN = 1;
    const CHARACTER_TOKEN = 2;
    const COMMENT_TOKEN   = 3;
    const DOCTYPE_TOKEN   = 4;
    const EOF_TOKEN       = 5;
    const START_TAG_TOKEN = 6;
    const END_TAG_TOKEN   = 7;

    public function getType();
}
