<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\DynamicProperty\Getter;
use Rowbot\DOM\DynamicProperty\Setter;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\TypeError;

/**
 * Represents a content attribute on an Element.
 *
 * @see https://dom.spec.whatwg.org/#attr
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Attr
 *
 * @property-read \Rowbot\DOM\Element\Element|null $ownerElement The Element to which this attribute belongs to, or null
 *                                                               if it is not owned by an Element.
 * @property-read string                           $value        The value of the attribute.
 */
class Attr extends Node
{
    public readonly ?string $namespaceURI;

    public readonly ?string $prefix;

    public readonly string $localName;

    #[Getter('value')]
    private string $value;

    #[Getter('ownerElement')]
    private ?Element $ownerElement;

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
        $this->ownerElement = null;
        $this->prefix = $prefix;
        $this->value = $value;
        $this->name = match ($this->prefix) {
            null    => $this->localName,
            default => $this->prefix . ':' . $this->localName,
        };
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
    #[Setter('value')]
    protected function setExistingAttributeValue(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        if (!$this->ownerElement) {
            $this->value = $value;

            return;
        }

        $this->ownerElement->getAttributeList()->change($this, (string) $value);
    }

    #[Getter('name')]
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

    protected function setNodeValue(mixed $value): void
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

    protected function setTextContent(mixed $value): void
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
