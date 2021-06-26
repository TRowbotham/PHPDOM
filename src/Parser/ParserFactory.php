<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser;

use Rowbot\DOM\DocumentFragment;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Parser\HTML\HTMLParser;
use Rowbot\DOM\Parser\XML\XMLParser;

final class ParserFactory
{
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-fragment-parsing-algorithm
     */
    public static function parseFragment(string $markup, Element $contextElement): DocumentFragment
    {
        $ownerDocument = $contextElement->getNodeDocument();

        if ($ownerDocument->isHTMLDocument()) {
            $newChildren = HTMLParser::parseHTMLFragment(
                $markup,
                $contextElement
            );
        } else {
            $newChildren = XMLParser::parseXMLFragment(
                $markup,
                $contextElement
            );
        }

        $fragment = $ownerDocument->createDocumentFragment();

        foreach ($newChildren as $child) {
            $fragment->appendChild($child);
        }

        return $fragment;
    }
}
