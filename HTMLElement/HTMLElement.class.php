<?php
require_once 'Element.class.php';

class HTMLElement extends Element {

    protected $mAccessKey;
    protected $mAccessKeyLabel;
    protected $mContentEditable;
    protected $mDir;
    protected $mDataset;
    protected $mHidden;
    protected $mIsContentEditable;
    protected $mLang;
    protected $mSpellcheck;
    protected $mTabIndex;
    protected $mTitle;
    protected $mTranslate;


        $this->mAccessKey = '';
        $this->mAccessKeyLabel = '';
        $this->mContentEditable = false;
    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
        $this->mDataset;
        $this->mDir = '';
        $this->mHidden = false;
        $this->mIsContentEditable = false;
        $this->mLang = '';
        $this->mNodeType = Node::ELEMENT_NODE;
        $this->mSpellcheck = false;
        $this->mTabIndex = '';
        $this->mTitle = '';
        $this->mTranslate = false;
    }

    public function __get($aName) {
        switch ($aName) {
            case 'accessKey':
                return $this->mAccessKey;
            case 'accessKeyLabel':
                return $this->mAccessKeyLabel;
            case 'contentEditable':
                return $this->mContentEditable;
            case 'dataset':
                return $this->mDataset;
            case 'dir':
                return $this->mDir;
            case 'hidden':
                return $this->mHidden;
            case 'isContentEditable':
                return $this->mIsContentEditable;
            case 'lang':
                return $this->mLang;
            case 'spellcheck':
                return $this->mSpellcheck;
            case 'tabIndex':
                return $this->mTabIndex;
            case 'title':
                return $this->mTitle;
            case 'translate':
                return $this->mTranslate;
            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'accessKey':
                $this->mAccessKey = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'accessKeyLabel':
                $this->mAccessKeyLabel = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'contentEditable':
                $this->mContentEditable = $this->mIsContentEditable = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'dir':
                $this->mDir = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'hidden':
                $this->mHidden = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'lang':
                $this->mLang = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'spellcheck':
                $this->mSpellcheck = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'tabIndex':
                $this->mTabIndex = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'title':
                $this->mTitle = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'translate':
                $this->mTranslate = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    public function __toString() {
        return get_class($this);
    }

    protected function _onAttributeChange(Event $aEvent) {
        switch ($aEvent->detail['attr']->name) {
            case 'accesskey':
                $this->mAccessKey = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'accesskeylabel':
                $this->mAccessKeyLabel = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'contenteditable':
                $this->mContentEditable = $this->mIsContentEditable = $aEvent->detail['action'] == 'set' ? true : false;

                break;

            case 'dir':
                $this->mDir = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'hidden':
                $this->hidden = $aEvent->detail['action'] == 'set' ? true : false;

                break;

            case 'lang':
                $this->mLang = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'spellcheck':
                $this->spellcheck = $aEvent->detail['action'] == 'set' ? true : false;

                break;

            case 'tabindex':
                $this->tabindex = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'title':
                $this->mTitle = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'translate':
                $this->mTranslate = $aEvent->detail['action'] == 'set' ? true : false;

                break;

            default:
                parent::_onAttributeChange($aEvent);
        }
    }
}
