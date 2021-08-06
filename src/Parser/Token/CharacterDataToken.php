<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\Token;

/**
 * A shared abstraction for character and comment tokens, which both have data.
 */
abstract class CharacterDataToken implements Token
{
    public string $data;

    public function __construct(string $data = '')
    {
        $this->data = $data;
    }
}
