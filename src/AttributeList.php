<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use ArrayAccess;
use Countable;
use Generator;
use IteratorAggregate;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\InUseAttributeError;
use Rowbot\DOM\InternalEvent\AttributeChangedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function array_search;
use function array_splice;
use function count;
use function explode;

/**
 * @implements \ArrayAccess<int, \Rowbot\DOM\Attr>
 * @implements \IteratorAggregate<int, \Rowbot\DOM\Attr>
 */
class AttributeList implements ArrayAccess, Countable, IteratorAggregate
{
    private EventDispatcherInterface $dispatcher;

    private Element $element;

    /**
     * @var list<\Rowbot\DOM\Attr>
     */
    private array $list;

    /**
     * @var array<string, array<string, \Rowbot\DOM\Attr>>
     */
    private array $cache;

    public function __construct(Element $element, EventDispatcherInterface $dispatcher)
    {
        $this->list = [];
        $this->dispatcher = $dispatcher;
        $this->element = $element;
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
        // 1. Let oldValue be attribute’s value.
        $oldValue = $attribute->value;

        // 2. Set attribute’s value to value.
        $attribute->setValue($value);

        // 3. Handle attribute changes for attribute with attribute’s element, oldValue, and value.
        $this->handleAttributeChanges($attribute, $attribute->ownerElement, $oldValue, $value);
    }

    /**
     * Appends an attribute to the list of attributes.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-append
     */
    public function append(Attr $attribute): void
    {
        // 1. Append attribute to element’s attribute list.
        $this->list[] = $attribute;
        $this->cache[$attribute->namespaceURI][$attribute->localName] = $attribute;

        // 2. Set attribute’s element to element.
        $attribute->setOwnerElement($this->element);

        // 3. Handle attribute changes for attribute with element, null, and attribute’s value.
        $this->handleAttributeChanges($attribute, $this->element, null, $attribute->getValue());
    }

    /**
     * Removes an attribute from the list.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-remove
     */
    public function remove(Attr $attribute): void
    {
        // 1. Let element be attribute’s element.
        $element = $attribute->getOwnerElement();

        // 2. Remove attribute from element’s attribute list.
        if (!isset($this->cache[$attribute->namespaceURI][$attribute->localName])) {
            return;
        }

        unset($this->cache[$attribute->namespaceURI][$attribute->localName]);
        $index = array_search($attribute, $this->list, true);

        if ($index === false) {
            return;
        }

        array_splice($this->list, $index, 1);

        // 3. Set attribute’s element to null.
        $attribute->setOwnerElement(null);

        // 4. Handle attribute changes for attribute with element, attribute’s value, and null.
        $this->handleAttributeChanges($attribute, $element, $attribute->value, null);
    }

    /**
     * Replaces and attribute with another attribute.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-replace
     */
    public function replace(Attr $oldAttr, Attr $newAttr): void
    {
        // 1. Replace oldAttr by newAttr in oldAttr’s element’s attribute list.
        $index = array_search($oldAttr, $this->list, true);

        if ($index === false) {
            return;
        }

        $this->list[$index] = $newAttr;
        unset($this->cache[$oldAttr->namespaceURI][$oldAttr->localName]);
        $this->cache[$newAttr->namespaceURI][$newAttr->localName] = $newAttr;

        // 2. Set newAttr’s element to oldAttr’s element.
        $newAttr->setOwnerElement($oldAttr->getOwnerElement());

        // 3. Set oldAttr’s element to null.
        $oldAttr->setOwnerElement(null);

        // 4. Handle attribute changes for oldAttr with newAttr’s element, oldAttr’s value, and newAttr’s value.
        $this->handleAttributeChanges(
            $oldAttr,
            $newAttr->getOwnerElement(),
            $oldAttr->getValue(),
            $newAttr->getValue()
        );
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

    /**
     * @see https://dom.spec.whatwg.org/#handle-attribute-changes
     */
    private function handleAttributeChanges(
        Attr $attribute,
        Element $element,
        ?string $oldValue,
        ?string $newValue
    ): void {
        // 1. Queue a mutation record of "attributes" for element with attribute’s local name, attribute’s namespace,
        // oldValue, « », « », null, and null.

        // 2. If element is custom, then enqueue a custom element callback reaction with element, callback name
        // "attributeChangedCallback", and an argument list containing attribute’s local name, oldValue, newValue, and
        // attribute’s namespace.

        // 3. Run the attribute change steps with element, attribute’s local name, oldValue, newValue, and attribute’s
        // namespace.
        $event = new AttributeChangedEvent(
            $element,
            $attribute->localName,
            $oldValue,
            $newValue,
            $attribute->namespaceURI
        );
        $this->dispatcher->dispatch($event, 'attribute.changed');
    }
}
