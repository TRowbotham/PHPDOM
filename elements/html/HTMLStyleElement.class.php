<?php
namespace phpjs\elements\html;

/**
 * Represents the HTML <style> element.
 *
 * @link https://html.spec.whatwg.org/multipage/semantics.html#the-style-element
 *
 * @property string $media Reflects the HTML media attribute.  This accepts a
 *     valid media query to instruct the browser on when this resource should
 *     apply to the document.
 *
 * @property bool $scoped Reflects the HTML scoped attribute.  When present, the
 *     styles contained within this element will only apply to its parent
 *     element and siblings.
 *
 * @property string $type Reflects the HTML type attribute, which hints to the
 *     browser what the content's MIME type is.  This property defaults to
 *     text/css.
 */
class HTMLStyleElement extends HTMLElement
{
    private $mMedia;
    private $mScoped;
    private $mType;

    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null)
    {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
    }

    public function __get($aName) {
        switch ($aName) {
            case 'media':
                return $this->reflectStringAttributeValue($aName);

            case 'scoped':
                return $this->reflectBooleanAttributeValue($aName);

            case 'type':
                return $this->reflectStringAttributeValue($aName);

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'media':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'scoped':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'type':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }
}
