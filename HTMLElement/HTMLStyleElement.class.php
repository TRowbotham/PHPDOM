<?php
require_once 'HTMLElement.class.php';

/**
 * Represents the HTML <style> element.
 *
 * @link https://html.spec.whatwg.org/multipage/semantics.html#the-style-element
 *
 * @property string $media  Reflects the HTML media attribute.  This accepts a valid media query to instruct the browser on when this resource
 *                          should apply to the document.
 *
 * @property bool $scoped   Reflects the HTML scoped attribute.  When present, the styles contained within this element will only apply to
 *                          its parent element and siblings.
 *
 * @property string $type   Reflects the HTML type attribute, which hints to the browser what the content's MIME type is.  This property
 *                          defaults to text/css.
 */
class HTMLStyleElement extends HTMLElement {
    private $mMedia;
    private $mScoped;
    private $mType;

    public function __construct($aTagName) {
        parent::__construct($aTagName);

        $this->mMedia = '';
        $this->mScoped = false;
        $this->mType = 'text/css';
    }

    public function __get($aName) {
        switch ($aName) {
            case 'media':
                return $this->mMedia;

            case 'scoped':
                return $this->mScoped;

            case 'type':
                return $this->mType;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'media':
                $this->mMedia = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'scoped':
                $this->mScoped = (bool)$aValue;
                $this->_updateAttributeOnPropertyChange($aName, $this->mScoped);

                break;

            case 'type':
                $this->mType = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    protected function _onAttributeChange(Event $aEvent) {
        switch ($aEvent->detail['attr']->name) {
            case 'media':
                $this->mMedia = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'scoped':
                $this->mScoped = $aEvent->detail['action'] == 'set' ? true : false;

                break;

            case 'type':
                $this->mType = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : 'text/css';

                break;

            default:
                parent::_onAttributeChange($aEvent);
        }
    }
}
