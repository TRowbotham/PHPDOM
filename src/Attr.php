<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;

/**
 * Represents a content attribute on an Element.
 *
 * @see https://dom.spec.whatwg.org/#attr
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Attr
 */
class Attr extends Node
{
    public readonly ?string $namespaceURI;

    public readonly ?string $prefix;

    public readonly string $localName;

    /**
     * @see https://dom.spec.whatwg.org/#dom-attr-name
     */
    public string $name {
        get => $this->getQualifiedName();
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-attr-value
     */
    public string $value {
        get => $this->_value;
        set(float|int|string $value) {
            $this->setExistingAttributeValue((string) $value);
        }
    }

    public ?Element $ownerElement {
        get => $this->_ownerElement;
    }

    public string $nodeName {
        get => $this->getQualifiedName();
    }

    public ?string $nodeValue {
        get => $this->value;
        set(float|int|string|null $value) {
            if ($value === null) {
                $value = '';
            }

            $this->setExistingAttributeValue((string) $value);
        }
    }

    public ?string $textContent {
        get => $this->value;
        set(float|int|string|null $value) {
            if ($value === null) {
                $value = '';
            }

            $this->setExistingAttributeValue((string) $value);
        }
    }

    private string $_value;

    private ?Element $_ownerElement;

    public function __construct(
        Document $document,
        string $localName,
        string $value,
        ?string $namespace = null,
        ?string $prefix = null
    ) {
        parent::__construct($document, self::ATTRIBUTE_NODE);

        $this->localName = $localName;
        $this->namespaceURI = $namespace;
        $this->_ownerElement = null;
        $this->prefix = $prefix;
        $this->_value = $value;
    }

    public function isEqualNode(?Node $otherNode): bool
    {
        return $otherNode !== null
            && $otherNode->nodeType === $this->nodeType
            && $otherNode instanceof self
            && $otherNode->namespaceURI === $this->namespaceURI
            && $otherNode->localName === $this->localName
            && $otherNode->_value === $this->_value
            && $this->hasEqualChildNodes($otherNode);
    }

    /**
     * Set the attribute's owning element.
     *
     * @internal
     */
    public function setOwnerElement(?Element $element): void
    {
        $this->_ownerElement = $element;
    }

    /**
     * Returns the attribute's value.
     *
     * @internal
     */
    public function getValue(): string
    {
        return $this->_value;
    }

    /**
     * Sets the attribute's value without running the change algorithm when an
     * owning element is present.
     *
     * @internal
     */
    public function setValue(string $value): void
    {
        $this->_value = $value;
    }

    public function getLength(): int
    {
        // Attr nodes cannot contain children, so just return 0.
        return 0;
    }

    /**
     * Sets the value of an existing attribute.
     *
     * @see https://dom.spec.whatwg.org/#set-an-existing-attribute-value
     */
    protected function setExistingAttributeValue(string $value): void
    {
        if (!$this->_ownerElement) {
            $this->_value = $value;

            return;
        }

        $this->_ownerElement->getAttributeList()->change($this, $value);
    }

    protected function getQualifiedName(): string
    {
        if ($this->prefix === null) {
            return $this->localName;
        }

        return $this->prefix . ':' . $this->localName;
    }

    protected function __clone()
    {
        parent::__clone();
        $this->_ownerElement = null;
    }
}
