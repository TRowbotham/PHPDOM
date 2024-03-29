<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Document;
use Rowbot\DOM\DOMTokenList;

/**
 * Represents the HTML <link> element.
 *
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-link-element
 */
class HTMLLinkElement extends HTMLElement
{
    private ?DOMTokenList $_relList;

    private ?DOMTokenList $_sizes;

    public function __construct(Document $document, string $localName, ?string $namespace, ?string $prefix = null)
    {
        parent::__construct($document, $localName, $namespace, $prefix);

        $this->_relList = null;
        $this->_sizes = null;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-link-href
     */
    public string $href {
        get => $this->reflectStringAttributeValue('href');
        set {
            $this->attributeList->setAttrValue('href', $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-link-crossorigin
     */
    public ?string $crossOrigin {
        get => $this->reflectEnumeratedStringAttributeValue(
            'crossorigin',
            'anonymous',
            'no-cors',
            self::CORS_STATE_MAP
        );
        set {
            $this->attributeList->setAttrValue('crossorigin', (string) $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-link-rel
     */
    public string $rel {
        get => $this->reflectStringAttributeValue('rel');
        set {
            $this->attributeList->setAttrValue('rel', $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-link-rellist
     */
    public DOMTokenList $relList {
        get => $this->getRelList();
        set(DOMTokenList|string $value) {
            $this->getRelList()->value = (string) $value;
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-link-media
     */
    public string $media {
        get => $this->reflectStringAttributeValue('media');
        set {
            $this->attributeList->setAttrValue('media', $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-link-hreflang
     */
    public string $hreflang {
        get => $this->reflectStringAttributeValue('hrefLang');
        set {
            $this->attributeList->setAttrValue('hrefLang', $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-link-type
     */
    public string $type {
        get => $this->reflectStringAttributeValue('type');
        set {
            $this->attributeList->setAttrValue('type', $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-link-sizes
     */
    public DOMTokenList $sizes {
        get => $this->getSizes();
        set(DOMTokenList|string $value) {
            $this->getSizes()->value = (string) $value;
        }
    }

    private function getRelList(): DOMTokenList
    {
        return $this->_relList ??= new DOMTokenList($this, $this->dispatcher, 'rel');
    }

    private function getSizes(): DOMTokenList
    {
        return $this->_sizes ??= new DOMTokenList($this, $this->dispatcher, 'sizes');
    }

    protected function __clone()
    {
        parent::__clone();

        $this->_relList = null;
        $this->_sizes = null;
    }
}
