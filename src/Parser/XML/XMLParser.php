<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\XML;

use Rowbot\DOM\Document;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\SyntaxError;
use Rowbot\DOM\NodeList;
use Rowbot\DOM\Parser\Parser;
use Throwable;

class XMLParser extends Parser
{
    /**
     * @var \Rowbot\DOM\Document
     */
    private $document;

    public function __construct(Document $document)
    {
        parent::__construct();

        $this->document = $document;
    }

    public function abort(): void
    {
    }

    /**
     * Parses an XML string fragment.
     *
     * @see https://html.spec.whatwg.org/multipage/xhtml.html#xml-fragment-parsing-algorithm
     *
     * @throws \Rowbot\DOM\Exception\SyntaxError
     */
    public static function parseXMLFragment(string $input, Element $contextElement): NodeList
    {
        // 1. Create a new XML parser.
        $document = new Document();
        $parser = new XMLParser($document);
        $parser->inputStream->append($input);

        // 2. Feed the parser just created the string corresponding to the start tag of the context element, declaring
        // all the namespace prefixes that are in scope on that element in the DOM, as well as declaring the default
        // namespace (if any) that is in scope on that element in the DOM.

        // 3. Feed the parser just created the string input.

        // 4. Feed the parser just created the string corresponding to the end tag of the context element.

        // A namespace prefix is in scope if the DOM lookupNamespaceURI() method on the element would return a non-null
        // value for that prefix.

        // The default namespace is the namespace for which the DOM isDefaultNamespace() method on the element would
        // return true.

        // Note: No DOCTYPE is passed to the parser, and therefore no external subset is referenced, and therefore no
        // entities will be recognized.
        $tagName = $contextElement->tagName;
        // TODO: Declare all the namespace prefixes that are in scope
        $parser->inputStream->append("<{$tagName}>{$input}</{$tagName}>");

        // 5. If there is an XML well-formedness or XML namespace well-formedness error, then throw a
        // "SyntaxError" DOMException.
        try {
            $parser->run();
        } catch (Throwable $e) {
            throw new SyntaxError('', $e);
        }

        // 6. If the document element of the resulting Document has any sibling nodes, then throw a
        // "SyntaxError" DOMException.
        $docElement = $document->documentElement;

        if ($docElement !== null && ($docElement->previousSibling !== null || $docElement->nextSibling !== null)) {
            throw new SyntaxError();
        }

        // 7. Return the child nodes of the document element of the resulting Document, in tree order.
        return $docElement->childNodes;
    }

    public function preprocessInputStream(string $input): void
    {
        $this->inputStream->append($input);
    }

    /**
     * Executes the parsing steps.
     */
    public function run(): void
    {
    }
}
