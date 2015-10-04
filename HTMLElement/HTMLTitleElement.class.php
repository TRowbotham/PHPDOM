<?php
require_once 'HTMLElement.class.php';

class HTMLTitleElement extends HTMLElement {
    public function __construct($aTagName) {
        parent::__construct($aTagName);
    }

    public function __get($aName) {
        switch ($aName) {
            case 'text':
                $value = '';

                foreach ($this->mChildNodes as $node) {
                    if ($node instanceof Text) {
                        $value .= $node->data;
                    }
                }

                return $value;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'text':
                parent::__set('textContent', $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }
}
