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
 * @property string $download Reflects the download HTML attribute, which indicates that the linked resource should be
 *                            downloaded rather than displayed in the browser. The value is the prefered name of the
 *                            file to be saved to disk. While there are no restrictions on the characters allowed, you
 *                            must take into consideration disallowed characters in file names on most operating
 *                            systems.
 * @property string $hrefLang Reflects the hrefLang HTML attribute, indicating the language of the linked resource.
 * @property string $ping     Reflects the ping HTML attribute. A notification will be sent to all the URLs contained
 *                            within this property if the user clicks on this link.
 * @property string $rel      Reflects the rel HTML attribute, which specifies the relationship of the target object to
 *                            the linked object.
 * @property string $target   Reflects the target HTML attribute, which indicates where to display the linked resource.
 * @property string $type     Reflects the type HTML attribute, which indicates the MIME type of the linked resource.
 * @property \Rowbot\DOM\DOMTokenList $relList Reflects the rel HTML attribute as a list of tokens.
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
