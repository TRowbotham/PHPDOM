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
        parent::__construct();

        $this->mData = $aData;
        $this->mNodeName = '#text';
        $this->mNodeType = Node::TEXT_NODE;
    }

    public function __get($aName) {
        switch ($aName) {
            case 'wholeText':
                $wholeText = '';
                $startNode = $this;

                while ($startNode) {
                    if (!($startNode->previousSibling instanceof Text)) {
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
                return parent::__get($aName);
        }
    }

    public function __toString() {
        return $this->mNodeName . ' ' . $this->mData;
    }

    public function splitText($aOffset) {
        $length = $this->length;

        if ($aOffset > $length) {
            throw new IndexSizeError;
        }

        $count = $length - $aOffset;
        $newData = substr($this->mData, $aOffset, $count);
        $newNode = new Text($newData);
        $newNode->mOwnerDocument = $this;

        if ($this->mParentNode) {
            $this->mParentNode->_insertNodeBeforeChild($newNode, $this->mNextSibling);

            foreach (Range::_getRangeCollection() as $index => $range) {
                if ($range->startContainer === $this && $range->startOffset > $aOffset) {
                    $range->setStart($newNode, $range->startOffset - $aOffset);
                }
            }

            foreach (Range::_getRangeCollection() as $index => $range) {
                if ($range->endContainer === $this && $range->endOffset > $aOffset) {
                    $range->setEnd($newNode, $range->endOffset - $aOffset);
                }
            }

            foreach (Range::_getRangeCollection() as $index => $range) {
                if ($range->startContainer === $this && $range->startOffset == $this->_getTreeIndex() + 1) {
                    $range->setStart($range->startContainer, $range->startOffset + 1);
                }
            }

            foreach (Range::_getRangeCollection() as $index => $range) {
                if ($range->endContainer === $this && $range->endOffset == $this->_getTreeIndex() + 1) {
                    $range->setEnd($range->endContainer, $range->endOffset + 1);
                }
            }
        }

        $this->replaceData($aOffset, $count, $this->mData);

        if (!$this->mParentNode) {
            foreach (Range::_getRangeCollection() as $index => $range) {
                if ($range->startContainer === $this && $range->startOffset > $aOffset) {
                    $range->setStart($range->startContainer, $aOffset);
                }
            }

            foreach (Range::_getRangeCollection() as $index => $range) {
                if ($range->endContainer === $this && $range->endOffset > $aOffset) {
                    $range->setEnd($range->endContainer, $aOffset);
                }
            }
        }

        return $newNode;
    }
}
