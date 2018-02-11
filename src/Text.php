<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Exception\IndexSizeError;

/**
 * Represents the text content of a Node.
 *
 * @see https://dom.spec.whatwg.org/#text
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Text
 *
 * @property-read string $wholeText Returns the concatenated string data of all
 *     contingious Text nodes relative to this Node in tree order.
 */
class Text extends CharacterData
{
    public function __construct($data = '')
    {
        parent::__construct(Utils::DOMString($data));

        $this->nodeType = Node::TEXT_NODE;
    }

    public function __get($name)
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
    ) {
        $document = $document ?: $this->getNodeDocument();
        $copy = new static();
        $copy->data = $this->data;
        $this->postCloneNode($copy, $document, $cloneChildren);

        return $copy;
    }

    public function splitText($offset)
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
     * Gets the name of the node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodename
     * @see Node::getNodeName()
     *
     * @return string Returns the string "#text".
     */
    protected function getNodeName()
    {
        return '#text';
    }
}
