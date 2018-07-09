<?php
namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#comment
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Comment
 */
class Comment extends CharacterData
{
    /**
     * Constructor.
     *
     * @param string $data (optional)
     *
     * @return void
     */
    public function __construct($data = '')
    {
        parent::__construct(Utils::DOMString($data));

        $this->nodeType = Node::COMMENT_NODE;
    }

    /**
     * {@inheritDoc}
     */
    public function cloneNodeInternal(
        Document $document = null,
        bool $cloneChildren = false
    ): Node {
        $document = $document ?: $this->getNodeDocument();
        $copy = new static();
        $copy->data = $this->data;
        $this->postCloneNode($copy, $document, $cloneChildren);

        return $copy;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNodeName(): string
    {
        return '#comment';
    }
}
