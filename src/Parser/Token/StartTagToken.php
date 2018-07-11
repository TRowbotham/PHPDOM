<?php
declare(strict_types=1);

namespace Rowbot\DOM\Parser\Token;

/**
 * When a start tag token is emitted with its self-closing flag set, if the flag is not acknowledged by the tree
 * construction stage, that is a non-void-html-element-start-tag-with-trailing-solidus parse error.
 *
 * {@inheritDoc}
 */
class StartTagToken extends TagToken
{
    /**
     * @var bool
     */
    private $selfClosingFlagAcknowledged;

    /**
     * {@inheritDoc}
     */
    public function __construct(string $tagName = null)
    {
        parent::__construct($tagName);

        $this->selfClosingFlagAcknowledged = false;
    }

    /**
     * Acknowledges that the self-closing flag is set.
     *
     * @return void
     */
    public function acknowledge(): void
    {
        $this->selfClosingFlagAcknowledged = true;
    }

    /**
     * Determines if the self-closing flag was acknowledged.
     *
     * @return bool
     */
    public function wasAcknowledged(): bool
    {
        return $this->selfClosingFlagAcknowledged;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): int
    {
        return self::START_TAG_TOKEN;
    }
}
