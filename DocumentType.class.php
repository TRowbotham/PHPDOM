<?php
namespace phpjs;

/**
 * @see https://dom.spec.whatwg.org/#documenttype
 * @see https://developer.mozilla.org/en-US/docs/Web/API/DocumentType
 */
class DocumentType extends Node
{
    use ChildNode;

    private $mName;
    private $mPublicId;
    private $mSystemId;

    public function __construct($aName, $aPublicId = '', $aSystemId = '')
    {
        parent::__construct();

        $this->mName = $aName;
        $this->mNodeType = self::DOCUMENT_TYPE_NODE;
        $this->mPublicId = $aPublicId;
        $this->mSystemId = $aSystemId;
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'name':
                return $this->mName;
            case 'publicId':
                return $this->mPublicId;
            case 'systemId':
                return $this->mSystemId;
            default:
                return parent::__get($aName);
        }
    }

    public function toHTML()
    {
        $html = '<!DOCTYPE';
        $html .= ($this->mName ? ' ' . $this->mName  : '');
        $html .= ($this->mPublicId ? ' ' . $this->mPublicId : '');
        $html .= ($this->mSystemId ? ' ' . $this->mSystemId : '');
        $html .= '>';

        return $html;
    }

    /**
     * Returns the Node's length.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-length
     *
     * @return int
     */
    public function _getNodeLength()
    {
        return 0;
    }

    /**
     * @see Node::getNodeName
     */
    protected function getNodeName()
    {
        return $this->mName;
    }
}
