<?php
namespace phpjs\elements\html;

/**
 * @see https://html.spec.whatwg.org/#the-colgroup-element
 * @see https://html.spec.whatwg.org/#the-col-element
 */
class HTMLTableColElement extends HTMLElement
{
    private $mSpan;

    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null)
    {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);

        if (strcasecmp($aLocalName, 'col') == 0) {
            $this->mEndTagOmitted = true;
        }

        $this->mSpan = 0;
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'span':
                return $this->mSpan;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
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
