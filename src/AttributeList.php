<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use ArrayAccess;
use Countable;
use Generator;
use IteratorAggregate;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\InUseAttributeError;

use function array_search;
use function array_splice;
use function count;
use function explode;
use function spl_object_id;

/**
 * @implements \ArrayAccess<int, \Rowbot\DOM\Attr>
 * @implements \IteratorAggregate<int, \Rowbot\DOM\Attr>
 */
class AttributeList implements ArrayAccess, Countable, IteratorAggregate
{
    private Element $element;

    /**
     * @var array<int, \Rowbot\DOM\AttributeChangeObserver>
     */
    private array $observers;

    /**
     * @var list<\Rowbot\DOM\Attr>
     */
    private array $list;

    /**
     * @var array<string, array<string, \Rowbot\DOM\Attr>>
     */
    private array $cache;

    public function __construct(Element $element)
    {
        $this->list = [];
        $this->element = $element;
        $this->observers = [];
        $this->cache = [];
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

        $attrName = $attribute->getLocalName();
        $attrValue = $attribute->getValue();
        $attrNamespace = $attribute->getNamespace();

        foreach ($this->observers as $observer) {
            $observer->onAttributeChanged(
                $this->element,
                $attrName,
                $attrValue,
                $value,
                $attrNamespace
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

        $attrName = $attribute->getLocalName();
        $attrValue = $attribute->getValue();
        $attrNamespace = $attribute->getNamespace();

        foreach ($this->observers as $observer) {
            $observer->onAttributeChanged(
                $this->element,
                $attrName,
                null,
                $attrValue,
                $attrNamespace
            );
        }

        $this->list[] = $attribute;
        $this->cache[$attrNamespace][$attrName] = $attribute;
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

        $attrName = $attribute->getLocalName();
        $attrValue = $attribute->getValue();
        $attrNamespace = $attribute->getNamespace();

        foreach ($this->observers as $observer) {
            $observer->onAttributeChanged(
                $this->element,
                $attrName,
                $attrValue,
                null,
                $attrNamespace
            );
        }

        if (!isset($this->cache[$attrNamespace][$attrName])) {
            return;
        }

        $attribute->setOwnerElement(null);
        unset($this->cache[$attrNamespace][$attrName]);
        $index = array_search($attribute, $this->list, true);

        if ($index === false) {
            return;
        }

        array_splice($this->list, $index, 1);
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

        $oldAttrName = $oldAttr->getLocalName();
        $oldAttrValue = $oldAttr->getValue();
        $newAttrValue = $newAttr->getValue();
        $oldAttrNamespace = $oldAttr->getNamespace();

        foreach ($this->observers as $observer) {
            $observer->onAttributeChanged(
                $this->element,
                $oldAttrName,
                $oldAttrValue,
                $newAttrValue,
                $oldAttrNamespace
            );
        }

        $oldAttr->setOwnerElement(null);
        $newAttr->setOwnerElement($this->element);

        $index = array_search($oldAttr, $this->list, true);

        if ($index === false) {
            return;
        }

        $this->list[$index] = $newAttr;
        unset($this->cache[$oldAttrNamespace][$oldAttrName]);
        $this->cache[$newAttr->getNamespace()][$newAttr->getLocalName()] = $newAttr;
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
            && $this->element->getNodeDocument()->isHTMLDocument()
        ) {
            $qualifiedName = Utils::toASCIILowercase($qualifiedName);
        }

        foreach ($this->cache as $names) {
            if (isset($names[$qualifiedName])) {
                return $names[$qualifiedName];
            }
        }

        $parts = explode(':', $qualifiedName, 2);
        $prefix = false;
        $localName = $parts[0];

        if (isset($parts[1])) {
            $localName = $parts[1];
            $prefix = $parts[0];
        }

        if ($prefix === false) {
            return null;
        }

        foreach ($this->cache as $names) {
            if (isset($names[$localName]) && $names[$localName]->prefix === $prefix) {
                return $names[$localName];
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

        return $this->cache[$namespace][$localName] ?? null;
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
        $this->observers[spl_object_id($observer)] = $observer;
    }

    public function unobserve(AttributeChangeObserver $observer): void
    {
        unset($this->observers[spl_object_id($observer)]);
    }

    public function contains(Attr $attr): bool
    {
        $namespace = $attr->getNamespace();
        $localName = $attr->getLocalName();

        return isset($this->cache[$namespace][$localName]) && $this->cache[$namespace][$localName] === $attr;
    }

    public function isEmpty(): bool
    {
        return $this->list === [];
    }

    /**
     * @param int $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->list[$offset]);
    }

    /**
     * @param int $offset
     */
    public function offsetGet($offset): ?Attr
    {
        return $this->list[$offset] ?? null;
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
        return count($this->list);
    }

    /**
     * @return \Generator<int, \Rowbot\DOM\Attr>
     */
    public function getIterator(): Generator
    {
        foreach ($this->list as $attr) {
            yield $attr;
        }
    }
}
