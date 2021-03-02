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

    protected function getNodeName(): string
    {
        return $this->target;
    }
}
