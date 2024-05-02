<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Document;
use Rowbot\DOM\Element\HTMLHyperlinkElementUtils;

/**
 * @see https://html.spec.whatwg.org/multipage/embedded-content.html#the-area-element
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
