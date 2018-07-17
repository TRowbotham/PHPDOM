<?php
namespace Rowbot\DOM\Parser;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Parser\HTML\HTMLParser;
use Rowbot\DOM\Parser\XML\XMLParser;

final class ParserFactory
{
    /**
     * Constructor.
     *
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * @param string $markup
     *
     * @return \Rowbot\DOM\HTMLDocument
     */
    public static function parseHTMLDocument(string $markup)
    {
        $doc = new HTMLDocument();
        $parser = new HTMLParser($doc);
        $parser->preprocessInputStream($markup);
        $parser->run();

        return $doc;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-fragment-parsing-algorithm
     *
     * @param string                      $markup
     * @param \Rowbot\DOM\Element\Element $contextElement
     *
     * @return \Rowbot\DOM\DocumentFragment
     */
    public static function parseFragment(
        string $markup,
        Element $contextElement
    ) {
        $ownerDocument = $contextElement->getNodeDocument();

        if ($ownerDocument instanceof HTMLDocument) {
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
