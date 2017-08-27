<?php
namespace Rowbot\DOM\Element\HTML;

/**
 * Represents the HTML <meta> element.
 *
 * @see https://html.spec.whatwg.org/#the-meta-element
 *
 * @property string $content Reflects the value of the HTML content attribute.
 *     Contains the value part of a name => value pair when the name attribute
 *     is present.
 *
 * @property string $httpEquiv Reflects the value of the HTML http-equiv
 *     attribute.
 *
 * @property string $name Reflects the value of the HTML name attribute.
 */
class HTMLMetaElement extends HTMLElement
{
    protected function __construct()
    {
        parent::__construct();
    }

    public function __get($aName)
    {
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

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'content':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'httpEquiv':
                $this->mAttributesList->setAttrValue('http-equiv', $aValue);

                break;

            case 'name':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }
}
