<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\DOMTokenList;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\HTMLHyperlinkElementUtils;

/**
 * Represents the HTML anchor element <a>.
 *
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-a-element
 *
 * @property string $download Reflects the download HTML attribute, which
 *     indicates that the linked resource should be downloaded rather than
 *     displayed in the browser.  The value is the prefered name of the file to
 *     be saved to disk.  While there are no restrictions on the characters
 *     allowed, you must take into consideration disallowed characters in file
 *     names on most operating systems.
 *
 * @property string $hash Represents the fragment identifier, including the
 *     leading hash (#) mark, if one is present, of the URL.
 *
 * @property string $host Represents the hostname and the port, if the port is
 *     not the default port, of the URL.
 *
 * @property string $hostname Represents the hostname of the URL.
 *
 * @property string $href Reflects the href HTML attribute.
 *
 * @property string $hrefLang Reflects the hrefLang HTML attribute, indicating
 *     the language of the linked resource.
 *
 * @property string $password Represents the password specified in the URL.
 *
 * @property string $pathname Represents the pathname of the URL, if any.
 *
 * @property string $ping Reflects the ping HTML attribute.  A notification will
 *     be sent to all the URLs contained within this property if the user clicks
 *     on this link.
 *
 * @property string $port Represents the port, if any, of the URL.
 *
 * @property string $protocol Represents the protocol, including the trailing
 *     colon (:), of the URL.
 *
 * @property string $rel Reflects the rel HTML attribute, which specifies the
 *     relationship of the target object to the linked object.
 *
 * @property string $search Represents the query string, including the leading
 *     question mark (?), if any, of the URL.
 *
 * @property string $target Reflects the target HTML attribute, which indicates
 *     where to display the linked resource.
 *
 * @property string $type Reflects the type HTML attribute, which indicates the
 *     MIME type of the linked resource.
 *
 * @property string $username Represents the username specified, if any, of the
 *     URL.
 *
 * @property-read string $origin Represents the URL's origin which is composed
 *     of the scheme, domain, and port.
 *
 * @property-read DOMTokenList $relList Reflects the rel HTML attribute as a
 *     list of tokens.
 */
class HTMLAnchorElement extends HTMLElement
{
    use HTMLHyperlinkElementUtils;

    private $mPing;
    private $mRelList;

    protected function __construct()
    {
        parent::__construct();

        $this->mPing = new DOMTokenList($this, 'ping');
        $this->mRelList = new DOMTokenList($this, 'rel');
        $this->mAttributesList->observe($this);
        $this->setURL();
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'download':
                return $this->reflectStringAttributeValue($aName);

            case 'hash':
                return $this->getHash();

            case 'host':
                return $this->getHost();

            case 'hostname':
                return $this->getHostname();

            case 'href':
                return $this->getHref();

            case 'hrefLang':
                return $this->reflectStringAttributeValue('hreflang');

            case 'origin':
                return $this->getOrigin();

            case 'password':
                return $this->getPassword();

            case 'pathname':
                return $this->getPathname();

            case 'ping':
                return $this->mPing;

            case 'port':
                return $this->getPort();

            case 'protocol':
                return $this->getProtocol();

            case 'rel':
                return $this->reflectStringAttributeValue($aName);

            case 'relList':
                return $this->mRelList;

            case 'search':
                return $this->getSearch();

            case 'target':
                return $this->reflectStringAttributeValue($aName);

            case 'text':
                return $this->getTextContent();

            case 'type':
                return $this->reflectStringAttributeValue($aName);

            case 'username':
                return $this->getUsername();

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'download':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'hash':
                $this->setHash($aValue);

                break;

            case 'host':
                $this->setHost($aValue);

                break;

            case 'hostname':
                $this->setHostname($aValue);

                break;

            case 'href':
                $this->setHref($aValue);

                break;

            case 'hrefLang':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'password':
                $this->setPassword($aValue);

                break;

            case 'pathname':
                $this->setPathname($aValue);

                break;

            case 'ping':
                $this->mPing->value = $aValue;

                break;

            case 'port':
                $this->setPort($aValue);

                break;

            case 'protocol':
                $this->setProtocol($aValue);

                break;

            case 'rel':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'search':
                $this->setSearch($aValue);

                break;

            case 'target':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'text':
                $this->setTextContent($aValue);

                break;

            case 'type':
                $this->mAttributesList->setAttrValue($aName, $aValue);

                break;

            case 'username':
                $this->setUsername($aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    /**
     * @see AttributeChangeObserver
     */
    public function onAttributeChanged(
        Element $aElement,
        $aLocalName,
        $aOldValue,
        $aValue,
        $aNamespace
    ) {
        if ($aLocalName === 'href' && $aNamespace === null) {
            $this->setURL();
        } else {
            parent::onAttributeChanged(
                $aElement,
                $aLocalName,
                $aOldValue,
                $aValue,
                $aNamespace
            );
        }
    }
}
