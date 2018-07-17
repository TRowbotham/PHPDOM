<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Element\HTML\HTMLBaseElement;
use Rowbot\DOM\Element\HTML\HTMLElement;
use Rowbot\DOM\Element\HTML\HTMLHeadElement;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Event\Event;
use Rowbot\DOM\Event\EventFlags;
use Rowbot\DOM\Event\EventTarget;
use Rowbot\DOM\Exception\DOMException;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\Exception\NotSupportedError;
use Rowbot\DOM\Parser\MarkupFactory;
use Rowbot\DOM\Support\Stringable;
use Rowbot\DOM\URL\URLParser;
use Rowbot\URL\URLRecord;

use function count;
use function in_array;
use function is_string;
use function mb_strpos;
use function method_exists;
use function preg_match;
use function strtolower;

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
class Document extends Node implements Stringable
{
    use GetElementsBy;
    use NonElementParentNode;
    use ParentNode;

    const INERT_TEMPLATE_DOCUMENT = 0x1;

    /**
     * @var ?self
     */
    protected static $defaultDocument = null;

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
     * @var ?self
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
     * \Rowbot\DOM\NodeIterator[]
     */
    private $nodeIteratorList;

    /**
     * \Rowbot\URL\URLRecord
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

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        if (!self::$defaultDocument) {
            self::$defaultDocument = $this;
        }

        $this->characterSet = 'UTF-8';
        $this->contentType = 'application/xml';
        $this->flags = 0;
        $this->implementation = new DOMImplementation($this);
        $this->isIframeSrcDoc = false;
        $this->inertTemplateDocument = null;
        $this->mode = DocumentMode::NO_QUIRKS;
        $this->nodeDocument = $this; // Documents own themselves.
        $this->nodeIteratorList = [];
        $this->nodeType = self::DOCUMENT_NODE;
        $this->url = null;

        // When a Document object is created, it must have its current document
        // readiness set to the string "loading" if the document is associated
        // with an HTML parser, an XML parser, or an XSLT processor, and to the
        // string "complete" otherwise.
        $this->readyState = DocumentReadyState::COMPLETE;

        $this->source = DocumentSource::NOT_FROM_PARSER;
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'characterSet':
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

    /**
     * {@inheritDoc}
     */
    public function cloneNodeInternal(
        Document $document = null,
        bool $cloneChildren = false
    ): Node {
        $document = $document ?: $this->getNodeDocument();
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
     * @param \Rowbot\DOM\Node $node The Node to be adopted.
     *
     * @return \Rowbot\DOM\Node The newly adopted Node.
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
     * @param string $localName The name of the attribute.
     *
     * @return \Rowbot\DOM\Attr
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

        $attribute = new Attr($localName, '');
        $attribute->setNodeDocument($this);

        return $attribute;
    }

    /**
     * Creates a new Attr object with the given namespace and the name and
     * prefix for the given namespace.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createattributens
     *
     * @param string|null $namespace     The attribute's namespace.
     * @param string      $qualifiedName The attribute's qualified name.
     *
     * @return \Rowbot\DOM\Attr
     *
     * @throws \Rowbot\DOM\Exception\NamespaceError
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError
     */
    public function createAttributeNS(
        ?string $namespace,
        string $qualifiedName
    ): Attr {
        try {
            list(
                $namespace,
                $prefix,
                $localName
            ) = Namespaces::validateAndExtract(
                $namespace,
                $qualifiedName
            );
        } catch (DOMException $e) {
            throw $e;
        }

        $attribute = new Attr($localName, '', $namespace, $prefix);
        $attribute->setNodeDocument($this);

        return $attribute;
    }

    /**
     * Creates a comment node.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createcomment
     *
     * @param string $data
     *
     * @return \Rowbot\DOM\Comment
     */
    public function createComment(string $data): Comment
    {
        $node = new Comment($data);
        $node->nodeDocument = $this;

        return $node;
    }

    /**
     * Creates a document fragment node.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createdocumentfragment
     *
     * @return \Rowbot\DOM\DocumentFragment
     */
    public function createDocumentFragment(): DocumentFragment
    {
        $node = new DocumentFragment();
        $node->nodeDocument = $this;

        return $node;
    }

