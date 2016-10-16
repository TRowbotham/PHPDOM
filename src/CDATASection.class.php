<?php
namespace phpjs;

class CDATASection extends Text
{
    public function __construct($aData)
    {
        parent::__construct($aData);

        $this->mNodeType = self::CDATA_SECTION_NODE;
    }

    /**
     * Gets the name of the node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodename
     * @see Node::getNodeName()
     *
     * @return string Returns the string "#cdata-section".
     */
    protected function getNodeName()
    {
        return '#cdata-section';
    }
}
