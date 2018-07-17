<?php
namespace Rowbot\DOM\Element\HTML;

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
    private $type;

    protected function __construct()
    {
        parent::__construct();
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'media':
                return $this->reflectStringAttributeValue($name);

            case 'scoped':
                return $this->reflectBooleanAttributeValue($name);

            case 'type':
                return $this->reflectStringAttributeValue($name);

            default:
                return parent::__get($name);
        }
    }

    public function __set(string $name, $value)
    {
        switch ($name) {
            case 'media':
                $this->attributeList->setAttrValue($name, $value);

                break;

            case 'scoped':
                $this->attributeList->setAttrValue($name, $value);

                break;

            case 'type':
                $this->attributeList->setAttrValue($name, $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }
}
