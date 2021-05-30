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
     * Sets the URL of the document, so that any links can be resolved against it.
     *
     * @return static
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
     * Sets the document's content type, which will determine how it is parsed.
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
     * Passing true will emulate an environment that has scripting enabled. No actual scripts will
     * be executed. This mostly affects HTML parsing and serialization around tags such as <noscript>.
     * If emulation is enabled, tags like <noscript> will not be part of the DOM, however, if disabled,
     * <noscript> tags and their descendants will be part of the DOM. By default, scripting emulation is disabled.
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
