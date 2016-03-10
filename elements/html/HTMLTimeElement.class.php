<?php
namespace phpjs\elements\html;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-time-element
 */
class HTMLTimeElement extends HTMLElement
{
    private $mDateTime;

    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null)
    {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);

        $this->mDateTime = '';
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'dateTime':
                return $this->mDateTime;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
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
