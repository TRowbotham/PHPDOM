<?php
namespace phpjs;

require_once 'HTMLElement.class.php';
require_once 'DOMSettableTokenList.class.php';

/**
 * Represents the HTML <link> element.
 *
 * @link https://html.spec.whatwg.org/multipage/semantics.html#the-link-element
 *
 * @property        string          $crossOrigin    Reflects the HTML crossorigin attribute and instructs how crossorigin requrests should be
 *                                                  handled for this particular resource.
 *
 * @property        string          $href           Reflects the HTML href attribute and represents the location of the linked resource.
 *
 * @property        string          $hrefLang       Reflects the HTML hreflang attribute, which indicates the language of the linked resource.
 *
 * @property        string          $media          Reflects the HTML media attribute.  This accepts a valid media query to instruct the browser on
 *                                                  when this resource should apply to the document.
 *
 * @property        string          $rel            Reflects the HTML rel attribute, which indicates the relationship between the document and
 *                                                  the linked resource.
 *
 * @property        string          $sizes          Reflects the HTML sizes attribute, which is used to describe the sizes of icons when the HTML
 *                                                  rel attribute with a value of icon is present.
 *
 * @property        string          $type           Reflects the HTML type attribute, which hints at the linked resource's MIME type.
 *
 * @property-read   DOMTokenList    $relList        Reflects the HTML rel attribute as a list of tokens.
 */
class HTMLLinkElement extends HTMLElement {
    private $mRelList;
    private $mSizes;

    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);

        $this->mEndTagOmitted = true;
        $this->mRelList = new DOMTokenList($this, 'rel');
        $this->mSizes = new DOMSettableTokenList($this, 'sizes');
    }

    public function __get($aName) {
        switch ($aName) {
            case 'crossOrigin':
                return $this->getAttributeStateEnumeratedString('crossorigin', 'anonymous', 'no-cors', self::CORS_STATE_MAP);

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

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'crossOrigin':
                $this->_setAttributeValue('crossorigin', $aValue);

                break;

            case 'href':
                $this->_setAttributeValue($aName, $aValue);

                break;

            case 'hrefLang':
                $this->_setAttributeValue('hreflang', $aValue);

                break;

            case 'media':
                $this->_setAttributeValue($aName, $aValue);

                break;

            case 'rel':
                $this->_setAttributeValue($aName, $aValue);

                break;

            case 'sizes':
                $this->_setAttributeValue($aName, $aValue);

                break;

            case 'type':
                $this->_setAttributeValue($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    protected function attributeHookHandler($aHookType, Attr $aAttr) {
        switch ($aAttr->name) {
            case 'rel':
                if ($aHookType == 'set') {
                    $value = $aAttr->value;

                    if (!empty($value)) {
                        $this->mRelList->appendTokens(DOMTokenList::_parseOrderedSet($value));
                    }
                } elseif ($aHookType == 'removed') {
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
