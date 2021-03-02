<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#interface-processinginstruction
 *
 * @property-read string $target
 */
class ProcessingInstruction extends CharacterData
{
    /**
     * @var string
     */
    protected $target;

    public function __construct(Document $document, string $target, string $data)
    {
        parent::__construct($document, $data);

        $this->nodeType = Node::PROCESSING_INSTRUCTION_NODE;
        $this->target = $target;
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'target':
                return $this->target;

            default:
                return parent::__get($name);
        }
    }

    public function isEqualNode(?Node $otherNode): bool
    {
        return $otherNode !== null
            && $otherNode->nodeType === $this->nodeType
            && $otherNode instanceof self
            && $otherNode->target === $this->target
            && $otherNode->data === $this->data
            && $this->hasEqualChildNodes($otherNode);
    }

    public function cloneNodeInternal(Document $document = null, bool $cloneChildren = false): Node
    {
        $document = $document ?? $this->nodeDocument;
        $copy = new static($document, $this->target, $this->data);
        $copy->data = $this->data;
        $copy->target = $this->target;
        $this->postCloneNode($copy, $document, $cloneChildren);

        return $copy;
    }

    protected function getNodeName(): string
    {
        return $this->target;
    }
}
