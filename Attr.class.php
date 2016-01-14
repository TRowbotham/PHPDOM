<?php
namespace phpjs;

use phpjs\elements\Element;

// https://developer.mozilla.org/en-US/docs/Web/API/Attr
// https://dom.spec.whatwg.org/#attr

class Attr {
    protected $mLocalName;
    protected $mName;
    protected $mNamespaceURI;
    protected $mOwnerElement;
    protected $mPrefix;
    protected $mValue;

    public function __construct($aLocalName, $aValue, $aName = null, $aNamespace = null, $aPrefix = null, Element $aOwnerElement = null) {
        $this->mLocalName = $aLocalName;
        $this->mName = $aName ? $aName : $aLocalName;
        $this->mNamespaceURI = $aNamespace;
        $this->mOwnerElement = $aOwnerElement;
        $this->mPrefix = $aPrefix;
        $this->mValue = $aValue;
    }

    public function __get($aName) {
        switch ($aName) {
            case 'localName':
                return $this->mLocalName;

            case 'name':
                return $this->mName;

            case 'namespaceURI':
                return $this->mNamespaceURI;

            case 'ownerElement':
                return $this->mOwnerElement;

            case 'prefix':
                return $this->mPrefix;

            case 'value':
                return $this->mValue;
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'value':
                if (!$this->mOwnerElement) {
                    $this->mValue = $aValue;
                } else {
                    $this->mOwnerElement->_changeAttributeValue($this, $aValue);
                }

        }
    }

    public static function _isBool($aAttributeName) {
        switch ($aAttributeName) {
            case 'async':
            case 'autofocus':
            case 'autoplay':
            case 'checked':
            case 'controls':
            case 'disabled':
            case 'default':
            case 'defer':
            case 'hidden':
            case 'ismap':
            case 'loop':
            case 'multiple':
            case 'novalidate':
            case 'ping':
            case 'readonly':
            case 'required':
            case 'reversed':
            case 'scoped':
                return true;

            default:
                return false;
        }
    }

    public function _setOwnerElement(Element $aElement = null) {
        $this->mOwnerElement = $aElement;
    }
}
