<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#comment
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Comment
 */
class Comment extends CharacterData
{
    public string $nodeName {
        get => '#comment';
    }

    public function __construct(Document $document, string $data = '')
    {
        parent::__construct($document, $data, self::COMMENT_NODE);
    }

    public function isEqualNode(?Node $otherNode): bool
    {
        return $otherNode !== null
            && $otherNode->nodeType === $this->nodeType
            && $otherNode instanceof self
            && $otherNode->_data === $this->_data
            && $this->hasEqualChildNodes($otherNode);
    }
}
