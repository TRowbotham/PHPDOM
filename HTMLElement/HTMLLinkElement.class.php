<?php
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
    private $mCrossOrigin;
    private $mHref;
    private $mHrefLang;
    private $mInvalidateRelList;
    private $mMedia;
    private $mRel;
    private $mRelList;
    private $mSizes;
    private $mType;

    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);

        $this->mCrossOrigin = '';
        $this->mEndTagOmitted = true;
        $this->mHref = '';
        $this->mHrefLang = '';
        $this->mInvalidateRelList = false;
        $this->mMedia = '';
        $this->mRel = '';
        $this->mSizes = new DOMSettableTokenList($this, 'sizes');
        $this->mType = '';
    }

    public function __get($aName) {
        switch ($aName) {
            case 'crossOrigin':
                return $this->mCrossOrigin;

            case 'href':
                return $this->mHref;

            case 'hrefLang':
                return $this->mHrefLang;

            case 'media':
                return $this->mMedia;

            case 'rel':
                return $this->mRel;

            case 'relList':
                return $this->getRelList();

            case 'sizes':
                return $this->mSizes->value;

            case 'type':
                return $this->mType;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'crossOrigin':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mCrossOrigin = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'href':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mHref = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'hrefLang':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mHrefLang = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'media':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mMedia = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'rel':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mRel = $aValue;
                $this->mInvalidateRelList = true;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'sizes':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mSizes->value = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'type':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mType = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    protected function _onAttributeChange(Event $aEvent) {
        switch ($aEvent->detail['attr']->name) {
            case 'crossorigin':
                $this->mCrossOrigin = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'href':
                $this->mHref = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'hrefLang':
                $this->mHrefLang = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'media':
                $this->mMedia = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'rel':
                $this->mRel = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';
                $this->mInvalidateRelList = true;

                break;

            case 'sizes':
                $this->mSizes = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'type':
                $this->mType = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;


            default:
                parent::_onAttributeChange($aEvent);
        }
    }

    private function getRelList() {
        if (!$this->mRelList || $this->mInvalidateRelList) {
            $this->mInvalidateRelList = false;
            $this->mRelList = new DOMTokenList($this, 'rel');

            if (!empty($this->mRel)) {
                call_user_func_array(array($this->mRelList, 'add'), $this->mRelList->_parseOrderedSet($this->mRel));
            }
        }

        return $this->mRelList;
    }
}
