<?php
namespace phpjs;

use phpjs\exceptions\IndexSizeError;

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
    public function __construct($aData = '')
    {
        parent::__construct(Utils::DOMString($aData));

        $this->mNodeType = Node::TEXT_NODE;
    }

    public function __get($aName)
    {
        switch ($aName) {
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
                    $wholeText .= $startNode->mData;
                    $startNode = $startNode->nextSibling;
                }

                return $wholeText;

            default:
                return parent::__get($aName);
        }
    }

    public function __toString()
    {
        return $this->getNodeName() . ' ' . $this->mData;
    }

    public function splitText($aOffset)
    {
        $length = $this->mLength;

        if ($aOffset > $length) {
            throw new IndexSizeError();
        }

        $count = $length - $aOffset;
        $newData = $this->substringData($aOffset, $count);
        $newNode = new Text($newData);
        $newNode->nodeDocument = $this->nodeDocument;
        $ranges = Range::_getRangeCollection();

        if ($this->mParentNode) {
            $this->mParentNode->insertNode($newNode, $this->nextSibling);
            $treeIndex = $this->_getTreeIndex();

            foreach ($ranges as $index => $range) {
                $startOffset = $range->startOffset;

                if ($range->startContainer === $this &&
                    $startOffset > $aOffset
                ) {
                    $range->setStart($newNode, $startOffset - $aOffset);
                }
            }

            foreach ($ranges as $index => $range) {
                $endOffset = $range->endOffset;

                if ($range->endContainer === $this && $endOffset > $aOffset) {
                    $range->setEnd($newNode, $endOffset - $aOffset);
                }
            }

            foreach ($ranges as $index => $range) {
                $startContainer = $range->startContainer;
                $startOffset = $range->startOffset;

                if ($startContainer === $this &&
                    $startOffset == $treeIndex + 1
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

        $this->doReplaceData($aOffset, $count, '');

        if (!$this->mParentNode) {
            foreach ($ranges as $index => $range) {
                $startContainer = $range->startContainer;

                if ($startContainer === $this &&
                    $range->startOffset > $aOffset
                ) {
                    $range->setStart($startContainer, $aOffset);
                }
            }

            foreach ($ranges as $index => $range) {
                $endContainer = $range->endContainer;

                if ($endContainer === $this && $range->endOffset > $aOffset) {
                    $range->setEnd($endContainer, $aOffset);
                }
            }
        }

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
