<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\Parser\HTML\HTMLParser;
use Rowbot\DOM\Parser\XML\XMLParser;
use Rowbot\URL\BasicURLParser;
use Rowbot\URL\String\Utf8String;
use Throwable;

use function assert;
use function in_array;

class DocumentBuilder
{
    private $enableScripting;
    private $contentType;
    private $url;

    private const VALID_CONTENT_TYPES = [
        'text/html',
        'text/xml',
        'application/xml',
        'application/xhtml+xml',
        'image/svg+xml',
    ];

    protected function __construct()
    {
        $this->contentType = null;
        $this->url = null;
        $this->enableScripting = false;
    }

    /**
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Sets the base URL of the document. This is used for resolving links in tags such as `<a href="/index.php"></a>`
     * and for resolving any links specified by `<base>` elements in the document. If not set, the document will default
     * to the "about:blank" URL. This must be an absolute URL. If the URL is not valid, a `TypeError` will be thrown.
     *
     * @return $this
     */
    public function setDocumentUrl(string $url)
    {
        $parser = new BasicURLParser();
        $record = $parser->parse(new Utf8String($url));

        if ($record === false) {
            throw new TypeError('Could not parse the given URL.');
        }

        $this->url = $record;

        return $this;
    }

    /**
     * Sets the content type of the document. If the given content type is invalid, a `TypeError` will be thrown. This
     * will determine the type of document returned as well as what parser to use. The content type can be one of the
     * following:
     *
     * * 'text/html'
     * * 'text/xml'
     * * 'application/xml'
     * * 'application/xhtml+xml'
     * * 'image/svg+xml'
     *
     * @return $this
     */
    public function setContentType(string $contentType)
    {
        if (!in_array($contentType, self::VALID_CONTENT_TYPES, true)) {
            throw new TypeError('Invalid content type.');
        }

        $this->contentType = $contentType;

        return $this;
    }

    /**
     * Enables scripting emulation. Enabling this does not cause any scripts to be executed. This affects how
     * the parser and serializer handle `<noscript>` tags. If scripting emulation is enabled, then their content
     * will be seen as plain text to the DOM. If emulation is disabled, which is the default, their content
     * will be parsed as part of the DOM.
     *
     * @return $this
     */
    public function emulateScripting(bool $enable)
    {
        $this->enableScripting = $enable;

        return $this;
    }

    /**
     * Creates a new document, from a string, using the settings defined by the DocumentBuilder.
     */
    public function createFromString(string $input): Document
    {
        $env = $this->createEnvironment();

        if ($this->contentType === 'text/html') {
            return $this->parseHTML($input, $env);
        }

        if (
            $this->contentType === 'text/xml'
            || $this->contentType === 'application/xml'
            || $this->contentType === 'application/xhtml+xml'
            || $this->contentType === 'image/svg+xml'
        ) {
            return $this->parseXML($input, $env);
        }
    }

    public function createEmptyDocument(): Document
    {
        $env = $this->createEnvironment();

        if ($this->contentType === 'text/html') {
            return new HTMLDocument($env);
        }

        if (
            $this->contentType === 'text/xml'
            || $this->contentType === 'application/xml'
            || $this->contentType === 'application/xhtml+xml'
            || $this->contentType === 'image/svg+xml'
        ) {
            return new Document($env);
        }
    }

    protected function createEnvironment(): Environment
    {
        if ($this->contentType === null) {
            throw new TypeError('You must specify the content type.');
        }

        if ($this->url === null) {
            $parser = new BasicURLParser();
            $record = $parser->parse(new Utf8String('about:blank'));
            assert($record !== false);
            $this->url = $record;
        }

        $env = new Environment($this->url, $this->contentType);
        $env->setScriptingEnabled($this->enableScripting);

        return $env;
    }

    protected function parseHTML(string $input, Environment $env): Document
    {
        $doc = new HTMLDocument($env);
        $parser = new HTMLParser($doc);
        $parser->preprocessInputStream($input);
        $parser->run();

        return $doc;
    }

    protected function parseXML(string $input, Environment $env): Document
    {
        $parserError = false;
        $document = new Document($env);

        try {
            // Parse str with a namespace-enabled XML parser.
            // NOTE: For all XHTML script elements parsed using the XML
            // parser, the equivalent of the scripting flag must be set
            // to "disabled".
            $parser = new XMLParser($document);
            $parser->preprocessInputStream($input);
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
        $document = new Document($env);

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
