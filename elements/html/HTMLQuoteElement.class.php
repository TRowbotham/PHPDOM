<?php
namespace phpjs\elements\html;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#htmlquoteelement
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-blockquote-element
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-q-element
 */
class HTMLQuoteElement extends HTMLElement
{
    private $mCite;

    protected function __construct()
    {
        parent::__construct();

        $this->mCite = '';
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'cite':
                return $this->mCite;

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

            default:
                parent::__set($aName, $aValue);
        }
    }
}
