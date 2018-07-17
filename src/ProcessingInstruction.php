<?php
namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#interface-processinginstruction
 *
 * @property-read string $target
 */
class ProcessingInstruction extends CharacterData
{
    /**
     * @var string
     */
    protected $target;

    /**
     * Constructor.
     *
     * @param string $target
     * @param string $data
     *
     * @return void
     */
    public function __construct(string $target, string $data)
    {
        parent::__construct($data);

        $this->nodeType = Node::PROCESSING_INSTRUCTION_NODE;
        $this->target = $target;
    }

    /**
     * {@inheritDoc}
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'target':
                return $this->target;

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
        $copy = new static($this->target, $this->data);
        $copy->data = $this->data;
        $copy->target = $this->target;
        $this->postCloneNode($copy, $document, $cloneChildren);

        return $copy;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNodeName(): string
    {
        return $this->target;
    }
}
