<?php
// https://dom.spec.whatwg.org/#interface-domsettabletokenlist

require_once 'DOMTokenList.class.php';

class DOMSettableTokenList extends DOMTokenList {
    public function __construct(Element $aElement, $aAttrLocalName = null) {
        parent::__construct($aElement, $aAttrLocalName);
    }

    public function __get($aName) {
        switch ($aName) {
            case 'value':
                return $this->serializeOrderedSet($this->mTokens);

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'value':
                $this->mTokens = self::_parseOrderedSet($aValue);
        }
    }

    /**
     * If there is no associated attribute, then terminate the update steps.
     * Otherwise, set an attribute value on the associated element using the
     * provided attribute local name.
     */
    private function update() {
        if (!$this->mAttrLocalName) {
            return;
        }

        parent::update();
    }
}
