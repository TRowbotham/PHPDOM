<?php
namespace phpjs\elements\html;

// https://html.spec.whatwg.org/multipage/semantics.html#the-ol-element
class HTMLOListElement extends HTMLElement {
    private $mReversed;
    private $mStart;
    private $mType;

    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);

        $this->mReversed = false;
        $this->mStart = 1;
        $this->mType = '';
    }

    public function __get($aName) {
        switch ($aName) {
            case 'reversed':
                return $this->mReversed;

            case 'start':
                return $this->mStart;

            case 'type':
                return $this->mType;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'reversed':
                $this->mReversed = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'start':
                $this->mStart = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'type':
                $this->mType = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }
}
