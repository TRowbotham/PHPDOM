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
     * @see Node::getNodeName
     */
    protected function getNodeName()
    {
        return '#comment';
    }

    /**
     * @see Node::getNodeValue
     */
    protected function getNodeValue()
    {
        return $this->mData;
    }

    /**
     * @see Node::getTextContent
     */
    protected function getTextContent()
    {
        return $this->mData;
    }

    /**
     * @see Node::setNodeValue
     */
    protected function setNodeValue($aNewValue)
    {
        $this->doReplaceData(
            0,
            $this->mLength,
            Utils::DOMString($aNewValue, true)
        );
    }

    /**
     * @see Node::setTextContent
     */
    protected function setTextContent($aNewValue)
    {
        $this->doReplaceData(
            0,
            $this->mLength,
            Utils::DOMString($aNewValue, true)
        );
    }
}
