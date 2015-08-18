<?php
require_once 'HTMLElement.class.php';

/**
 * Represents the HTML <meta> element.
 *
 * @link https://html.spec.whatwg.org/#the-meta-element
 *
 * @property string $content    Reflects the value of the HTML content attribute.  Contains the value part of a name => value pair
 *                              when the name attribute is present.
 *
 * @property string $httpEquiv  Reflects the value of the HTML http-equiv attribute.
 *
 * @property string $name       Reflects the value of the HTML name attribute.
 */
class HTMLMetaElement extends HTMLElement {
    private $mContent;
    private $mHttpEquiv;
    private $mName;

    public function __construct($aTagName) {
        parent::__construct($aTagName);

        $this->mContent = '';
        $this->mEndTagOmitted = true;
        $this->mHttpEquiv = '';
        $this->mName = '';
    }

    public function __get($aName) {
        switch ($aName) {
            case 'content':
                return $this->mContent;

            case 'httpEquiv':
                return $this->mHttpEquiv;

            case 'name':
                return $this->mName;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'content':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mContent = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'httpEquiv':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mHttpEquiv = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'name':
                if (!is_string($aValue)) {
                    break;
                }

                $this->mName = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    protected function _onAttributeChange(Event $aEvent) {
        switch ($aEvent->detail['attr']->name) {
            case 'charset':
                $charset = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : 'utf-8';
                $this->mOwnerDocument->_setCharacterSet($charset);

                break;

            case 'content':
                $this->mContent = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'http-equiv':
                $this->mHttpEquiv = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'name':
                $this->mName = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            default:
                parent::_onAttributeChange($aEvent);
        }
    }
}
