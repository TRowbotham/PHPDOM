<?php
// https://html.spec.whatwg.org/#the-colgroup-element
// https://html.spec.whatwg.org/#the-col-element

require_once 'HTMLElement.class.php';

class HTMLTableColElement extends HTMLElement {
    private $mSpan;

    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);

        if (strcasecmp($aTagName, 'col') == 0) {
            $this->mEndTagOmitted = true;
        }

        $this->mSpan = 0;
    }

    public function __get($aName) {
        switch ($aName) {
            case 'span':
                return $this->mSpan;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'span':
                $this->mSpan = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }
}
