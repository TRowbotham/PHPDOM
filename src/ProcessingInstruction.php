<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#interface-processinginstruction
 */
class ProcessingInstruction extends CharacterData
{
    public string $nodeName {
        get => $this->target;
    }

    public readonly string $target;

    public function __construct(Document $document, string $target, string $data)
    {
        parent::__construct($document, $data, self::PROCESSING_INSTRUCTION_NODE);

        $this->target = $target;
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
}
