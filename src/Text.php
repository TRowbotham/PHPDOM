<?php

declare(strict_types=1);

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
    public function __construct(Document $document, string $data = '')
    {
        parent::__construct($document, $data);

        $this->nodeType = Node::TEXT_NODE;
    }

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

    public function cloneNodeInternal(Document $document = null, bool $cloneChildren = false): Node
    {
        $document = $document ?? $this->getNodeDocument();
        $copy = new static($document, $this->data);
        $this->postCloneNode($copy, $document, $cloneChildren);

        return $copy;
    }

    /**
     * Splits the text at the given offset.
     *
     * @see https://dom.spec.whatwg.org/#dom-text-splittext
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
        $newNode = new Text($this->nodeDocument, $newData);
        $ranges = Range::getRangeCollection();

        if ($this->parentNode) {
            $this->parentNode->insertNode($newNode, $this->nextSibling);
            $treeIndex = $this->getTreeIndex();

            foreach ($ranges as $range) {
                $startOffset = $range->startOffset;

                if ($range->startContainer === $this && $startOffset > $offset) {
                    $range->setStartInternal($newNode, $startOffset - $offset);
                }
            }

            foreach ($ranges as $range) {
                $endOffset = $range->endOffset;

                if ($range->endContainer === $this && $endOffset > $offset) {
                    $range->setEndInternal($newNode, $endOffset - $offset);
                }
            }

            foreach ($ranges as $range) {
                $startContainer = $range->startContainer;
                $startOffset = $range->startOffset;

                if ($startContainer === $this->parentNode && $startOffset === $treeIndex + 1) {
                    $range->setStartInternal($startContainer, $startOffset + 1);
                }
            }

            foreach ($ranges as $range) {
                $endContainer = $range->endContainer;
                $endOffset = $range->endOffset;

                if ($endContainer === $this->parentNode && $endOffset === $treeIndex + 1) {
                    $range->setEndInternal($endContainer, $endOffset + 1);
                }
            }
        }

        $this->doReplaceData($offset, $count, '');

        return $newNode;
    }

    protected function getNodeName(): string
    {
        return '#text';
    }
}
