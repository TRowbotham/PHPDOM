<?php
namespace Rowbot\DOM\Parser;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\DOMException;
use Rowbot\DOM\Exception\InvalidStateError;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Parser\HTML\HTMLParser;
use Rowbot\DOM\Parser\XML\XMLParser;

abstract class ParserFactory
{
    public static function parseHTMLDocument($aMarkup)
    {
        $doc = new HTMLDocument();
        $parser = new HTMLParser($doc);
        $parser->preprocessInputStream($aMarkup);
        $parser->run();

        return $doc;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-fragment-parsing-algorithm
     *
     * @param string $aMarkup [description]
     *
     * @param Element $aContextElement [description]
     *
     * @return DocumentFragment
     */
    public static function parseFragment($aMarkup, Element $aContextElement)
    {
        $ownerDocument = $aContextElement->getNodeDocument();

        if ($ownerDocument instanceof HTMLDocument) {
            $newChildren = HTMLParser::parseHTMLFragment(
                $aMarkup,
                $aContextElement
            );
        } else {
            $newChildren = XMLParser::parseXMLFragment(
                $aMarkup,
                $aContextElement
            );
        }

        $fragment = $ownerDocument->createDocumentFragment();

        foreach ($newChildren as $child) {
            $fragment->appendChild($child);
        }

        return $fragment;
    }
}
