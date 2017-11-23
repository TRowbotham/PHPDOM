<?php
namespace Rowbot\DOM;

class CDATASection extends Text
{
    public function __construct($data)
    {
        parent::__construct($data);

        $this->nodeType = self::CDATA_SECTION_NODE;
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
