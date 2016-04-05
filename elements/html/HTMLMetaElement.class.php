<?php
namespace phpjs\elements\html;

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

        $this->mEndTagOmitted = true;
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
                $this->mAttributesList->setAttrValue($this, $aName, $aValue);

                break;

            case 'httpEquiv':
                $this->mAttributesList->setAttrValue(
                    $this,
                    'http-equiv',
                    $aValue
                );

                break;

            case 'name':
                $this->mAttributesList->setAttrValue($this, $aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }
}
