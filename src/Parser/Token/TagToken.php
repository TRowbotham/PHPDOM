<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\Token;

use SplDoublyLinkedList;

/**
 * Start and end tag tokens have a tag name, a self-closing flag, and a list of attributes, each of which has a name and
 * a value. When a start or end tag token is created its self-closing flag must be unset (its other state is that it be
 * set), and its attributes list must be empty.
 */
abstract class TagToken implements Token
{
    /**
     * @var \SplDoublyLinkedList<\Rowbot\DOM\Parser\Token\AttributeToken>
     */
    public $attributes;

    /**
     * @var bool
     */
    private $isSelfClosing;

    /**
     * @var bool
     */
    private $selfClosingFlagAcknowledged;

    /**
     * @var string
     */
    public $tagName;

    public function __construct(string $tagName = '')
    {
        $this->attributes = new SplDoublyLinkedList();
        $this->selfClosingFlagAcknowledged = false;
        $this->tagName = $tagName;
    }

    /**
     * Creates a new empty list for attributes.
     */
    public function clearAttributes(): void
    {
        $this->attributes = new SplDoublyLinkedList();
    }

    /**
     * Determines if the self-closing flag is set.
     */
    public function isSelfClosing(): bool
    {
        return $this->isSelfClosing === true;
    }

    /**
     * Sets the self-closing flag.
     */
    public function setSelfClosingFlag(): void
    {
        $this->isSelfClosing = true;
    }

    /**
     * Acknowledges that the self-closing flag is set.
     */
    public function acknowledge(): void
    {
        $this->selfClosingFlagAcknowledged = true;
    }

    /**
     * Determines if the self-closing flag was acknowledged.
     */
    public function wasAcknowledged(): bool
    {
        return $this->selfClosingFlagAcknowledged;
    }
}
