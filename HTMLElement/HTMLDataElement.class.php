<?php
namespace phpjs;

// https://html.spec.whatwg.org/multipage/semantics.html#the-data-element

require_once 'HTMLElement.class.php';

class HTMLDataElement extends HTMLElement {
    private $mValue;

    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);

        $this->mValue = '';
    }

    public function __get($aName) {
        switch ($aName) {
            case 'value':
                return $this->mValue;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'value':
                if (!is_scalar($aValue)) {
                    break;
                }

                $this->mValue = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }
}
