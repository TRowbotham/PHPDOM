<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#comment
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Comment
 */
class Comment extends CharacterData
{
    public function __construct(string $data = '')
    {
        parent::__construct($data);

        $this->nodeType = Node::COMMENT_NODE;
    }

    public function cloneNodeInternal(Document $document = null, bool $cloneChildren = false): Node
    {
        $document = $document ?: $this->getNodeDocument();
        $copy = new static();
        $copy->data = $this->data;
        $this->postCloneNode($copy, $document, $cloneChildren);

        return $copy;
    }

    protected function getNodeName(): string
    {
        return '#comment';
    }
}
