<?php
namespace phpjs\parser;

use phpjs\HTMLDocument;
use phpjs\elements\Element;
use phpjs\exceptions\DOMException;
use phpjs\exceptions\InvalidStateError;
use phpjs\Namespaces;
use phpjs\Node;
use phpjs\parser\html\HTMLParser;
use phpjs\parser\xml\XMLParser;

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
        $ownerDocument = $aContextElement->ownerDocument;

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

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-fragment-serializing-algorithm
     *
     * @param Node $aNode              [description]
     *
     * @param bool $aRequireWellFormed [description]
     *
     * @return string
     */
    public static function serializeFragment(Node $aNode, $aRequireWellFormed)
    {
        $contextDocument = $aNode->ownerDocument;

        if ($contextDocument instanceof HTMLDocument) {
            return HTMLParser::serializeHTMLFragment($aNode);
        }

        return XMLParser::serializeAsXML($aNode, $aRequireWellFormed);
    }
}
