<?php
namespace phpjs;

class ProcessingInstruction extends CharacterData
{
    protected $mTarget;

    public function __construct($aTarget, $aData)
    {
        parent::__construct($aData);

        $this->mNodeType = Node::PROCESSING_INSTRUCTION_NODE;
        $this->mTarget = $aTarget;
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'target':
                return $this->mTarget;

            default:
                return parent::__get($aName);
        }
    }

    /**
     * @see Node::getNodeName
     */
    protected function getNodeName()
    {
        return $this->mTarget;
    }

    /**
     * @see Node::getNodeValue
     */
    protected function getNodeValue()
    {
        return null;
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
        $this->replaceData(0, $this->mLength, $aNewValue);
    }

    /**
     * @see Node::setTextContent
     */
    protected function setTextContent($aNewValue)
    {
        $this->replaceData(0, $this->mLength, $aNewValue);
    }
}
