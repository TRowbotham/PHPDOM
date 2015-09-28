<?php
// https://developer.mozilla.org/en-US/docs/Web/API/CharacterData
// https://dom.spec.whatwg.org/#characterdata

require_once 'Node.class.php';
require_once 'ChildNode.class.php';
require_once 'NonDocumentTypeChildNode.class.php';

/**
 * Represents a Node that contains characters.
 *
 * @link https://dom.spec.whatwg.org/#characterdata
 * @link https://developer.mozilla.org/en-US/docs/Web/API/CharacterData
 *
 * @property        string          $data                       Represents the textual data contained by this Node.
 *
 * @property-read   int             $length                     Represents the length of the data contained by this Node.
 *
 * @property-read   Element|null    $nextElementSibling         Returns the next sibling that is an Element, if any.
 *
 * @property-read   Element|null    $previousElementSibling     Returns the previous sibling that is an Element, if any.
 */
abstract class CharacterData extends Node {
    use ChildNode, NonDocumentTypeChildNode;

    protected $mData;

    public function __construct() {
        parent::__construct();

        $this->mData = '';
    }

    public function __get($aName) {
        switch ($aName) {
            case 'data':
                return $this->mData;
            case 'length':
                return strlen($this->mData);
            case 'nextElementSibling':
                return $this->getNextElementSibling();
            case 'previousElementSibling':
                return $this->getPreviousElementSibling();
            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'data':
                if (is_string($aValue)) {
                    $this->mData = $aValue;
                }

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    public function appendData($aData) {
        // TODO
    }

    public function deleteData($aOffset, $aCount) {
        // TODO
    }

    public function insertData($aOffset, $aData) {
        // TODO
    }

    public function replaceData($aOffset, $aCount, $aData) {
        // TODO
    }

    public function substringData($aOffset, $aCount) {
        // TODO
    }
}
