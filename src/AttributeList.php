<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use ArrayAccess;
use Countable;
use Iterator;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\InUseAttributeError;
use Rowbot\DOM\Support\Collection\NodeSet;
use SplObjectStorage;

/**
 * @implements \ArrayAccess<int, \Rowbot\DOM\Attr>
 * @implements \Iterator<int, \Rowbot\DOM\Attr>
 */
class AttributeList implements ArrayAccess, Countable, Iterator
{
    /**
     * @var \Rowbot\DOM\Support\Collection\NodeSet<\Rowbot\DOM\Attr>
     */
    private $list;

    /**
     * @var \Rowbot\DOM\Element\Element
     */
    private $element;

    /**
     * @var \SplObjectStorage<\Rowbot\DOM\AttributeChangeObserver>
     */
    private $observers;

    public function __construct(Element $element)
    {
        $this->list = new NodeSet();
        $this->element = $element;
        $this->observers = new SplObjectStorage();
    }

    /**
     * Changes the value of an attribute.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-change
     *
     * @param \Rowbot\DOM\Attr $attribute The attribute whose value is to be changed.
     * @param string           $value     The attribute's new value.
     */
    public function change(Attr $attribute, string $value): void
    {
        // TODO: Queue a mutation record of "attributes" for element with name
        // attribute’s local name, namespace attribute’s namespace, and
        // oldValue attribute’s value.

        foreach ($this->observers as $observer) {
            $observer->onAttributeChanged(
                $this->element,
                $attribute->getLocalName(),
                $attribute->getValue(),
                $value,
                $attribute->getNamespace()
            );
        }

        $attribute->setValue($value);
    }

    /**
     * Appends an attribute to the list of attributes.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-append
     */
    public function append(Attr $attribute): void
    {
        // TODO: Queue a mutation record of "attributes" for element with name
        // attribute’s local name, namespace attribute’s namespace, and
        // oldValue null.

        foreach ($this->observers as $observer) {
            $observer->onAttributeChanged(
                $this->element,
                $attribute->getLocalName(),
                null,
                $attribute->getValue(),
                $attribute->getNamespace()
            );
        }

        $this->list->append($attribute);
        $attribute->setOwnerElement($this->element);
    }

    /**
     * Removes an attribute from the list.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-remove
     */
    public function remove(Attr $attribute): void
    {
        // TODO: Queue a mutation record of "attributes" for element with name
        // attribute’s local name, namespace attribute’s namespace, and
        // oldValue attribute’s value.

        foreach ($this->observers as $observer) {
            $observer->onAttributeChanged(
                $this->element,
                $attribute->getLocalName(),
                $attribute->getValue(),
                null,
                $attribute->getNamespace()
            );
        }

        $this->list->remove($attribute);
        $attribute->setOwnerElement(null);
    }

    /**
     * Replaces and attribute with another attribute.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-replace
     */
    public function replace(Attr $oldAttr, Attr $newAttr): void
    {
        // TODO: Queue a mutation record of "attributes" for element with name
        // oldAttr’s local name, namespace oldAttr’s namespace, and oldValue
        // oldAttr’s value.

        foreach ($this->observers as $observer) {
            $observer->onAttributeChanged(
                $this->element,
                $oldAttr->getLocalName(),
                $oldAttr->getValue(),
                $newAttr->getValue(),
                $oldAttr->getNamespace()
            );
        }

        $this->list->replace($oldAttr, $newAttr);
        $oldAttr->setOwnerElement(null);
        $newAttr->setOwnerElement($this->element);
    }

    /**
     * Gets an attribute using a fully qualified name.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-get-by-name
     */
    public function getAttrByName(string $qualifiedName): ?Attr
    {
        if (
            $this->element->namespaceURI === Namespaces::HTML
            && $this->element->getNodeDocument() instanceof HTMLDocument
        ) {
            $qualifiedName = Utils::toASCIILowercase($qualifiedName);
        }

        foreach ($this->list as $attribute) {
            if ($attribute->getQualifiedName() === $qualifiedName) {
                return $attribute;
            }
        }

        return null;
    }

