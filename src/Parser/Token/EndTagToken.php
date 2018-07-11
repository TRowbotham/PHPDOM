<?php
declare(strict_types=1);

namespace Rowbot\DOM\Parser\Token;

/**
 * When an end tag token is emitted with attributes, that is an end-tag-with-attributes parse error. When an end tag
 * token is emitted with its self-closing flag set, that is an end-tag-with-trailing-solidus parse error.
 *
 * {@inheritDoc}
 */
class EndTagToken extends TagToken
{
    /**
     * {@inheritDoc}
     */
    public function __construct(string $tagName = null)
    {
        parent::__construct($tagName);
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): int
    {
        return self::END_TAG_TOKEN;
    }
}
