<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Document;
use Rowbot\DOM\DOMTokenList;
use Rowbot\DOM\DynamicProperty\Getter;
use Rowbot\DOM\DynamicProperty\Setter;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\Utils;

/**
 * Represents the HTML <link> element.
 *
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-link-element
 *
 * @property string $crossOrigin               Reflects the HTML crossorigin attribute and instructs how crossorigin
 *                                             requests should be handled for this particular resource.
 * @property string $href                      Reflects the HTML href attribute and represents the location of the
 *                                             linked resource.
 * @property string $hrefLang                  Reflects the HTML hreflang attribute, which indicates the language of the
 *                                             linked resource.
 * @property string $media                     Reflects the HTML media attribute. This accepts a valid media query to
 *                                             instruct the browser on when this resource should apply to the document.
 * @property string $rel                       Reflects the HTML rel attribute, which indicates the relationship between
 *                                             the document and the linked resource.
 * @property \Rowbot\DOM\DOMTokenList $sizes   Reflects the HTML sizes attribute as a list of tokens.
 * @property string $type                      Reflects the HTML type attribute, which hints at the linked resource's
 *                                             MIME type.
 * @property \Rowbot\DOM\DOMTokenList $relList Reflects the HTML rel attribute as a list of tokens.
 */
class HTMLLinkElement extends HTMLElement
{
    private ?DOMTokenList $relList;

    private ?DOMTokenList $sizes;

    public function __construct(Document $document, string $localName, ?string $namespace, ?string $prefix = null)
    {
        parent::__construct($document, $localName, $namespace, $prefix);

        $this->relList = null;
        $this->sizes = null;
    }

    #[Getter('crossOrigin')]
    private function getCrossOrigin(): string
    {
        return $this->reflectEnumeratedStringAttributeValue(
            'crossorigin',
            'anonymous',
            'no-cors',
            self::CORS_STATE_MAP
        );
    }

    #[Getter('href')]
    private function getHref(): string
    {
        return $this->reflectStringAttributeValue('href');
    }

    #[Getter('hrefLang')]
    private function getHrefLang(): string
    {
        return $this->reflectStringAttributeValue('hrefLang');
    }

    #[Getter('media')]
    private function getMedia(): string
    {
        return $this->reflectStringAttributeValue('media');
    }

    #[Getter('rel')]
    private function getRel(): string
    {
        return $this->reflectStringAttributeValue('rel');
    }

    #[Getter('relList')]
    private function getRelList(): DOMTokenList
    {
        return $this->relList ??= new DOMTokenList($this, $this->dispatcher, 'rel');
    }

    #[Getter('sizes')]
    private function getSizes(): DOMTokenList
    {
        return $this->sizes ??= new DOMTokenList($this, $this->dispatcher, 'sizes');
    }

    #[Getter('type')]
    private function getType(): string
    {
        return $this->reflectStringAttributeValue('type');
    }

    #[Setter('crossOrigin')]
    private function setCrossOrigin(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->attributeList->setAttrValue('crossorigin', (string) $value);
    }

    #[Setter('href')]
    private function setHref(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->attributeList->setAttrValue('href', (string) $value);
    }

    #[Setter('hrefLang')]
    private function setHrefLang(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->attributeList->setAttrValue('hrefLang', (string) $value);
    }

    #[Setter('media')]
    private function setMedia(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->attributeList->setAttrValue('media', (string) $value);
    }

    #[Setter('rel')]
    private function setRel(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->attributeList->setAttrValue('rel', (string) $value);
    }

    #[Setter('relList')]
    private function setRelList(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->getRelList()->value = (string) $value;
    }

    #[Setter('sizes')]
    private function setSizes(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->getSizes()->value = (string) $value;
    }

    #[Setter('type')]
    private function setType(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->attributeList->setAttrValue('type', (string) $value);
    }

    protected function __clone()
    {
        parent::__clone();

        $this->relList = null;
        $this->sizes = null;
    }
}
