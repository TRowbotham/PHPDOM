<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Element\HTML\HTMLBaseElement;
use Rowbot\DOM\Element\HTML\HTMLHeadElement;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Event\Event;
use Rowbot\DOM\Event\EventFlags;
use Rowbot\DOM\Event\EventTarget;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\Exception\NotSupportedError;
use Rowbot\DOM\Parser\MarkupFactory;
use Rowbot\DOM\Support\Stringable;
use Rowbot\DOM\URL\URLParser;
use Rowbot\URL\URLRecord;

use function array_filter;
use function count;
use function in_array;
use function mb_strpos;
use function method_exists;
use function preg_match;
use function strtolower;

use const PHP_SAPI;

/**
 * @see https://dom.spec.whatwg.org/#interface-document
 * @see https://html.spec.whatwg.org/#document
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Document
 *
 * @property-read \Rowbot\DOM\DOMImplementation    $implementation
 * @property-read string                           $URL
 * @property-read string                           $documentURI
 * @property-read string                           $origin
 * @property-read string                           $compatMode
 * @property-read string                           $characterSet
 * @property-read string                           $contentType
 * @property-read \Rowbot\DOM\DocumentType|null    $doctype
 * @property-read \Rowbot\DOM\Element\Element|null $documentElement
 * @property-read string                           $readyState
 */
class Document extends Node implements NonElementParentNode, ParentNode, Stringable
{
    use GetElementsBy;
    use NonElementParentNodeTrait;
    use ParentNodeTrait;

    protected const INERT_TEMPLATE_DOCUMENT = 0x1;

    /**
     * @var string
     */
    protected $characterSet;

    /**
     * @var string
     */
    protected $contentType;

    /**
     * @var int
     */
    protected $flags;

    /**
     * @var static|null
     */
    protected $inertTemplateDocument;

    /**
     * @var int
     */
    protected $mode;

    /**
     * @var string
     */
    private $compatMode;

    /**
     * @var \Rowbot\DOM\DOMImplementation
     */
    private $implementation;

    /**
     * @var bool
     */
    private $isIframeSrcDoc;

    /**
     * @var bool
     */
    private $isHTMLDocument;

    /**
     * @var \Rowbot\DOM\NodeIterator[]
     */
    private static $nodeIteratorList = [];

    /**
     * @var \Rowbot\URL\URLRecord|null
     */
    private $url;

    /**
     * @var string
     */
    private $readyState;

    /**
     * @var int
     */
    private $source;

    public function __construct()
    {
        parent::__construct($this);

        $this->characterSet = 'UTF-8';
        $this->contentType = 'application/xml';
        $this->flags = 0;
        $this->implementation = new DOMImplementation($this);
        $this->isIframeSrcDoc = false;
        $this->inertTemplateDocument = null;
        $this->mode = DocumentMode::NO_QUIRKS;
        $this->nodeType = self::DOCUMENT_NODE;
        $this->url = null;

        // When a Document object is created, it must have its current document
        // readiness set to the string "loading" if the document is associated
        // with an HTML parser, an XML parser, or an XSLT processor, and to the
        // string "complete" otherwise.
        $this->readyState = DocumentReadyState::COMPLETE;

        $this->source = DocumentSource::NOT_FROM_PARSER;
    }

