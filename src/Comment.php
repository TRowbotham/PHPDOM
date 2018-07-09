<?php
namespace Rowbot\DOM;

// https://developer.mozilla.org/en-US/docs/Web/API/Comment
// https://dom.spec.whatwg.org/#comment
class Comment extends CharacterData
{
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
     * Gets the name of the node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodename
     * @see Node::getNodeName()
     *
     * @return string Returns the string "#comment".
     */
    protected function getNodeName(): string
    {
        return '#comment';
    }
}
