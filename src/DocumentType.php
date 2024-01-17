<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#documenttype
 * @see https://developer.mozilla.org/en-US/docs/Web/API/DocumentType
 */
class DocumentType extends Node implements ChildNode
{
    use ChildNodeTrait;

    public readonly string $name;

    public readonly string $publicId;

    public readonly string $systemId;

    public function __construct(
        Document $document,
        string $name,
        string $publicId = '',
        string $systemId = ''
    ) {
        parent::__construct($document, self::DOCUMENT_TYPE_NODE);

        $this->name = $name;
        $this->publicId = $publicId;
        $this->systemId = $systemId;
    }

    public function isEqualNode(?Node $otherNode): bool
    {
        return $otherNode !== null
            && $otherNode->nodeType === $this->nodeType
            && $otherNode instanceof self
            && $otherNode->name === $this->name
            && $otherNode->publicId === $this->publicId
            && $otherNode->systemId === $this->systemId
            && $this->hasEqualChildNodes($otherNode);
    }

    public function getLength(): int
    {
        // Return 0 since a DocumentType cannot have any children.
        return 0;
    }

    protected function getNodeName(): string
    {
        return $this->name;
    }

    protected function getNodeValue(): ?string
    {
        return null;
    }

    protected function getTextContent(): ?string
    {
        return null;
    }

    protected function setNodeValue(?string $value): void
    {
        // Do nothing.
    }

    protected function setTextContent(?string $value): void
    {
        // Do nothing.
    }
}
