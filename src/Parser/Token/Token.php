<?php
declare(strict_types=1);

namespace Rowbot\DOM\Parser\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/parsing.html#tokenization
 */
interface Token
{
    public const ATTRIBUTE_TOKEN = 1;
    public const CHARACTER_TOKEN = 2;
    public const COMMENT_TOKEN   = 3;
    public const DOCTYPE_TOKEN   = 4;
    public const EOF_TOKEN       = 5;
    public const START_TAG_TOKEN = 6;
    public const END_TAG_TOKEN   = 7;

    /**
     * Returns the token's type.
     *
     * @return int
     */
    public function getType(): int;
}
