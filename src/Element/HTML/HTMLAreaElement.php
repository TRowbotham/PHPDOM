<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Document;
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

    public function __construct(Document $document, string $localName, ?string $namespace, ?string $prefix = null)
    {
        parent::__construct($document, $localName, $namespace, $prefix);

        $this->dispatcher->addListener('attribute.changed', $this->onHrefAttributeChanged(...));
        $this->setURL();
    }

    protected function __clone()
    {
        parent::__clone();

        $this->dispatcher->addListener('attribute.changed', $this->onHrefAttributeChanged(...));

        if ($this->url !== null) {
            $this->url = clone $this->url;
        }
    }
}
