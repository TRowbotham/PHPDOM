<?php
namespace phpjs\elements\html;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-li-element
 */

class HTMLLIElement extends HTMLElement
{
    private $mValue;

    protected function __construct()
    {
        parent::__construct();

        $this->mValue = '';
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'value':
                return $this->mValue;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'value':
                if (!is_string($aValue)) {
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
