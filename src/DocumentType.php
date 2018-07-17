<?php
namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#documenttype
 * @see https://developer.mozilla.org/en-US/docs/Web/API/DocumentType
 *
 * @property-read string $name
 * @property-read string $publicId
 * @property-read string $systemId
 */
class DocumentType extends Node
{
    use ChildNode;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $publicId;

    /**
     * @var string
     */
    private $systemId;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $publicId
     * @param string $systemId
     *
     * @return void
     */
    public function __construct(
        string $name,
        string $publicId = '',
        string $systemId = ''
    ) {
        parent::__construct();

        $this->name = $name;
        $this->nodeType = self::DOCUMENT_TYPE_NODE;
        $this->publicId = $publicId;
        $this->systemId = $systemId;
    }

    /**
     * {@inheritDoc}
     */
    public function __get(string $name)
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
     * {@inheritDoc}
     */
    public function getLength(): int
    {
        // Return 0 since a DocumentType cannot have any children.
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNodeName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNodeValue(): ?string
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    protected function getTextContent(): ?string
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    protected function setNodeValue(?string $value): void
    {
        // Do nothing.
    }

    /**
     * {@inheritDoc}
     */
    protected function setTextContent(?string $value): void
    {
        // Do nothing.
    }
}
