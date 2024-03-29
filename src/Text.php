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

    public function isEqualNode(?Node $otherNode): bool
    {
        return $otherNode !== null
            && $otherNode->nodeType === $this->nodeType
            && $otherNode instanceof self
            && $otherNode->data === $this->data
            && $otherNode->hasEqualChildNodes($otherNode);
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

            foreach (Range::getRangeCollection() as $range) {
                // 7.2. For each live range whose start node is node and start offset is greater
                // than offset, set its start node to new node and decrease its start offset by
                // offset.
                if ($range->startNode === $this && $range->startOffset > $offset) {
                    $range->startNode = $newNode;
                    $range->startOffset -= $offset;

                // 7.4. For each live range whose start node is parent and start offset is equal to
                // the index of node plus 1, increase its start offset by 1.
                } elseif ($range->startNode === $this->parentNode && $range->startOffset === $treeIndex + 1) {
                    $range->startOffset += 1;
                }

                // 7.3. For each live range whose end node is node and end offset is greater than
                // offset, set its end node to new node and decrease its end offset by offset.
                if ($range->endNode === $this && $range->endOffset > $offset) {
                    $range->endNode = $newNode;
                    $range->endOffset -= $offset;

                // 7.5. For each live range whose end node is parent and end offset is equal to the
                // index of node plus 1, increase its end offset by 1.
                } elseif ($range->endNode === $this->parentNode && $range->endOffset === $treeIndex + 1) {
                    $range->endOffset += 1;
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
