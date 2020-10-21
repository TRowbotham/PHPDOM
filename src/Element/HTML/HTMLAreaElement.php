<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Document;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\HTMLHyperlinkElementUtils;

/**
 * @see https://html.spec.whatwg.org/multipage/embedded-content.html#the-area-element
 *
 * @property string $hash     Represents the fragment identifier, including the leading hash (#) mark, if one is
 *                            present, of the URL.
 * @property string $host     Represents the hostname and the port, if the port is not the default port, of the URL.
 * @property string $hostname Represents the hostname of the URL.
 * @property string $href     Reflects the href HTML attribute.
 * @property string $password Represents the password specified in the URL.
 * @property string $pathname Represents the pathname of the URL, if any.
 * @property string $port     Represents the port, if any, of the URL.
 * @property string $protocol Represents the protocol, including the trailing colon (:), of the URL.
 * @property string $search   Represents the query string, including the leading question mark (?), if any, of the URL.
 * @property string $username Represents the username specified, if any, of theURL.
 *
 * @property-read string $origin Represents the URL's origin which is composed of the scheme, domain, and port.
 */
class HTMLAreaElement extends HTMLElement
{
    use HTMLHyperlinkElementUtils;

    public function __construct(Document $document)
    {
        parent::__construct($document);

        $this->setURL();
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'hash':
                return $this->getHash();

            case 'host':
                return $this->getHost();

            case 'hostname':
                return $this->getHostname();

            case 'href':
                return $this->getHref();

            case 'origin':
                return $this->getOrigin();

            case 'password':
                return $this->getPassword();

            case 'pathname':
                return $this->getPathname();

            case 'port':
                return $this->getPort();

            case 'protocol':
                return $this->getProtocol();

            case 'search':
                return $this->getSearch();

            case 'username':
                return $this->getUsername();

            default:
                return parent::__get($name);
        }
    }

    public function __set(string $name, $value): void
    {
        switch ($name) {
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

            case 'password':
                $this->setPassword((string) $value);

                break;

            case 'pathname':
                $this->setPathname((string) $value);

                break;

            case 'port':
                $this->setPort((string) $value);

                break;

            case 'protocol':
                $this->setProtocol((string) $value);

                break;

            case 'search':
                $this->setSearch((string) $value);

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
}
