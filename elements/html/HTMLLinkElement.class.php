<?php
namespace phpjs\elements\html;

use phpjs\Attr;
use phpjs\AttributeList;
use phpjs\DOMTokenList;
use phpjs\Utils;

/**
 * Represents the HTML <link> element.
 *
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-link-element
 *
 * @property string $crossOrigin Reflects the HTML crossorigin attribute and
 *     instructs how crossorigin requrests should be handled for this particular
 *     resource.
 *
 * @property string $href Reflects the HTML href attribute and represents the
 *     location of the linked resource.
 *
 * @property string $hrefLang Reflects the HTML hreflang attribute, which
 *     indicates the language of the linked resource.
 *
 * @property string $media Reflects the HTML media attribute.  This accepts a
 *     valid media query to instruct the browser on when this resource should
 *     apply to the document.
 *
 * @property string $rel Reflects the HTML rel attribute, which indicates the
 *     relationship between the document and the linked resource.
 *
 * @property string $sizes Reflects the HTML sizes attribute, which is used to
 *     describe the sizes of icons when the HTML rel attribute with a value of
 *     icon is present.
 *
 * @property string $type Reflects the HTML type attribute, which hints at the
 *     linked resource's MIME type.
 *
 * @property-read DOMTokenList $relList Reflects the HTML rel attribute as a
 *     list of tokens.
 */
class HTMLLinkElement extends HTMLElement
{
    private $mRelList;
    private $mSizes;

    protected function __construct()
    {
        parent::__construct();

        $this->mRelList = new DOMTokenList($this, 'rel');
        $this->mSizes = new DOMTokenList($this, 'sizes');
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'crossOrigin':
                return $this->getAttributeStateEnumeratedString(
                    'crossorigin',
                    'anonymous',
                    'no-cors',
                    self::CORS_STATE_MAP
                );

            case 'href':
                return $this->reflectStringAttributeValue($aName);

            case 'hrefLang':
                return $this->reflectStringAttributeValue('hreflang');

            case 'media':
                return $this->reflectStringAttributeValue($aName);

            case 'rel':
                return $this->reflectStringAttributeValue($aName);

            case 'relList':
                return $this->mRelList;

            case 'sizes':
                return $this->reflectStringAttributeValue($aName);

            case 'type':
                return $reflectStringAttributeValue($aName);

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'crossOrigin':
                $this->mAttributesList->setAttrValue(
                    $this,
                    'crossorigin',
                    $aValue
                );

                break;

            case 'href':
                $this->mAttributesList->setAttrValue($this, $aName, $aValue);

                break;

            case 'hrefLang':
                $this->mAttributesList->setAttrValue(
                    $this,
                    'hreflang',
                    $aValue
                );

                break;

            case 'media':
                $this->mAttributesList->setAttrValue($this, $aName, $aValue);

                break;

            case 'rel':
                $this->mAttributesList->setAttrValue($this, $aName, $aValue);

                break;

            case 'sizes':
                $this->mAttributesList->setAttrValue($this, $aName, $aValue);

                break;

            case 'type':
                $this->mAttributesList->setAttrValue($this, $aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    public function attributeHookHandler($aHookType, Attr $aAttr)
    {
        switch ($aAttr->name) {
            case 'rel':
                if ($aHookType & AttributeList::ATTR_SET) {
                    $value = $aAttr->value;

                    if (!empty($value)) {
                        $this->mRelList->appendTokens(
                            Utils::parseOrderedSet($value)
                        );
                    }
                } elseif ($aHookType & AttributeList::ATTR_REMOVED) {
                    $this->mRelList->emptyList();
                }

                break;

            case 'sizes':
                $this->mSizes->value = $aAttr->value;

                break;

            default:
                parent::attributeHookHandler($aHookType, $aAttr);
        }
    }
}
