<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Document;
use Rowbot\DOM\DOMTokenList;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\HTMLHyperlinkElementUtils;

/**
 * Represents the HTML anchor element <a>.
 *
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-a-element
 *
 * @property string $download Reflects the download HTML attribute, which indicates that the linked resource should be
 *                            downloaded rather than displayed in the browser. The value is the prefered name of the
 *                            file to be saved to disk. While there are no restrictions on the characters allowed, you
 *                            must take into consideration disallowed characters in file names on most operating
 *                            systems.
 * @property string $hash     Represents the fragment identifier, including the leading hash (#) mark, if one is
 *                            present, of the URL.
 * @property string $host     Represents the hostname and the port, if the port is not the default port, of the URL.
 * @property string $hostname Represents the hostname of the URL.
 * @property string $href     Reflects the href HTML attribute.
 * @property string $hrefLang Reflects the hrefLang HTML attribute, indicating the language of the linked resource.
 * @property string $password Represents the password specified in the URL.
 * @property string $pathname Represents the pathname of the URL, if any.
 * @property string $ping     Reflects the ping HTML attribute. A notification will be sent to all the URLs contained
 *                            within this property if the user clicks on this link.
 * @property string $port     Represents the port, if any, of the URL.
 * @property string $protocol Represents the protocol, including the trailing colon (:), of the URL.
 * @property string $rel      Reflects the rel HTML attribute, which specifies the relationship of the target object to
 *                            the linked object.
 * @property string $search   Represents the query string, including the leading question mark (?), if any, of the URL.
 * @property string $target   Reflects the target HTML attribute, which indicates where to display the linked resource.
 * @property string $type     Reflects the type HTML attribute, which indicates the MIME type of the linked resource.
 * @property string $username Represents the username specified, if any, of theURL.
 * @property string $text
 * @property \Rowbot\DOM\DOMTokenList $relList Reflects the rel HTML attribute as a list of tokens.
 *
 * @property-read string                   $origin  Represents the URL's origin which is composed of the scheme, domain,
 *                                                  and port.
 */
class HTMLAnchorElement extends HTMLElement
{
    use HTMLHyperlinkElementUtils;

    /**
     * @var \Rowbot\DOM\DOMTokenList
     */
    private $relList;

    public function __construct(Document $document, string $localName, ?string $namespace, ?string $prefix = null)
    {
        parent::__construct($document, $localName, $namespace, $prefix);

        $this->attributeList->observe($this);
        $this->setURL();
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'download':
                return $this->reflectStringAttributeValue($name);

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
                return $this->reflectStringAttributeValue($name);

            case 'port':
                return $this->getPort();

            case 'protocol':
                return $this->getProtocol();

            case 'rel':
                return $this->reflectStringAttributeValue($name);

            case 'relList':
                return $this->getRelList();

            case 'search':
                return $this->getSearch();

            case 'target':
                return $this->reflectStringAttributeValue($name);

            case 'text':
                return $this->getTextContent();

            case 'type':
                return $this->reflectStringAttributeValue($name);

            case 'username':
                return $this->getUsername();

            default:
                return parent::__get($name);
        }
    }

    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'download':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'hash':
                $this->setHash((string) $value);

                break;

            case 'host':
                $this->setHost((string) $value);

                break;

            case 'hostname':
                $this->setHostname((string) $value);

                break;

            case 'href':
                $this->setHref((string) $value);

                break;

            case 'hrefLang':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'password':
                $this->setPassword((string) $value);

                break;

            case 'pathname':
                $this->setPathname((string) $value);

                break;

            case 'ping':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'port':
                $this->setPort((string) $value);

                break;

            case 'protocol':
                $this->setProtocol((string) $value);

                break;

            case 'rel':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'relList':
                $this->getRelList()->value = (string) $value;

                break;

            case 'search':
                $this->setSearch((string) $value);

                break;

            case 'target':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'text':
                $this->setTextContent((string) $value);

                break;

            case 'type':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'username':
                $this->setUsername((string) $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * @see \Rowbot\DOM\AttributeChangeObserver
     */
    public function onAttributeChanged(
        Element $element,
        string $localName,
        ?string $oldValue,
        ?string $value,
        ?string $namespace
    ): void {
        if ($localName === 'href' && $namespace === null) {
            $this->setURL();

            return;
        }

        parent::onAttributeChanged($element, $localName, $oldValue, $value, $namespace);
    }

    protected function __clone()
    {
        parent::__clone();

        $this->relList = null;

        if ($this->url !== null) {
            $this->url = clone $this->url;
        }
    }

    private function getRelList(): DOMTokenList
    {
        if ($this->relList === null) {
            $this->relList = new DOMTokenList($this, 'rel');
        }

        return $this->relList;
    }
}
