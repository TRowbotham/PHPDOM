<?php
namespace phpjs;

// https://developer.mozilla.org/en-US/docs/Web/API/Comment
// https://dom.spec.whatwg.org/#comment
class Comment extends CharacterData {
    public function __construct($aData = '') {
        parent::__construct($aData);

        $this->mNodeType = Node::COMMENT_NODE;
    }

    public function toHTML() {
        return '<!-- ' . $this->mData . ' -->';
    }

    /**
     * @see Node::getNodeName
     */
    protected function getNodeName()
    {
        return '#comment';
    }
}
