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

    public function __get($name)
    {
        switch ($name) {
            case 'content':
                return $this->reflectStringAttributeValue($name);

            case 'httpEquiv':
                return $this->reflectStringAttributeValue('http-equiv');

            case 'name':
                return $this->reflectStringAttributeValue($name);

            default:
                return parent::__get($name);
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'content':
                $this->attributeList->setAttrValue($name, $value);

                break;

            case 'httpEquiv':
                $this->attributeList->setAttrValue('http-equiv', $value);

                break;

            case 'name':
                $this->attributeList->setAttrValue($name, $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }
}