    /**
     * Creates an HTMLElement with the specified tag name.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createelement
     *
     * @param string $localName The name of the element to create.
     *
     * @return \Rowbot\DOM\Element\HTML\HTMLElement A known \Rowbot\DOM\Element\HTML\HTMLElement or
     *                                              \Rowbot\DOM\Element\HTML\HTMLUnknownElement.
     *
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError
     */
    public function createElement(string $localName): HTMLElement
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

        $element = ElementFactory::create(
            $this,
            $localName,
            $namespace,
            null
        );

        return $element;
    }

    /**
     * Creates an Element in a particular namespace.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createelementns
     *
     * @param ?string $namespace     The element's namespace.
     * @param string  $qualifiedName The element's qualified name.
     *
     * @return \Rowbot\DOM\Element\Element
     *
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError
     * @throws \Rowbot\DOM\Exception\NamespaceError
     */
    public function createElementNS(
        ?string $namespace,
        string $qualifiedName
    ): Element {
        return ElementFactory::createNS($this, $namespace, $qualifiedName);
    }

    /**
     * Creates a new Event of the specified type and returns it.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createevent
     *
     * @param string $interface The type of event interface to be created.
     *
     * @return \Rowbot\DOM\Event\Event
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
     *
     * @return \Rowbot\DOM\NodeIterator
     */
    public function createNodeIterator(
        Node $root,
        int $whatToShow = NodeFilter::SHOW_ALL,
        $filter = null
    ): NodeIterator {
        $iter = new NodeIterator($root, $whatToShow, $filter);
        $this->nodeIteratorList[] = $iter;

        return $iter;
    }

    /**
     * Creates a processing instruction.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createprocessinginstruction
     *
     * @param string $target
     * @param string $data
     *
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError
     *
     * @return \Rowbot\DOM\ProcessingInstruction
     */
    public function createProcessingInstruction(
        string $target,
        string $data
    ): ProcessingInstruction {
        // If target does not match the Name production, then throw an
        // InvalidCharacterError.
        if (!preg_match(Namespaces::NAME_PRODUCTION, $target)) {
            throw new InvalidCharacterError();
        }

        if (mb_strpos($data, '?>') !== false) {
            throw new InvalidCharacterError();
        }

        $pi = new ProcessingInstruction($target, $data);
        $pi->nodeDocument = $this;

        return $pi;
    }

    /**
     * Creates a range.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createrange
     *
     * @return \Rowbot\DOM\Range
     */
    public function createRange(): Range
    {
        $range = new Range();
        $range->setStart($this, 0);
        $range->setEnd($this, 0);

        return $range;
    }

    /**
     * Creates a text node.
     *
     * @param string $data
     *
     * @return \Rowbot\DOM\Text
     */
    public function createTextNode(string $data): Text
    {
        $node = new Text($data);
        $node->nodeDocument = $this;

        return $node;
    }

    /**
     * Creates a new CDATA Section node, with data as its data.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createcdatasection
     *
     * @param string $data The node's content.
     *
     * @return \Rowbot\DOM\CDATASection A CDATASection node.
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
        if (mb_strpos($data, ']]>') !== false) {
            throw new InvalidCharacterError();
        }

        // Return a new CDATASection node with its data set to data and node
        // document set to the context object.
        $node = new CDATASection($data);
        $node->setNodeDocument($this);

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
     *
     * @return \Rowbot\DOM\TreeWalker
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
     *
     * @param \Rowbot\DOM\Node $node The node being adopted.
     *
     * @return void
     */
    public function doAdoptNode(Node $node): void
    {
        $oldDocument = $node->nodeDocument;

        if ($node->parentNode) {
            $node->parentNode->removeNode($node);
        }

        if ($this !== $oldDocument) {
            $iter = new NodeIterator($node, NodeFilter::SHOW_ALL);

            while (($nextNode = $iter->nextNode())) {
                $nextNode->nodeDocument = $this;
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

    /**
     * {@inheritDoc}
     */
    public function ownerDocument(): ?self
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
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
     *
     * @return self
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
     *
     * @return \Rowbot\URL\URLRecord
     */
    public function getBaseURL(): URLRecord
    {
        $head = $this->getHeadElement();
        $base = null;

        if ($head) {
            // A base element is only valid if it is the first base element
            // within the head element.
            foreach ($head->childNodes as $child) {
                $isValidBase = $child instanceof HTMLBaseElement
                    && $child->hasAttribute('href');

                if ($isValidBase) {
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

    /**
     * Returns the first document created, which is assumed to be the global
     * document. This global document is the owning document for objects
     * instantiated using its constructor.  These objects are DocumentFragment,
     * Text, Comment, and ProcessingInstruction.
     *
     * @internal
     *
     * @return self|null Returns the global document. If null is returned,
     *     then no document existed before the user attempted to instantiate an
     *     object that has an owning document.
     *
     * @todo Returning null here is probably a bug. Look into it.
     */
    public static function getDefaultDocument(): ?self
    {
        return self::$defaultDocument;
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
     *
     * @return int
     */
    public function getFlags(): int
    {
        return $this->flags;
    }

    /**
     * Sets the document's ready state.
     *
     * @internal
     *
     * @param string $readyState
     *
     * @return void
     */
    public function setReadyState(string $readyState): void
    {
        $this->readyState = $readyState;
    }

    /**
     * Gets the value of the document's mode.
     *
     * @internal
     *
     * @return int The document's current mode.
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * Sets the document's mode.
     *
     * @internal
     *
     * @param int $mode An integer representing the current mode.
     *
     * @return void
     */
    public function setMode(int $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * Indicates whether the document is an HTML document or not.
     *
     * @internal
     *
     * @return bool
     */
    public function isHTMLDocument(): bool
    {
        return $this->isHTMLDocument;
    }

    /**
     * Indicates whether the document is an iframe src document.
     *
     * @internal
     *
     * @return bool
     */
    public function isIframeSrcdoc(): bool
    {
        return $this->isIframeSrcDoc;
    }

    /**
     * Marks the document as being an iframe src document.
     *
     * @internal
     *
     * @return void
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
     * @return \Rowbot\DOM\NodeIterator[]
     */
    public function getNodeIteratorCollection(): array
    {
        return $this->nodeIteratorList;
    }

    /**
     * Imports a node from another document into the current document.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-importnode
     *
     * @param \Rowbot\DOM\Node $node
     * @param bool             $deep (optional)
     *
     * @throws \Rowbot\DOM\Exception\NotSupportedError
     *
     * @return \Rowbot\DOM\Node
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
     *
     * @param string $characterSet The document's character set
     *
     * @return void
     */
    public function setCharacterSet(string $characterSet): void
    {
        if (!is_string($characterSet)) {
            return;
        }

        $this->characterSet = $characterSet;
    }

    /**
     * Sets the document's content type.
     *
     * @internal
     *
     * @param string $type The MIME content type of the document.
     *
     * @return void
     */
    public function setContentType(string $type): void
    {
        $this->contentType = $type;
    }

    /**
     * Sets flags on the document.
     *
     * @internal
     *
     * @param int $flag Bitwise flags.
     *
     * @return void
     */
    public function setFlags(int $flag): void
    {
        $this->flags |= $flag;
    }

    /**
     * Unsets bitwise flags on the document.
     *
     * @internal
     *
     * @param int $flag Bitwise flags.
     *
     * @return void
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
     *
     * @return \Rowbot\DOM\Element\HTML\HTMLHeadElement|null
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

    /**
     * {@inheritDoc}
     */
    protected function getNodeName(): string
    {
        return '#document';
    }

    /**
     * {@inheritDoc}
     */
    protected function getNodeValue(): ?string
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
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
     *
     * @param \Rowbot\DOM\Event\Event $event An Event object
     *
     * @return ?self
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
     *
     * @return \Rowbot\URL\URLRecord
     */
    protected function getURL(): URLRecord
    {
        if (!isset($this->url)) {
            $ssl = isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == 'on';
            $port = in_array($_SERVER['SERVER_PORT'], array(80, 443)) ?
                '' : ':' . $_SERVER['SERVER_PORT'];
            $url = ($ssl ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] .
                $port . $_SERVER['REQUEST_URI'];

            $this->url = URLParser::parseUrl($url);
        }


        return $this->url;
    }

    /**
     * {@inheritDoc}
     */
    protected function setNodeValue(?string $value): void
    {
        // Do nothing.
    }

    /**
     * {@inheritDoc}
     */
    protected function setTextContent(?string $value): void
    {
        // Do nothing.
    }

    /**
     * {@inheirtDoc}
     */
    public function toString(): string
    {
        return MarkupFactory::serializeFragment($this, true);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return MarkupFactory::serializeFragment($this, true);
    }
}
