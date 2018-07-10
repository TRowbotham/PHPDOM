<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Exception\IndexSizeError;

/**
 * Represents the text content of a Node.
 *
 * @see https://dom.spec.whatwg.org/#text
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Text
 *
 * @property-read string $wholeText Returns the concatenated string data of all contingious Text nodes relative to this
 *                                  Node in tree order.
 */
class Text extends CharacterData
{
    /**
     * Constructor.
     *
     * @param string $data
     *
     * @return void
     */
    public function __construct($data = '')
    {
        parent::__construct(Utils::DOMString($data));

        $this->nodeType = Node::TEXT_NODE;
    }

    /**
     * {@inheritDoc}
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'wholeText':
                $wholeText = '';
                $startNode = $this;

                while ($startNode) {
                    if (!$startNode->previousSibling instanceof Text) {
                        break;
                    }

                    $startNode = $startNode->previousSibling;
                }

                while ($startNode instanceof Text) {
                    $wholeText .= $startNode->data;
                    $startNode = $startNode->nextSibling;
                }

                return $wholeText;

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
        $copy = new static();
        $copy->data = $this->data;
        $this->postCloneNode($copy, $document, $cloneChildren);

        return $copy;
    }

    /**
     * Splits the text at the given offset.
     *
     * @see https://dom.spec.whatwg.org/#dom-text-splittext
     *
     * @param int $offset
     *
     * @return self
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError
     */
    public function splitText(int $offset): self
    {
        $length = $this->length;

        if ($offset > $length) {
            throw new IndexSizeError();
        }

        $count = $length - $offset;
        $newData = $this->substringData($offset, $count);
        $newNode = new Text($newData);
        $newNode->nodeDocument = $this->nodeDocument;
        $ranges = Range::getRangeCollection();

        if ($this->parentNode) {
            $this->parentNode->insertNode($newNode, $this->nextSibling);
            $treeIndex = $this->getTreeIndex();

            foreach ($ranges as $index => $range) {
                $startOffset = $range->startOffset;

                if ($range->startContainer === $this
                    && $startOffset > $offset
                ) {
                    $range->setStart($newNode, $startOffset - $offset);
                }
            }

            foreach ($ranges as $index => $range) {
                $endOffset = $range->endOffset;

                if ($range->endContainer === $this && $endOffset > $offset) {
                    $range->setEnd($newNode, $endOffset - $offset);
                }
            }

            foreach ($ranges as $index => $range) {
                $startContainer = $range->startContainer;
                $startOffset = $range->startOffset;

                if ($startContainer === $this
                    && $startOffset == $treeIndex + 1
                ) {
                    $range->setStart($startContainer, $startOffset + 1);
                }
            }

            foreach ($ranges as $index => $range) {
                $endContainer = $range->endContainer;
                $endOffset = $range->endOffset;

                if ($endContainer === $this && $endOffset == $treeIndex + 1) {
                    $range->setEnd($endContainer, $endOffset + 1);
                }
            }
        }

        $this->doReplaceData($offset, $count, '');

        return $newNode;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNodeName(): string
    {
        return '#text';
    }
}
