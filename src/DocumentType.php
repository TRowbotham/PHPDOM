<?php
namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#documenttype
 * @see https://developer.mozilla.org/en-US/docs/Web/API/DocumentType
 */
class DocumentType extends Node
{
    use ChildNode;

    private $name;
    private $publicId;
    private $systemId;

    public function __construct($name, $publicId = '', $systemId = '')
    {
        parent::__construct();

        $this->name = $name;
        $this->nodeType = self::DOCUMENT_TYPE_NODE;
        $this->publicId = $publicId;
        $this->systemId = $systemId;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'name':
                return $this->name;
            case 'publicId':
                return $this->publicId;
            case 'systemId':
                return $this->systemId;
            default:
                return parent::__get($name);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function cloneNodeInternal(
        Document $document = null,
        bool $cloneChildren = false
    ): Node {
        $document = $document ?: $this->getNodeDocument();
        $copy = new static($this->name);
        $copy->name = $this->name;
        $copy->publicId = $this->publicId;
        $copy->systemId = $this->systemId;
        $this->postCloneNode($copy, $document, $cloneChildren);

        return $copy;
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
    public function getLength(): int
    {
        return 0;
    }

    /**
     * @see Node::getNodeName
     */
    protected function getNodeName(): string
    {
        return $this->name;
    }

    /**
     * @see Node::getNodeValue
     */
    protected function getNodeValue(): ?string
    {
        return null;
    }

    /**
     * @see Node::getTextContent
     */
    protected function getTextContent(): ?string
    {
        return null;
    }

    /**
     * @see Node::setNodeValue
     */
    protected function setNodeValue($newValue): void
    {
        // Do nothing.
    }

    /**
     * @see Node::setTextContent
     */
    protected function setTextContent($newValue): void
    {
        // Do nothing.
    }
}