    public function __destruct()
    {
        // Filter out any NodeIterators where root's node document is this document.
        self::$nodeIteratorList = array_filter(
            self::$nodeIteratorList,
            function (NodeIterator $iter): bool {
                return $iter->root->nodeDocument !== $this;
            }
        );
        Range::prune($this);
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'characterSet':
            case 'charset':
            case 'inputEncoding':
                return $this->characterSet;

            case 'childElementCount':
                return $this->getChildElementCount();

            case 'children':
                return $this->getChildren();

            case 'contentType':
                return $this->contentType;

            case 'doctype':
                foreach ($this->childNodes as $child) {
                    if ($child instanceof DocumentType) {
                        return $child;
                    }
                }

                return null;

            case 'documentElement':
                return $this->getFirstElementChild();

            case 'documentURI':
            case 'URL':
                return $this->getURL()->serializeURL();

            case 'firstElementChild':
                return $this->getFirstElementChild();

            case 'implementation':
                return $this->implementation;

            case 'lastElementChild':
                return $this->getLastElementChild();

            case 'origin':
                return (string) $this->getURL()->getOrigin();

            case 'readyState':
                return $this->readyState;

            default:
                return parent::__get($name);
        }
    }

    public function cloneNodeInternal(Document $document = null, bool $cloneChildren = false): Node
    {
        $document = $document ?? $this->getNodeDocument();
        $copy = new static();
        $copy->characterSet = $this->characterSet;
        $copy->contentType = $this->contentType;
        $copy->mode = $this->mode;
        $this->postCloneNode($copy, $document, $cloneChildren);

        return $copy;
    }

    /**
     * Adopts the given Node and its subtree from a differnt Document allowing
     * the node to be used in this Document.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-adoptnode
     *
     * @throws \Rowbot\DOM\Exception\NotSupportedError
     * @throws \Rowbot\DOM\Exception\HierarchyRequestError
     */
    public function adoptNode(Node $node): Node
    {
        if ($node instanceof Document) {
            throw new NotSupportedError();
        }

        if ($node instanceof ShadowRoot) {
            throw new HierarchyRequestError();
        }

        $this->doAdoptNode($node);

        return $node;
    }

    /**
     * Creates a new Attr object that with the given name.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createattribute
     *
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError
     */
    public function createAttribute(string $localName): Attr
    {
        // If localName does not match the Name production in XML, then
        // throw an InvalidCharacterError.
        if (!preg_match(Namespaces::NAME_PRODUCTION, $localName)) {
            throw new InvalidCharacterError();
        }

        if ($this instanceof HTMLDocument) {
            $localName = Utils::toASCIILowercase($localName);
        }

        return new Attr($this, $localName, '');
    }

    /**
     * Creates a new Attr object with the given namespace and the name and
     * prefix for the given namespace.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createattributens
     *
     * @throws \Rowbot\DOM\Exception\NamespaceError
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError
     */
    public function createAttributeNS(?string $namespace, string $qualifiedName): Attr
    {
        [$namespace, $prefix, $localName] = Namespaces::validateAndExtract(
            $namespace,
            $qualifiedName
        );

        return new Attr($this, $localName, '', $namespace, $prefix);
    }

    /**
     * Creates a comment node.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createcomment
     */
    public function createComment(string $data): Comment
    {
        return new Comment($this, $data);
    }

    /**
     * Creates a document fragment node.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createdocumentfragment
     */
    public function createDocumentFragment(): DocumentFragment
    {
        return new DocumentFragment($this);
    }

    /**
     * Creates an Element with the specified tag name.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createelement
     *
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError
     */
    public function createElement(string $localName): Element
    {
        // If localName does not match the Name production, then throw an
        // InvalidCharacterError.
        if (!preg_match(Namespaces::NAME_PRODUCTION, $localName)) {
            throw new InvalidCharacterError();
        }

        // If the context object is an HTML document, let localName be converted
        // to ASCII lowercase.
        if ($this instanceof HTMLDocument) {
            $localName = Utils::toASCIILowercase($localName);
        }

        // Let namespace be the HTML namespace, if the context object’s content
        // type is "text/html" or "application/xhtml+xml", and null otherwise.
        switch ($this->contentType) {
            case 'text/html':
            case 'application/xhtml+xml':
                $namespace = Namespaces::HTML;

                break;

            default:
                $namespace = null;
        }

        $element = ElementFactory::create($this, $localName, $namespace, null);

        return $element;
    }

    /**
     * Creates an Element in a particular namespace.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createelementns
     *
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError
     * @throws \Rowbot\DOM\Exception\NamespaceError
     */
    public function createElementNS(?string $namespace, string $qualifiedName): Element
    {
        return ElementFactory::createNS($this, $namespace, $qualifiedName);
    }

    /**
     * Creates a new Event of the specified type and returns it.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createevent
     *
     * @throws \Rowbot\DOM\Exception\NotSupportedError
     */
    public function createEvent(string $interface): Event
    {
        $constructor = null;
        $interface = strtolower($interface);

        switch ($interface) {
            case 'customevent':
                $constructor = '\\Rowbot\\DOM\\Event\\CustomEvent';

                break;

            case 'event':
            case 'events':
            case 'htmlevents':
                $constructor = '\\Rowbot\\DOM\\Event\\Event';
        }

        if (!$constructor) {
            throw new NotSupportedError();
        }

        $event = new $constructor('');
        $event->unsetFlag(EventFlags::INITIALIZED);

        return $event;
    }

    /**
     * Returns a new NodeIterator object, which represents an iterator over the
     * members of a list of the nodes in a subtree of the DOM.
     *
     * @param \Rowbot\DOM\Node                     $root       The root node of the iterator object.
     * @param int                                  $whatToShow (optional) A bitmask of NodeFilter constants allowing the
     *                                                         user to filter for specific node types.
     * @param \Rowbot\DOM\NodeFilter|callable|null $filter     A user defined function to determine whether or not to
     *                                                         accept a node that has passed the whatToShow check.
     */
    public function createNodeIterator(
        Node $root,
        int $whatToShow = NodeFilter::SHOW_ALL,
        $filter = null
    ): NodeIterator {
        $iter = new NodeIterator($root, $whatToShow, $filter);
        self::$nodeIteratorList[] = $iter;

        return $iter;
    }

    /**
     * Creates a processing instruction.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createprocessinginstruction
     *
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError
     */
    public function createProcessingInstruction(string $target, string $data): ProcessingInstruction
    {
        // If target does not match the Name production, then throw an
        // InvalidCharacterError.
        if (!preg_match(Namespaces::NAME_PRODUCTION, $target)) {
            throw new InvalidCharacterError();
        }

        if (mb_strpos($data, '?>', 0, 'utf-8') !== false) {
            throw new InvalidCharacterError();
        }

        return new ProcessingInstruction($this, $target, $data);
    }

    /**
     * Creates a range.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createrange
     */
    public function createRange(): Range
    {
        return new Range($this);
    }

    /**
     * Creates a text node.
     */
    public function createTextNode(string $data): Text
    {
        return new Text($this, $data);
    }

    /**
     * Creates a new CDATA Section node, with data as its data.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createcdatasection
     *
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError
     * @throws \Rowbot\DOM\Exception\NotSupportedError
     */
    public function createCDATASection(string $data): CDATASection
    {
        // If context object is an HTML document, then throw a
        // NotSupportedError.
        if ($this instanceof HTMLDocument) {
            throw new NotSupportedError();
        }

        // If data contains the string "]]>", then throw an
        // InvalidCharacterError.
        if (mb_strpos($data, ']]>', 0, 'utf-8') !== false) {
            throw new InvalidCharacterError();
        }

        // Return a new CDATASection node with its data set to data and node
        // document set to the context object.
        $node = new CDATASection($this, $data);

        return $node;
    }

    /**
     * Returns a new TreeWalker object, which represents the nodes of a document
     * subtree and a position within them.
     *
     * @param \Rowbot\DOM\Node                     $root       The root node of the DOM subtree being traversed.
     * @param int                                  $whatToShow (optional) A bitmask of NodeFilter constants allowing the
     *                                                         user to filter for specific node types.
     * @param \Rowbot\DOM\NodeFilter|callable|null $filter     (optional) A user defined function to determine whether
     *                                                         or not to accept a node that has passed the whatToShow
     *                                                         check.
     */
    public function createTreeWalker(
        Node $root,
        int $whatToShow = NodeFilter::SHOW_ALL,
        $filter = null
    ): TreeWalker {
        return new TreeWalker($root, $whatToShow, $filter);
    }

    /**
     * Removes a node from its parent and adopts it and all its children.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-adopt
     */
    public function doAdoptNode(Node $node): void
    {
        $oldDocument = $node->nodeDocument;

        if ($node->parentNode) {
            $node->removeNode();
        }

        if ($this !== $oldDocument) {
            $iter = new NodeIterator($node, NodeFilter::SHOW_ALL);

            while (($nextNode = $iter->nextNode())) {
                $nextNode->nodeDocument = $this;

                // 3.1.2. If inclusiveDescendant is an element, then set the node document of each
                // attribute in inclusiveDescendant’s attribute list to document.
                if ($nextNode instanceof Element) {
                    foreach ($nextNode->getAttributeList() as $attr) {
                        $attr->nodeDocument = $this;
                    }
                }
            }

            // For each descendant in node’s inclusive descendants, in
            // tree order, run the adopting steps with descendant and
            // oldDocument.
            $iter = new NodeIterator($node);

            while (($descendant = $iter->nextNode())) {
                if (method_exists($descendant, 'doAdoptingSteps')) {
                    $descendant->doAdoptingSteps($oldDocument);
                }
            }
        }
    }

    public function ownerDocument(): ?self
    {
        return null;
    }

    public function getLength(): int
    {
        return count($this->childNodes);
    }

    /**
     * Returns the special proxy document responsible for owning all of a
     * template element's content's children.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/scripting.html#appropriate-template-contents-owner-document
     */
    public function getAppropriateTemplateContentsOwnerDocument(): self
    {
        $doc = $this;

        if (!($this->flags & self::INERT_TEMPLATE_DOCUMENT)) {
            if (!$this->inertTemplateDocument) {
                $newDoc = new static();
                $newDoc->flags |= self::INERT_TEMPLATE_DOCUMENT;
                $this->inertTemplateDocument = $newDoc;
            }

            $doc = $this->inertTemplateDocument;
        }

        return $doc;
    }

    /**
     * Gets the document's base URL, which is either the document's address or
     * the value of a base element's href attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/infrastructure.html#document-base-url
     */
    public function getBaseURL(): URLRecord
    {
        $head = $this->getHeadElement();
        $base = null;

        if ($head) {
            // A base element is only valid if it is the first base element
            // within the head element.
            foreach ($head->childNodes as $child) {
                if ($child instanceof HTMLBaseElement && $child->hasAttribute('href')) {
                    $base = $child;

                    break;
                }
            }
        }

        // We don't have a valid base element to use as a base URL, use the
        // fallback base URL.
        if (!$base) {
            return $this->getFallbackBaseURL();
        }

        return $base->getFrozenBaseURL();
    }

    public function getFallbackBaseURL(): URLRecord
    {
        // TODO: If the Document is an iframe srcdoc document, then return the
        // document base URL of the Document's browsing context's browsing
        // context container's node document and abort these steps.

        // TODO: If the document's address is about:blank, and the Document's
        // browsing context has a creator browsing context, then return the
        // creator base URL and abort these steps.

        return $this->getURL();
    }

    /**
     * Gets the flags set on the document.
     *
     * @internal
     */
    public function getFlags(): int
    {
        return $this->flags;
    }

    /**
     * Sets the document's ready state.
     *
     * @internal
     */
    public function setReadyState(string $readyState): void
    {
        $this->readyState = $readyState;
    }

    /**
     * Gets the value of the document's mode.
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * Sets the document's mode.
     *
     * @internal
     */
    public function setMode(int $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * Indicates whether the document is an HTML document or not.
     *
     * @internal
     */
    public function isHTMLDocument(): bool
    {
        return $this->isHTMLDocument;
    }

    /**
     * Indicates whether the document is an iframe src document.
     *
     * @internal
     */
    public function isIframeSrcdoc(): bool
    {
        return $this->isIframeSrcDoc;
    }

    /**
     * Marks the document as being an iframe src document.
     *
     * @internal
     */
    public function markAsIframeSrcdoc(): void
    {
        $this->isIframeSrcDoc = true;
    }

    /**
     * Gets the node iterator collection.
     *
     * @internal
     *
     * @return array<int, \Rowbot\DOM\NodeIterator>
     */
    public static function getNodeIteratorCollection(): array
    {
        return self::$nodeIteratorList;
    }

    /**
     * Imports a node from another document into the current document.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-importnode
     *
     * @throws \Rowbot\DOM\Exception\NotSupportedError
     */
    public function importNode(Node $node, bool $deep = false): Node
    {
        if ($node instanceof Document || $node instanceof ShadowRoot) {
            throw new NotSupportedError();
        }

        return $node->cloneNodeInternal($this, $deep);
    }

    /**
     * Sets the document's character set.
     *
     * @internal
     */
    public function setCharacterSet(string $characterSet): void
    {
        $this->characterSet = $characterSet;
    }

    /**
     * Sets the document's content type.
     *
     * @internal
     */
    public function setContentType(string $type): void
    {
        $this->contentType = $type;
    }

    /**
     * Sets flags on the document.
     *
     * @internal
     */
    public function setFlags(int $flag): void
    {
        $this->flags |= $flag;
    }

    /**
     * Unsets bitwise flags on the document.
     *
     * @internal
     */
    public function unsetFlags(int $flag): void
    {
        $this->flags &= ~$flag;
    }

    /**
     * Gets the document's head element. The document's head element is the
     * first child of the html element that is a head element.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/dom.html#dom-document-head
     */
    protected function getHeadElement(): ?HTMLHeadElement
    {
        $docElement = $this->getFirstElementChild();

        if ($docElement && $docElement instanceof HTMLHtmlElement) {
            // Get the first child in the document element that is a head
            // element.
            foreach ($docElement->childNodes as $child) {
                if ($child instanceof HTMLHeadElement) {
                    return $child;
                }
            }
        }

        return null;
    }

    protected function getNodeName(): string
    {
        return '#document';
    }

    protected function getNodeValue(): ?string
    {
        return null;
    }

    protected function getTextContent(): ?string
    {
        return null;
    }

    /**
     * Returns null if event’s type attribute value is "load" or document does
     * not have a browsing context, and the document’s associated Window
     * object otherwise.
     *
     * @see \Rowbot\DOM\Event\EventTarget::getTheParent
     */
    protected function getTheParent(Event $event): ?EventTarget
    {
        // We don't currently support browsing contexts or the concept of a
        // Window object, so return null as this is the end of the chain.
        return null;
    }

    /**
     * Gets the Document's URL address.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-document-url
     */
    protected function getURL(): URLRecord
    {
        if (isset($this->url)) {
            return $this->url;
        }

        if (PHP_SAPI === 'cli') {
            $this->url = URLParser::parseUrl('about:blank');
        } else {
            $ssl = isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] === 'on';
            $port = in_array($_SERVER['SERVER_PORT'], ['80', '443'], true)
                ? ''
                : ':' . $_SERVER['SERVER_PORT'];
            $url = ($ssl ? 'https' : 'http')
                . '://'
                . $_SERVER['SERVER_NAME']
                . $port
                . $_SERVER['REQUEST_URI'];

            $this->url = URLParser::parseUrl($url);
        }

        return $this->url;
    }

    protected function setNodeValue(?string $value): void
    {
        // Do nothing.
    }

    protected function setTextContent(?string $value): void
    {
        // Do nothing.
    }

    public function toString(): string
    {
        return MarkupFactory::serializeFragment($this, true);
    }

    public function __toString(): string
    {
        return MarkupFactory::serializeFragment($this, true);
    }
}
