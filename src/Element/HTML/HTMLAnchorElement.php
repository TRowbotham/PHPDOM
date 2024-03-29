<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Document;
use Rowbot\DOM\DOMTokenList;
use Rowbot\DOM\Element\HTMLHyperlinkElementUtils;

/**
 * Represents the HTML anchor element <a>.
 *
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-a-element
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
 * @property-read string                   $origin  Represents the URL's origin which is composed of the scheme, domain,
 *                                                  and port.
 */
class HTMLAnchorElement extends HTMLElement
{
    use HTMLHyperlinkElementUtils;

    /**
     * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#dom-a-target
     */
    public string $target {
        get => $this->reflectStringAttributeValue('target');
        set {
            $this->attributeList->setAttrValue('target', $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#dom-a-download
     */
    public string $download {
        get => $this->reflectStringAttributeValue('download');
        set {
            $this->attributeList->setAttrValue('download', $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#dom-a-ping
     */
    public string $ping {
        get => $this->reflectStringAttributeValue('ping');
        set {
            $this->attributeList->setAttrValue('ping', $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#dom-a-rel
     */
    public string $rel {
        get => $this->reflectStringAttributeValue('rel');
        set {
            $this->attributeList->setAttrValue('rel', $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#dom-a-rellist
     */
    public DOMTokenList $relList {
        get => $this->getRelList();
        set(DOMTokenList|string $value) {
            $this->getRelList()->value = (string) $value;
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#dom-a-hreflang
     */
    public string $hreflang {
        get => $this->reflectStringAttributeValue('hreflang');
        set {
            $this->attributeList->setAttrValue('hreflang', $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#dom-a-type
     */
    public string $type {
        get => $this->reflectStringAttributeValue('type');
        set {
            $this->attributeList->setAttrValue('type', $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#dom-a-text
     */
    public string $text {
        get => $this->textContent;
        set {
            $this->textContent = $value;
        }
    }

    private ?DOMTokenList $_relList;

    public function __construct(Document $document, string $localName, ?string $namespace, ?string $prefix = null)
    {
        parent::__construct($document, $localName, $namespace, $prefix);

        $this->dispatcher->addListener('attribute.changed', $this->onHrefAttributeChanged(...));
        $this->_relList = null;
        $this->setURL();
    }

    private function getRelList(): DOMTokenList
    {
        return $this->_relList ??= new DOMTokenList($this, $this->dispatcher, 'rel');
    }

    protected function __clone()
    {
        parent::__clone();

        $this->dispatcher->addListener('attribute.changed', $this->onHrefAttributeChanged(...));
        $this->_relList = null;

        if ($this->url !== null) {
            $this->url = clone $this->url;
        }
    }
}
