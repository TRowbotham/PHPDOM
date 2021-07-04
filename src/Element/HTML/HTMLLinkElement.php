<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\DOMTokenList;

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
    /**
     * @var \Rowbot\DOM\DOMTokenList|null
     */
    private $relList;

    /**
     * @var \Rowbot\DOM\DOMTokenList|null
     */
    private $sizes;

    public function __get(string $name)
    {
        switch ($name) {
            case 'crossOrigin':
                return $this->reflectEnumeratedStringAttributeValue(
                    'crossorigin',
                    'anonymous',
                    'no-cors',
                    self::CORS_STATE_MAP
                );

            case 'href':
                return $this->reflectStringAttributeValue($name);

            case 'hrefLang':
                return $this->reflectStringAttributeValue('hreflang');

            case 'media':
                return $this->reflectStringAttributeValue($name);

            case 'rel':
                return $this->reflectStringAttributeValue($name);

            case 'relList':
                return $this->getRelList();

            case 'sizes':
                return $this->getSizes();

            case 'type':
                return $this->reflectStringAttributeValue($name);

            default:
                return parent::__get($name);
        }
    }

    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'crossOrigin':
                $this->attributeList->setAttrValue('crossorigin', (string) $value);

                break;

            case 'href':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'hrefLang':
                $this->attributeList->setAttrValue('hreflang', (string) $value);

                break;

            case 'media':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'rel':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'relList':
                $this->getRelList()->value = (string) $value;

                break;

            case 'sizes':
                $this->getSizes()->value = (string) $value;

                break;

            case 'type':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }

    protected function __clone()
    {
        parent::__clone();

        $this->relList = null;
        $this->sizes = null;
    }

    private function getRelList(): DOMTokenList
    {
        if ($this->relList === null) {
            $this->relList = new DOMTokenList($this, 'rel');
        }

        return $this->relList;
    }

    private function getSizes(): DOMTokenList
    {
        if ($this->sizes === null) {
            $this->sizes = new DOMTokenList($this, 'sizes');
        }

        return $this->sizes;
    }
}
