<?php
namespace Rowbot\DOM;

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
     * Gets the name of the node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodename
     * @see Node::getNodeName()
     *
     * @return string Returns the node's target.
     */
    protected function getNodeName()
    {
        return $this->mTarget;
    }
}