    /**
     * Gets an attribute using a namespace and local name.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-get-by-namespace
     */
    public function getAttrByNamespaceAndLocalName(?string $namespace, string $localName): ?Attr
    {
        if ($namespace === '') {
            $namespace = null;
        }

        foreach ($this->list as $attribute) {
            if (
                $attribute->getNamespace() === $namespace
                && $attribute->getLocalName() === $localName
            ) {
                return $attribute;
            }
        }

        return null;
    }

    /**
     * Gets an attribute's value.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-get-value
     */
    public function getAttrValue(string $localName, ?string $namespace = null): string
    {
        $attr = $this->getAttrByNamespaceAndLocalName($namespace, $localName);

        if ($attr === null) {
            return '';
        }

        return $attr->getValue();
    }

    /**
     * Sets an attribute on an element.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-set
     *
     * @throws \Rowbot\DOM\Exception\InUseAttributeError If the attribute's owning element is not null and not an
     *                                                   element.
     */
    public function setAttr(Attr $attr): ?Attr
    {
        $owner = $attr->getOwnerElement();

        if ($owner !== null && $owner !== $this->element) {
            throw new InUseAttributeError();
        }

        $oldAttr = $this->getAttrByNamespaceAndLocalName($attr->namespaceURI, $attr->localName);

        if ($oldAttr === $attr) {
            return $attr;
        }

        if ($oldAttr !== null) {
            $this->replace($oldAttr, $attr);

            return $oldAttr;
        }

        $this->append($attr);

        return $oldAttr;
    }

    /**
     * Sets the attributes value.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-set-value
     */
    public function setAttrValue(
        string $localName,
        string $value,
        ?string $prefix = null,
        ?string $namespace = null
    ): void {
        $attribute = $this->getAttrByNamespaceAndLocalName(
            $namespace,
            $localName
        );

        if ($attribute === null) {
            $attribute = new Attr(
                $this->element->getNodeDocument(),
                $localName,
                $value,
                $namespace,
                $prefix
            );
            $this->append($attribute);

            return;
        }

        $this->change($attribute, $value);
    }

    /**
     * Removes an attribute from the list with the specified fully qualified
     * name.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-remove-by-name
     */
    public function removeAttrByName(string $qualifiedName): ?Attr
    {
        $attr = $this->getAttrByName($qualifiedName);

        if ($attr !== null) {
            $this->remove($attr);
        }

        return $attr;
    }

    /**
     * Remove an attribute from the list using a namespace and local name.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-remove-by-namespace
     */
    public function removeAttrByNamespaceAndLocalName(?string $namespace, string $localName): ?Attr
    {
        $attr = $this->getAttrByNamespaceAndLocalName($namespace, $localName);

        if ($attr !== null) {
            $this->remove($attr);
        }

        return $attr;
    }

    public function observe(AttributeChangeObserver $observer): void
    {
        $this->observers->attach($observer);
    }

    public function unobserve(AttributeChangeObserver $observer): void
    {
        $this->observers->detach($observer);
    }

    public function contains(Attr $attr): bool
    {
        return $this->list->contains($attr);
    }

    public function isEmpty(): bool
    {
        return $this->list->isEmpty();
    }

    /**
     * @param int $offset
     */
    public function offsetExists($offset): bool
    {
        return $this->list->offsetExists($offset);
    }

    /**
     * @param int $offset
     */
    public function offsetGet($offset): ?Attr
    {
        return $this->list->offsetGet($offset);
    }

    /**
     * @param int              $offset
     * @param \Rowbot\DOM\Attr $value
     */
    public function offsetSet($offset, $value): void
    {
    }

    /**
     * @param int $offset
     */
    public function offsetUnset($offset): void
    {
    }

    public function count(): int
    {
        return $this->list->count();
    }

    public function current(): Attr
    {
        return $this->list->current();
    }

    public function key(): int
    {
        return $this->list->key();
    }

    public function next(): void
    {
        $this->list->next();
    }

    public function rewind(): void
    {
        $this->list->rewind();
    }

    public function valid(): bool
    {
        return $this->list->valid();
    }
}
