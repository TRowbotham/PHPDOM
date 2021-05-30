<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\Parser\HTML\HTMLParser;
use Rowbot\DOM\Parser\XML\XMLParser;
use Throwable;

/**
 * @see https://html.spec.whatwg.org/multipage/dynamic-markup-insertion.html#domparser
 */
final class DOMParser
{
    /**
     * This takes a string of markup text and parses it, returning a Document
     * object containing DOM tree that was created from the provided markup
     * string. In any case, the returned Document's content type must be the
     * type argument. Additionally, the Document must have a URL value equal to
     * the URL of the active document, and a location value of null.
     *
     * NOTE: The returned Document's encoding is the default, UTF-8.
     *
     * @see https://html.spec.whatwg.org/multipage/dynamic-markup-insertion.html#dom-domparser-parsefromstring
     *
     * @param string $str  A string of markup consiting of unicode characters.
     * @param string $type The MIME type of the markup string. Valid types are:
     *                         - "text/html"
     *                         - "text/xml"
     *                         - "application/xml"
     *                         - "application/xhtml+xml"
     *                         - "image/svg+xml"
     */
    public function parseFromString(string $str, string $type): Document
    {
        switch ($type) {
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
                $env = new Environment(null, $type);
                $env->setScriptingEnabled(false);
                $document = new HTMLDocument($env);
                $parser = new HTMLParser($document);
                $parser->preprocessInputStream($str);
                $parser->run();
                $document->setContentType($type);

                return $document;

            case 'text/xml':
            case 'application/xml':
            case 'application/xhtml+xml':
            case 'image/svg+xml':
                $parserError = false;
                $env = new Environment(null, $type);
                $env->setScriptingEnabled(false);
                $document = new Document($env);
                $document->setContentType($type);

                try {
                    // Parse str with a namespace-enabled XML parser.
                    // NOTE: For all XHTML script elements parsed using the XML
                    // parser, the equivalent of the scripting flag must be set
                    // to "disabled".
                    $parser = new XMLParser($document);
                    $parser->preprocessInputStream($str);
                    $parser->run();
                } catch (Throwable $e) {
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
                $document->setContentType($type);

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

        throw new TypeError(
            'The second paramter "$type" must be one of: "text/html", "text/xml", "application/xml"'
            . ', "application/xhtml+xml", or "image/svg+xml".'
        );
    }
}
