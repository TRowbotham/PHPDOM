<?php
namespace phpjs\elements\html;

use phpjs\Attr;
use phpjs\DOMTokenList;

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
    use \phpjs\elements\HTMLHyperlinkElementUtils;

    private $mPing;
    private $mRelList;

    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null)
    {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);

        $this->initHTMLHyperlinkElementUtils();
        $this->mPing = new DOMTokenList($this, 'ping');
        $this->mRelList = new DOMTokenList($this, 'rel');
    }

    public function __destruct()
    {
        $this->mPing = null;
        $this->mRelList = null;
        $this->HTMLHyperLinkElementUtilsDestructor();
        parent::__destruct();
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'download':
                return $this->reflectStringAttributeValue($aName);
            case 'hrefLang':
                return $this->reflectStringAttributeValue('hreflang');
            case 'ping':
                return $this->reflectStringAttributeValue($aName);
            case 'rel':
                return $this->reflectStringAttributeValue($aName);
            case 'relList':
                return $this->mRelList;
            case 'target':
                return $this->reflectStringAttributeValue($aName);
            case 'type':
                return $this->reflectStringAttributeValue($aName);
            default:
                $rv = $this->HTMLHyperlinkElementUtilsGetter($aName);

                if ($rv != 'HTMLHyperlinkElementUtilsGetter') {
                    return $rv;
                }

                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'download':

                break;

            case 'hrefLang':

                break;

            case 'ping':

                break;

            case 'rel':

                break;

            case 'target':

                break;

            case 'type':

                break;

            default:
                $this->HTMLHyperlinkElementUtilsSetter($aName, $aValue);
                parent::__set($aName, $aValue);
        }
    }

    public function attributeHookHandler($aHookType, Attr $aAttr)
    {
        switch ($aAttr->name) {
            case 'href':
                if ($aHookType == 'set') {
                    $resolvedURL = $this->resolveURL($aAttr->value);
                    $this->mUrl = $resolvedURL !== false ?
                        $resolvedURL['parsed_url'] : null;
                } elseif ($aHookType == 'removed') {
                    $this->mUrl = null;
                }

                break;

            case 'ping':
                $this->mPing->value = $aAttr->value;

                break;

            case 'rel':
                if ($aHookType == 'set') {
                    $value = $aAttr->value;

                    if (!empty($value)) {
                        $this->mRelList->appendTokens(
                            DOMTokenList::_parseOrderedSet($value)
                        );
                    }
                } elseif ($aHookType == 'removed') {
                    $this->mRelList->emptyList();
                }

                break;

            default:
                parent::attributeHookHandler($aHookType, $aAttr);
        }
    }
}
