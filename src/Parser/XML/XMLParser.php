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
        $document = new Document();
        $parser = new XMLParser($document);
        $parser->inputStream->append($input);

        try {
            $parser->run();
        } catch (Throwable $e) {
            throw new SyntaxError('', $e);
        }

        $docElement = $document->documentElement;

        if (
            $docElement !== null
            && ($docElement->previousSibling !== null || $docElement->nextSibling !== null)
        ) {
            throw new SyntaxError();
        }

        return $document->childNodes;
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
