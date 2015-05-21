<?php
// https://dom.spec.whatwg.org/#interface-domsettabletokenlist

require_once 'DOMTokenList.class.php';

class DOMSettableTokenList extends DOMTokenList {
    public function __construct() {
        parent::__construct();
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
}
