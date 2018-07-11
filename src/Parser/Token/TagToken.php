<?php
declare(strict_types=1);

namespace Rowbot\DOM\Parser\Token;

use SplDoublyLinkedList;

/**
 * Start and end tag tokens have a tag name, a self-closing flag, and a list of attributes, each of which has a name and
 * a value. When a start or end tag token is created its self-clising flag must be unset (its other state is that it be
 * set), and its attributes list must be empty.
 *
 * {@inheritDoc}
 */
abstract class TagToken implements Token
{
    /**
     * @var \SplDoublyLinkedList
     */
    public $attributes;

    /**
     * @var bool
     */
    public $isSelfClosing;

    /**
     * @var string
     */
    public $tagName;

    /**
     * Constructor.
     *
     * @param string $tagName
     *
     * @return void
     */
    public function __construct(string $tagName = null)
    {
        $this->attributes = new SplDoublyLinkedList();

        if ($tagName !== null) {
            $this->tagName = $tagName;
        }
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
     *
     * @return bool
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
}
