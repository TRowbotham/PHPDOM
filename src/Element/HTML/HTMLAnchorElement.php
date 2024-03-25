<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Document;
use Rowbot\DOM\DOMTokenList;
use Rowbot\DOM\DynamicProperty\Getter;
use Rowbot\DOM\DynamicProperty\Setter;
use Rowbot\DOM\Element\HTMLHyperlinkElementUtils;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\Utils;

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
 * @property \Rowbot\DOM\DOMTokenList $relList Reflects the rel HTML attribute as a list of tokens.
 *
 * @property-read string                   $origin  Represents the URL's origin which is composed of the scheme, domain,
 *                                                  and port.
 */
class HTMLAnchorElement extends HTMLElement
{
    use HTMLHyperlinkElementUtils;

    public string $text {
        get => $this->textContent;
        set {
            $this->textContent = $value;
        }
    }

    private ?DOMTokenList $relList;

    public function __construct(Document $document, string $localName, ?string $namespace, ?string $prefix = null)
    {
        parent::__construct($document, $localName, $namespace, $prefix);

        $this->dispatcher->addListener('attribute.changed', $this->onHrefAttributeChanged(...));
        $this->relList = null;
        $this->setURL();
    }

    #[Getter('download')]
    private function getDownload(): string
    {
        return $this->reflectStringAttributeValue('download');
    }

    #[Getter('hrefLang')]
    private function getHrefLang(): string
    {
        return $this->reflectStringAttributeValue('hreflang');
    }

    #[Getter('ping')]
    private function getPing(): string
    {
        return $this->reflectStringAttributeValue('ping');
    }

    #[Getter('rel')]
    private function getRel(): string
    {
        return $this->reflectStringAttributeValue('rel');
    }

    #[Getter('target')]
    private function getTarget(): string
    {
        return $this->reflectStringAttributeValue('target');
    }

    #[Getter('type')]
    private function getType(): string
    {
        return $this->reflectStringAttributeValue('type');
    }

    #[Getter('relList')]
    private function getRelList(): DOMTokenList
    {
        return $this->relList ??= new DOMTokenList($this, $this->dispatcher, 'rel');
    }

    #[Setter('hrefLang')]
    private function setHrefLang(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->attributeList->setAttrValue('hrefLang', (string) $value);
    }

    #[Setter('ping')]
    private function setPing(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->attributeList->setAttrValue('ping', (string) $value);
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

    #[Setter('target')]
    private function setTarget(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->attributeList->setAttrValue('target', (string) $value);
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

        $this->dispatcher->addListener('attribute.changed', $this->onHrefAttributeChanged(...));
        $this->relList = null;

        if ($this->url !== null) {
            $this->url = clone $this->url;
        }
    }
}
