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
        }
    }

    /**
     * @see Node::getNodeName
     */
    protected function getNodeName()
    {
        return $this->mTarget;
    }
}
