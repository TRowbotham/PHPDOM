<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\DOMTokenList;

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

    public function __destruct()
    {
        $this->mRelList = null;
        $this->mSizes = null;
        parent::__destruct();
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
                $this->mAttributesList->setAttrValue('crossorigin', $aValue);

                break;

            case 'href':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'hrefLang':
                $this->mAttributesList->setAttrValue('hreflang', $aValue);

                break;

            case 'media':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'rel':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'sizes':
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
