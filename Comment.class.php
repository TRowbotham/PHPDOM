<?php
namespace phpjs;

// https://developer.mozilla.org/en-US/docs/Web/API/Comment
// https://dom.spec.whatwg.org/#comment
class Comment extends CharacterData {
    public function __construct($aData = '') {
        parent::__construct(Utils::DOMString($aData));

        $this->mNodeType = Node::COMMENT_NODE;
    }

    public function toHTML() {
        return '<!-- ' . $this->mData . ' -->';
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
    protected function getNodeName()
    {
        return '#comment';
    }
}
