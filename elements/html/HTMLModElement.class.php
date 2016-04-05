<?php
namespace phpjs\elements\html;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#htmlmodelement
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-ins-element
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-del-element
 */
class HTMLModElement extends HTMLElement
{
    private $mCite;
    private $mDateTime;

    protected function __construct()
    {
        parent::__construct();

        $this->mCite = '';
        $this->mDateTime = '';
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'cite':
                return $this->mCite;

            case 'dateTime':
                return $this->mDateTime;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'cite':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mCite = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

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
