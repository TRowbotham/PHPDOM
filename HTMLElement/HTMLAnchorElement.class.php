<?php
require_once 'HTMLElement.class.php';
require_once 'DOMSettableTokenList.class.php';
require_once 'URLUtils.class.php';

/**
 * Represents the HTML anchor element <a>.
 *
 * @link https://html.spec.whatwg.org/multipage/semantics.html#the-a-element
 *
 * @property string $download               Reflects the download HTML attribute, which indicates that the linked
 *                                          resource should be downloaded rather than displayed in the browser.
 *                                          The value is the prefered name of the file to be saved to disk.  While there
 *                                          are no restrictions on the characters allowed, you must take into consideration
 *                                          disallowed characters in file names on most operating systems.
 *
 * @property string $hash                   Represents the fragment identifier, including the leading hash (#) mark,
 *                                          if one is present, of the URL.
 *
 * @property string $host                   Represents the hostname and the port, if the port is not the
 *                                          default port, of the URL.
 *
 * @property string $hostname               Represents the hostname of the URL.
 *
 * @property string $href                   Reflects the href HTML attribute.
 *
 * @property string $hrefLang               Reflects the hrefLang HTML attribute, indicating the language of the linked
 *                                          resource.
 *
 * @property string $password               Represents the password specified in the URL.
 *
 * @property string $pathname               Represents the pathname of the URL, if any.
 *
 * @property string $ping                   Reflects the ping HTML attribute.  A notification will be sent to all the
 *                                          URLs contained within this property if the user clicks on this link.
 *
 * @property string $port                   Represents the port, if any, of the URL.
 *
 * @property string $protocol               Represents the protocol, including the trailing colon (:), of the URL.
 *
 * @property string $rel                    Reflects the rel HTML attribute, which specifies the relationship of the
 *                                          target object to the linked object.
 *
 * @property string $search                 Represents the query string, including the leading question mark (?), if
 *                                          any, of the URL.
 *
 * @property string $target                 Reflects the target HTML attribute, which indicates where to display the
 *                                          linked resource.
 *
 * @property string $type                   Reflects the type HTML attribute, which indicates the MIME type of the
 *                                          linked resource.
 *
 * @property string $username               Represents the username specified, if any, of the URL.
 *
 * @property-read string            $origin         Represents the URL's origin which is composed of the scheme, domain, and
 *                                                  port.
 *
 * @property-read DOMTokenList      $relList        Reflects the rel HTML attribute as a list of tokens.
 *
 * @property-read URLSearchParams   $searchParams   Represents the search property as a list of tokens.
 */
class HTMLAnchorElement extends HTMLElement {
    use URLUtils;

    private $mDownload;
    private $mHrefLang;
    private $mInvalidateRelList;
    private $mPing;
    private $mRel;
    private $mRelList;
    private $mTarget;
    private $mType;

    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);
        $this->initURLUtils();

        $this->mDownload = '';
        $this->mHrefLang = '';
        $this->mInvalidateRelList = false;
        $this->mPing = new DOMSettableTokenList($this, 'ping');
        $this->mRel = '';
        $this->mRelList = null;
        $this->mTarget = '';
        $this->mType = '';
    }

    public function __get($aName) {
        switch ($aName) {
            case 'download':
                return $this->mDownload;
            case 'hrefLang':
                return $this->mHrefLang;
            case 'ping':
                return $this->mPing->value;
            case 'rel':
                return $this->mRel;
            case 'relList':
                return $this->getRelList();
            case 'target':
                return $this->mTarget;
            case 'type':
                return $this->mType;
            default:
                $rv = $this->URLUtilsGetter($aName);

                if ($rv !== false) {
                    return $rv;
                }

                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'download':
                $this->mDownload = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'hrefLang':
                $this->mHrefLang = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'ping':
                $this->mPing->value = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'rel':
                $this->mRel = $aValue;
                $this->mInvalidateRelList = true;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'target':
                $this->mTarget = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'type':
                $this->mType = $aValue;
                $this->_updateAttributeOnPropertyChange($aName, $aValue);

                break;

            default:
                $this->URLUtilsSetter($aName, $aValue);
                parent::__set($aName, $aValue);
        }
    }

    public function update(SplSubject $aObject) {
        if ($aObject instanceof URLSearchParams) {
            $this->mUrl->mQuery = $aObject->toString();
            $this->preupdate();
        } else {
            parent::update($aObject);
        }
    }

    protected function _onAttributeChange(Event $aEvent) {
        switch ($aEvent->detail['attr']->name) {
            case 'download':
                $this->mDownload = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'hrefLang':
                $this->mHrefLang = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'ping':
                $this->mPing->value = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'rel':
                $this->mRel = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';
                $this->mInvalidateRelList = true;

                break;

            case 'target':
                $this->mTarget = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'type':
                $this->mType = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            default:
                parent::_onAttributeChange($aEvent);
        }
    }

    private function getBaseURL() {
        return URLParser::basicURLParser($this->mOwnerDocument->baseURI);
    }

    private function getRelList() {
        if (!$this->mRelList || $this->mInvalidateRelList) {
            $this->mInvalidateRelList = false;
            $this->mRelList = new DOMTokenList($this, 'rel');

            if (!empty($this->mRel)) {
                call_user_func_array(array($this->mRelList, 'add'), explode(' ', $this->mRel));
            }
        }

        return $this->mRelList;
    }

    private function updateURL($aValue) {
        $this->_updateAttributeOnPropertyChange('href', $aValue);
    }
}
