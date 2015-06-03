<?php
// https://html.spec.whatwg.org/multipage/semantics.html#the-time-element

require_once 'HTMLElement.class.php';

class HTMLTimeElement extends HTMLElement {
    private $mDateTime;

    public function __construct($aTagName) {
        parent::__construct($aTagName);

        $this->mDateTime = '';
    }

    public function __get($aName) {
        switch ($aName) {
            case 'dateTime':
                return $this->mDateTime;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'dateTime':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mDateTime = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }
}
