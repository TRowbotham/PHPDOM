<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use ArrayAccess;
use Countable;
use Iterator;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\NotFoundError;

/**
 * Represents a list of named attributes.
 *
 * @see https://dom.spec.whatwg.org/#namednodemap
 * @see https://developer.mozilla.org/en-US/docs/Web/API/NamedNodeMap
 *
 * @property-read int $length Returns the number of attributes in the list.
 *
 * @implements \ArrayAccess<int, \Rowbot\DOM\Attr>
 * @implements \Iterator<int, \Rowbot\DOM\Attr>
 */
class NamedNodeMap implements ArrayAccess, Countable, Iterator
{
    /**
     * @var \Rowbot\DOM\Element\Element
     */
    private $element;

    public function __construct(Element $element)
    {
        $this->element = $element;
    }

    public function __get(string $name): int
    {
        switch ($name) {
            case 'length':
                return $this->element->getAttributeList()->count();
        }
    }

    /**
     * Finds the Attr node at the given index.
     *
     * @see https://dom.spec.whatwg.org/#dom-namednodemap-item
     */
    public function item(int $index): ?Attr
    {
        return $this->element->getAttributeList()->offsetGet($index);
    }

    /**
     * Finds an Attr node with the given qualified name.
     *
     * @see https://dom.spec.whatwg.org/#dom-namednodemap-getnameditem
     */
    public function getNamedItem(string $qualifiedName): ?Attr
    {
        return $this->element->getAttributeList()->getAttrByName($qualifiedName);
    }

    /**
     * Finds the Attr node with the given namespace and localname.
     *
     * @see https://dom.spec.whatwg.org/#dom-namednodemap-getnameditemns
     */
    public function getNamedItemNS(?string $namespace, string $localName): ?Attr
    {
        return $this->element->getAttributeList()->getAttrByNamespaceAndLocalName(
            $namespace,
            $localName
        );
    }

    /**
     * Adds the given attribute to the element's attribute list.
     *
     * @see https://dom.spec.whatwg.org/#dom-namednodemap-setnameditem
     */
    public function setNamedItem(Attr $attr): ?Attr
    {
        return $this->element->getAttributeList()->setAttr($attr);
    }

    /**
     * Adds the given attribute to the element's attribute list.
     *
     * @see https://dom.spec.whatwg.org/#dom-namednodemap-setnameditemns
     */
    public function setNamedItemNS(Attr $attr): ?Attr
    {
        return $this->element->getAttributeList()->setAttr($attr);
    }

    /**
     * Removes the attribute with the given qualified name from the element's attribute list.
     *
     * @see https://dom.spec.whatwg.org/#dom-namednodemap-removenameditem
     *
     * @throws \Rowbot\DOM\Exception\NotFoundError
     */
    public function removeNamedItem(string $qualifiedName): Attr
    {
        $attr = $this->element->getAttributeList()->removeAttrByName($qualifiedName);

        if (!$attr) {
            throw new NotFoundError();
        }

        return $attr;
    }

    /**
     * Removes the attribute with the given qualified name from the element's attribute list.
     *
     * @see https://dom.spec.whatwg.org/#dom-namednodemap-removenameditemns
     *
     * @throws \Rowbot\DOM\Exception\NotFoundError
     */
    public function removeNamedItemNS(
        ?string $namespace,
        string $localName
    ): Attr {
        $attr = $this->element->getAttributeList()->removeAttrByNamespaceAndLocalName(
            $namespace,
            $localName
        );

        if (!$attr) {
            throw new NotFoundError();
        }

        return $attr;
    }

    /**
     * Indicates whether an attribute node exists at the given offset.
     *
     * @param int $offset
     */
    public function offsetExists($offset): bool
    {
        return $this->element->getAttributeList()->offsetExists($offset);
    }

    /**
     * Returns the attribute node at the given offset.
     *
     * @param int $offset
     */
    public function offsetGet($offset): ?Attr
    {
        return $this->element->getAttributeList()->offsetGet($offset);
    }

    /**
     * Noop.
     *
     * @param int $offset
     * @param \Rowbot\DOM\Attr $attr
     */
    public function offsetSet($offset, $attr): void
    {
        // Do nothing.
    }

    /**
     * Noop.
     *
     * @param int $offset
     */
    public function offsetUnset($offset): void
    {
        // Do nothing.
    }

    /**
     * Returns the number of attribute nodes in the list.
     */
    public function count(): int
    {
        return $this->element->getAttributeList()->count();
    }

    /**
     * Returns the current attribute node.
     */
    public function current(): Attr
    {
        return $this->element->getAttributeList()->current();
    }

    /**
     * Returns the current iterator key.
     */
    public function key(): int
    {
        return $this->element->getAttributeList()->key();
    }

    /**
     * Advances the iterator to the next item.
     */
    public function next(): void
    {
        $this->element->getAttributeList()->next();
    }

    /**
     * Rewinds the iterator to the beginning.
     */
    public function rewind(): void
    {
        $this->element->getAttributeList()->rewind();
    }

    /**
     * Indicates if the iterator is still valid.
     */
    public function valid(): bool
    {
        return $this->element->getAttributeList()->valid();
    }
}
