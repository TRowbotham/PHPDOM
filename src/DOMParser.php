<?php
namespace phpjs;

use phpjs\elements\ElementFactory;
use phpjs\parser\html\HTMLParser;

/**
 * @see https://w3c.github.io/DOM-Parsing/#the-domparser-interface
 */
class DOMParser
{
    public function __construct()
    {
    }

    /**
     * This takes a string of markup text and parses it, returning a Document
     * object containing DOM tree that was created from the provided markup
     * string. In any case, the returned Document's content type must be the
     * type argument. Additionally, the Document must have a URL value equal to
     * the URL of the active document, and a location value of null.
     *
     * NOTE: The returned Document's encoding is the default, UTF-8.
     *
     * @see https://w3c.github.io/DOM-Parsing/#dom-domparser-parsefromstring
     *
     * @param string $aStr A string of markup consiting of unicode characters.
     *
     * @param string $aType The MIME type of the markup string. Valid types are:
     *     - "text/html"
     *     - "text/xml"
     *     - "application/xml"
     *     - "application/xhtml+xml"
     *     - "image/svg+xml"
     *
     * @return Document
     */
    public function parseFromString($aStr, $aType)
    {
        switch ($aType) {
            case 'text/html':
                // Parse str with an HTML parser, and return the newly
                // created Document. The scripting flag must be set to
                // "disabled".
                //
                // NOTE: meta elements are not taken into account for the
                // encoding used, as a Unicode stream is passed into the parser.
                //
                // NOTE: script elements get marked unexecutable and the
                // contents of noscript get parsed as markup.
                $document = new HTMLDocument();
                $parser = new HTMLParser($document);
                $parser->preprocessInputStream($aStr);
                $parser->run();
                $document->_setContentType($aType);

                return $document;

            case 'text/xml':
            case 'application/xml':
            case 'application/xhtml+xml':
            case 'image/svg+xml':
                $parserError = false;
                $document = new Document();
                $document->_setContentType($aType);

                try {
                    // Parse str with a namespace-enabled XML parser.
                    // NOTE: For all XHTML script elements parsed using the XML
                    // parser, the equivalent of the scripting flag must be set
                    // to "disabled".
                    $parser = new XMLParser($document);
                    $parser->preprocessInputStream($aStr);
                    $parser->run();
                } catch (Exception $e) {
                    $parserError = true;
                }

                // If the previous step didn't return an error, return the newly
                // created Document.
                if (!$parserError) {
                    return $document;
                }

                // Let document be a newly-created XML Document.
                // NOTE: The document will use the Document interface rather
                // than the XMLDocument interface.
                $document = new Document();
                $document->_setContentType($aType);

                // Let root be a new Element, with its local name set to
                // "parsererror" and its namespace set to
                // "http://www.mozilla.org/newlayout/xml/parsererror.xml".
                $root = ElementFactory::create(
                    $document,
                    'parsererror',
                    'http://www.mozilla.org/newlayout/xml/parsererror.xml'
                );

                // NOTE: At this point user agents may append nodes to root, for
                // example to describe the nature of the error.

                // Append root to document.
                $document->appendChild($root);

                // Return the value of document.
                return $document;
        }
    }
}
