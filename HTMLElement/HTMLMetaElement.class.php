<?php
namespace phpjs;

require_once 'HTMLElement.class.php';

/**
 * Represents the HTML <meta> element.
 *
 * @link https://html.spec.whatwg.org/#the-meta-element
 *
 * @property string $content    Reflects the value of the HTML content attribute.  Contains the value part of a name => value pair
 *                              when the name attribute is present.
 *
 * @property string $httpEquiv  Reflects the value of the HTML http-equiv attribute.
 *
 * @property string $name       Reflects the value of the HTML name attribute.
 */
class HTMLMetaElement extends HTMLElement {
    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);

        $this->mEndTagOmitted = true;
    }

    public function __get($aName) {
        switch ($aName) {
            case 'content':
                return $this->reflectStringAttributeValue($aName);

            case 'httpEquiv':
                return $this->reflectStringAttributeValue('http-equiv');

            case 'name':
                return $this->reflectStringAttributeValue($aName);

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'content':
                $this->_setAttributeValue($aName, $aValue);

                break;

            case 'httpEquiv':
                $this->_setAttributeValue('http-equiv', $aValue);

                break;

            case 'name':
                $this->_setAttributeValue($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }
}
