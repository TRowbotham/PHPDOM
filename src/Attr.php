<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;

/**
 * Represents a content attribute on an Element.
 *
 * @see https://dom.spec.whatwg.org/#attr
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Attr
 *
 * @property-read string                           $localName    The attribute's local name.
 * @property-read string                           $name         The attribute's fully qualified name, usually inthe
 *                                                               form  of prefix:localName or localName if the attribute
 *                                                               does not have a namespace.
 * @property-read string|null                      $namespaceURI The attribute's namespace or null if it does not have a
 *                                                               namespace.
 * @property-read \Rowbot\DOM\Element\Element|null $ownerElement The Element to which this attribute belongs to, or null
 *                                                               if it is not owned by an Element.
 * @property-read string|null                      $prefix       The attribute's namespace prefix or null if it does not
 *                                                               have a namespace.
 * @property-read string                           $value        The value of the attribute.
 */
class Attr extends Node
{
    /**
     * @var string
     */
    private $localName;

    /**
     * @var string|null
     */
    private $namespaceURI;

    /**
     * @var \Rowbot\DOM\Element\Element|null
     */
    private $ownerElement;

    /**
     * @var string|null
     */
    private $prefix;

    /**
     * @var string
     */
    private $value;

    public function __construct(
        Document $document,
        string $localName,
        string $value,
        ?string $namespace = null,
        ?string $prefix = null
    ) {
        parent::__construct($document);

        $this->localName = $localName;
        $this->nodeType = self::ATTRIBUTE_NODE;
        $this->namespaceURI = $namespace;
        $this->ownerElement = null;
        $this->prefix = $prefix;
        $this->value = $value;
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'localName':
                return $this->localName;

            case 'name':
                if ($this->prefix) {
                    return $this->prefix . ':' . $this->localName;
                }

                return $this->localName;

            case 'namespaceURI':
                return $this->namespaceURI;

            case 'ownerElement':
                return $this->ownerElement;

            case 'prefix':
                return $this->prefix;

            case 'value':
                return $this->value;

            default:
                return parent::__get($name);
        }
    }

    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'value':
                $this->setExistingAttributeValue((string) $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }

    public function isEqualNode(?Node $otherNode): bool
    {
        return $otherNode !== null
            && $otherNode->nodeType === $this->nodeType
            && $otherNode instanceof self
            && $otherNode->namespaceURI === $this->namespaceURI
            && $otherNode->localName === $this->localName
            && $otherNode->value === $this->value
            && $this->hasEqualChildNodes($otherNode);
    }

    /**
     * Returns the attribute's namespace.
     *
     * @internal
     */
    public function getNamespace(): ?string
    {
        return $this->namespaceURI;
    }

    /**
     * Returns the attribute's local name.
     *
     * @internal
     */
    public function getLocalName(): string
    {
        return $this->localName;
    }

    /**
     * Returns the attribute's qualified name.
     *
     * @internal
     */
    public function getQualifiedName(): string
    {
        if ($this->prefix === null) {
            return $this->localName;
        }

        return $this->prefix . ':' . $this->localName;
    }

    /**
     * Returns the attribute's owner element.
     */
    public function getOwnerElement(): ?Element
    {
        return $this->ownerElement;
    }

    /**
     * Set the attribute's owning element.
     *
     * @internal
     */
    public function setOwnerElement(?Element $element): void
    {
        $this->ownerElement = $element;
    }

    /**
     * Returns the attribute's value.
     *
     * @internal
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Sets the attribute's value without running the change algorithm when an
     * owning element is present.
     *
     * @internal
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * Sets the value of an existing attribute.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#set-an-existing-attribute-value
     */
    protected function setExistingAttributeValue(string $value): void
    {
        if (!$this->ownerElement) {
            $this->value = $value;

            return;
        }

        $this->ownerElement->getAttributeList()->change($this, $value);
    }

    protected function getNodeName(): string
    {
        if ($this->prefix) {
            return $this->prefix . ':' . $this->localName;
        }

        return $this->localName;
    }

    public function getLength(): int
    {
        // Attr nodes cannot contain children, so just return 0.
        return 0;
    }

    protected function getNodeValue(): string
    {
        return $this->value;
    }

    protected function setNodeValue(?string $value): void
    {
        if ($value === null) {
            $value = '';
        }

        $this->setExistingAttributeValue($value);
    }

    protected function getTextContent(): string
    {
        return $this->value;
    }

    protected function setTextContent(?string $value): void
    {
        if ($value === null) {
            $value = '';
        }

        $this->setExistingAttributeValue($value);
    }

    protected function __clone()
    {
        parent::__clone();
        $this->ownerElement = null;
    }
}
