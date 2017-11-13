<?php
namespace Rowbot\DOM;

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

    /**
     * Returns the Node's length, which is 0, as a DocumentType cannot have
     * children.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-length
     * @see Node::getLength()
     *
     * @return int
     */
    public function getLength()
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
        return null;
    }

    /**
     * @see Node::setNodeValue
     */
    protected function setNodeValue($aNewValue)
    {
        // Do nothing.
    }

    /**
     * @see Node::setTextContent
     */
    protected function setTextContent($aNewValue)
    {
        // Do nothing.
    }
}
