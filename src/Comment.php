<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#comment
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Comment
 */
class Comment extends CharacterData
{
    public function __construct(Document $document, string $data = '')
    {
        parent::__construct($document, $data);

        $this->nodeType = Node::COMMENT_NODE;
    }

    public function isEqualNode(?Node $otherNode): bool
    {
        return $otherNode !== null
            && $otherNode->nodeType === $this->nodeType
            && $otherNode instanceof self
            && $otherNode->data === $this->data
            && $this->hasEqualChildNodes($otherNode);
    }

    protected function getNodeName(): string
    {
        return '#comment';
    }
}
