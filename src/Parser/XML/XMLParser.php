<?php
namespace Rowbot\DOM\Parser\XML;

use Exception;
use Rowbot\DOM\Document;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Parser\Parser;

class XMLParser extends Parser
{
    private $document;

    public function __construct(Document $aDocument)
    {
        parent::__construct();

        $this->document = $aDocument;
    }

    public function abort()
    {
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/xhtml.html#xml-fragment-parsing-algorithm
     * @param string       $aInput          [description]
     * @param Element|null $aContextElement [description]
     */
    public static function parseXMLFragment(
        $aInput,
        Element $aContextElement = null
    ) {
        $document = new Document();
        $parser = new XMLParser($document);
        $parser->inputStream->append($aInput);

        try {
            $parser->run();
        } catch (Exception $e) {
            throw new SyntaxError('', 0, $e);
        }
    }

    public function preprocessInputStream($aInput)
    {
        $this->inputStream->append($aInput);
    }
}
