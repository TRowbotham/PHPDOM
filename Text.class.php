<?php
require_once 'CharacterData.class.php';

/**
 * Represents the text content of a Node.
 *
 * @link https://dom.spec.whatwg.org/#text
 * @link https://developer.mozilla.org/en-US/docs/Web/API/Text
 *
 * @property-read string $wholeText Returns the concatenated string data of all contingious Text nodes relative
 *                                  to this Node in tree order.
 */
class Text extends CharacterData {
    public function __construct($aData = '') {
        parent::__construct($aData);

        $this->mNodeName = '#text';
        $this->mNodeType = Node::TEXT_NODE;
    }

    public function __get($aName) {
        switch ($aName) {
            case 'wholeText':
                $wholeText = '';
                $startNode = $this;

                while ($startNode) {
                    if (!($startNode->mPreviousSibling instanceof Text)) {
                        break;
                    }

                    $startNode = $startNode->mPreviousSibling;
                }

                while ($startNode instanceof Text) {
                    $wholeText .= $startNode->mData;
                    $startNode = $startNode->mNextSibling;
                }

                return $wholeText;

            default:
                return parent::__get($aName);
        }
    }

    public function __toString() {
        return $this->mNodeName . ' ' . $this->mData;
    }

    public function splitText($aOffset) {
        $length = $this->mLength;

        if ($aOffset > $length) {
            throw new IndexSizeError;
        }

        $count = $length - $aOffset;
        $newData = substr($this->mData, $aOffset, $count);
        $newNode = new Text($newData);
        $newNode->mOwnerDocument = $this;
        $ranges = Range::_getRangeCollection();

        if ($this->mParentNode) {
            $this->mParentNode->_insertNodeBeforeChild($newNode, $this->mNextSibling);
            $treeIndex = $this->_getTreeIndex();

            foreach ($ranges as $index => $range) {
                $startOffset = $range->startOffset;

                if ($range->startContainer === $this && $startOffset > $aOffset) {
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

                if ($startContainer === $this && $startOffset == $treeIndex + 1) {
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

        $this->replaceData($aOffset, $count, $this->mData);

        if (!$this->mParentNode) {
            foreach ($ranges as $index => $range) {
                $startContainer = $range->startContainer;

                if ($startContainer === $this && $range->startOffset > $aOffset) {
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
}
