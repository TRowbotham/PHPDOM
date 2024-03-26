<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Exception\IndexSizeError;

/**
 * Represents the text content of a Node.
 *
 * @see https://dom.spec.whatwg.org/#text
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Text
 */
class Text extends CharacterData
{
    /**
     * @see https://dom.spec.whatwg.org/#dom-text-wholetext
     */
    public string $wholeText {
        get {
            $wholeText = '';
            $startNode = $this;

            while ($startNode) {
                if (!$startNode->previousSibling instanceof Text) {
                    break;
                }

                $startNode = $startNode->previousSibling;
            }

            while ($startNode instanceof Text) {
                $wholeText .= $startNode->_data;
                $startNode = $startNode->nextSibling;
            }

            return $wholeText;
        }
    }

    public string $nodeName {
        get => '#text';
    }

    public function __construct(Document $document, string $data = '', int $nodeType = self::TEXT_NODE)
    {
        parent::__construct($document, $data, $nodeType);
    }

    public function isEqualNode(?Node $otherNode): bool
    {
        return $otherNode !== null
            && $otherNode->nodeType === $this->nodeType
            && $otherNode instanceof self
            && $otherNode->_data === $this->_data
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
                if ($range->start->node === $this && $range->start->offset > $offset) {
                    $range->start->node = $newNode;
                    $range->start->offset -= $offset;

                // 7.4. For each live range whose start node is parent and start offset is equal to
                // the index of node plus 1, increase its start offset by 1.
                } elseif ($range->start->node === $this->parentNode && $range->start->offset === $treeIndex + 1) {
                    $range->start->offset += 1;
                }

                // 7.3. For each live range whose end node is node and end offset is greater than
                // offset, set its end node to new node and decrease its end offset by offset.
                if ($range->end->node === $this && $range->end->offset > $offset) {
                    $range->end->node = $newNode;
                    $range->end->offset -= $offset;

                // 7.5. For each live range whose end node is parent and end offset is equal to the
                // index of node plus 1, increase its end offset by 1.
                } elseif ($range->end->node === $this->parentNode && $range->end->offset === $treeIndex + 1) {
                    $range->end->offset += 1;
                }
            }
        }

        $this->doReplaceData($offset, $count, '');

        return $newNode;
    }
}
