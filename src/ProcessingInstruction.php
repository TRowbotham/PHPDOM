<?php
namespace Rowbot\DOM;

class ProcessingInstruction extends CharacterData
{
    protected $target;

    public function __construct($target, $data)
    {
        parent::__construct($data);

        $this->nodeType = Node::PROCESSING_INSTRUCTION_NODE;
        $this->target = $target;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'target':
                return $this->target;

            default:
                return parent::__get($name);
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
        return $this->target;
    }
}
