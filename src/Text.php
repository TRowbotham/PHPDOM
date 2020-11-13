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
        $length = $this->getLength();

        if ($offset > $length) {
            throw new IndexSizeError();
        }

        $count = $length - $offset;
        $newData = $this->substringData($offset, $count);
        $newNode = new Text($this->nodeDocument, $newData);

        if ($this->parentNode) {
            $this->parentNode->insertNode($newNode, $this->nextSibling);
            $treeIndex = $this->getTreeIndex();
            $ranges = [];

            foreach (Range::getRangeCollection() as $range) {
                $startNode = $range->startContainer;
                $endNode = $range->endContainer;

                if (
                    $startNode === $this
                    || $startNode === $this->parentNode
                    || $endNode === $this
                    || $endNode === $this->parentNode
                ) {
                    $ranges[] = [$range, $startNode, $endNode];
                }
            }

            foreach ($ranges as [$range, $startNode, $endNode]) {
                $startOffset = $range->startOffset;

                if ($startNode === $this && $startOffset > $offset) {
                    $range->setStartInternal($newNode, $startOffset - $offset);
                }
            }

            foreach ($ranges as [$range, $startNode, $endNode]) {
                $endOffset = $range->endOffset;

                if ($endNode === $this && $endOffset > $offset) {
                    $range->setEndInternal($newNode, $endOffset - $offset);
                }
            }

            foreach ($ranges as [$range, $startNode, $endNode]) {
                $startOffset = $range->startOffset;

                if ($startNode === $this->parentNode && $startOffset === $treeIndex + 1) {
                    $range->setStartInternal($startNode, $startOffset + 1);
                }
            }

            foreach ($ranges as [$range, $startNode, $endNode]) {
                $endOffset = $range->endOffset;

                if ($endNode === $this->parentNode && $endOffset === $treeIndex + 1) {
                    $range->setEndInternal($endNode, $endOffset + 1);
                }
            }

            unset($ranges);
        }

        $this->doReplaceData($offset, $count, '');

        return $newNode;
    }

    protected function getNodeName(): string
    {
        return '#text';
    }
}
