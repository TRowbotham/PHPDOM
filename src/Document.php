<?php
namespace Rowbot\DOM;

use Rowbot\DOM\DocumentType;
use Rowbot\DOM\Element\HTML\HTMLBaseElement;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Element\HTML\HTMLHeadElement;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Event\EventFlags;
use Rowbot\DOM\Exception\DOMException;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\Exception\NotSupportedError;
use Rowbot\DOM\Parser\MarkupFactory;
use Rowbot\DOM\Support\Stringable;
use Rowbot\DOM\URL\URLParser;
use Rowbot\DOM\Utils;

/**
 * @see https://dom.spec.whatwg.org/#interface-document
 * @see https://html.spec.whatwg.org/#document
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Document
 */
class Document extends Node implements Stringable
{
    use GetElementsBy;
    use NonElementParentNode;
    use ParentNode;

    const INERT_TEMPLATE_DOCUMENT = 0x1;

    protected static $defaultDocument = null;

    protected $characterSet;
    protected $contentType;
    protected $flags;
    protected $inertTemplateDocument;
    protected $mode;

    private $compatMode;
    private $implementation;
    private $isIframeSrcDoc;
    private $isHTMLDocument;
    private $nodeIteratorList;
    private $url;
    private $readyState;
    private $source;

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

    public function __get($name)
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
                return $this->getURL()->getOrigin()->serializeAsUnicode();
            case 'readyState':
                return $this->readyState;
            default:
                return parent::__get($name);
        }
    }

    /**
     * Adopts the given Node and its subtree from a differnt Document allowing
     * the node to be used in this Document.
     *
     * @link https://dom.spec.whatwg.org/#dom-document-adoptnode
     *
     * @param  Node  $node The Node to be adopted.
     *
     * @return Node         The newly adopted Node.
     */
    public function adoptNode(Node $node)
    {
        if ($node instanceof Document) {
            throw new NotSupportedError();
            return;
        }

        if ($node instanceof ShadowRoot) {
            throw new HierarchyRequestError();
            return;
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
     * @return Attr
     *
     * @throws InvalidCharacterError
     */
    public function createAttribute($localName)
    {
        $localName = Utils::DOMString($localName);

        // If localName does not match the Name production in XML, then
        // throw an InvalidCharacterError.
        if (!\preg_match(Namespaces::NAME_PRODUCTION, $localName)) {
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
     * @param string $namespace The attribute's namespace.
     *
     * @param string $qualifiedName The attribute's qualified name.
     *
     * @return Attr
     *
     * @throws NamespaceError
     *
     * @throws InvalidCharacterError
     */
    public function createAttributeNS($namespace, $qualifiedName)
    {
        try {
            list(
                $namespace,
                $prefix,
                $localName
            ) = Namespaces::validateAndExtract(
                Utils::DOMString($namespace, false, true),
                Utils::DOMString($qualifiedName)
            );
        } catch (DOMException $e) {
            throw $e;
        }

        $attribute = new Attr($localName, '', $namespace, $prefix);
        $attribute->setNodeDocument($this);

        return $attribute;
    }

    public function createComment($data)
    {
        $node = new Comment($data);
        $node->nodeDocument = $this;

        return $node;
    }

    public function createDocumentFragment()
    {
        $node = new DocumentFragment();
        $node->nodeDocument = $this;

        return $node;
    }

    /**
     * Creates an HTMLElement with the specified tag name.
     *
     * @link https://dom.spec.whatwg.org/#dom-document-createelement
     *
     * @param string $localName The name of the element to create.
     *
     * @return HTMLElement A known HTMLElement or HTMLUnknownElement.
     */
    public function createElement($localName)
    {
        $localName = Utils::DOMString($localName);

        // If localName does not match the Name production, then throw an
        // InvalidCharacterError.
        if (!\preg_match(Namespaces::NAME_PRODUCTION, $localName)) {
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

        try {
            $element = ElementFactory::create(
                $this,
                $localName,
                $namespace,
                null
            );
        } catch (DOMException $e) {
            throw $e;
        }

        return $element;
    }

    /**
     * Creates an Element in a particular namespace.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-createelementns
     *
     * @param string|null $namespace The element's namespace.
     *
     * @param string $qualifiedName The element's qualified name.
     *
     * @return Element
     *
     * @throws InvalidCharacterError
     *
     * @throws NamespaceError
     */
    public function createElementNS($namespace, $qualifiedName)
    {
        return ElementFactory::createNS(
            $this,
            Utils::DOMString($namespace, false, true),
            Utils::DOMString($qualifiedName)
        );
    }

    /**
     * Creates a new Event of the specified type and returns it.
     *
     * @link https://dom.spec.whatwg.org/#dom-document-createevent
     *
     * @param string $interface The type of event interface to be created.
     *
     * @return Event
     *
     * @throws NotSupportedError
     */
    public function createEvent($interface)
    {
        $constructor = null;
        $interface = \strtolower(Utils::DOMString($interface));

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
     * @param Node $root The root node of the iterator object.
     *
     * @param int $whatToShow Optional. A bitmask of NodeFilter constants
     *     allowing the user to filter for specific node types.
     *
     * @param NodeFilter|callable|null $filter A user defined function to
     *     determine whether or not to accept a node that has passed the
     *     whatToShow check.
     *
     * @return NodeIterator
     */
    public function createNodeIterator(
        Node $root,
        $whatToShow = NodeFilter::SHOW_ALL,
        $filter = null
    ) {
        $iter = new NodeIterator($root, $whatToShow, $filter);
        $this->nodeIteratorList[] = $iter;

        return $iter;
    }

    public function createProcessingInstruction($target, $data)
    {
        $target = Utils::DOMString($target);

        // If target does not match the Name production, then throw an
        // InvalidCharacterError.
        if (!\preg_match(Namespaces::NAME_PRODUCTION, $target)) {
            throw new InvalidCharacterError();
        }

        $data = Utils::DOMString($data);

        if (\mb_strpos($data, '?>') !== false) {
            throw new InvalidCharacterError();
        }

        $pi = new ProcessingInstruction($target, $data);
        $pi->nodeDocument = $this;

        return $pi;
    }

    public function createRange()
    {
        $range = new Range();
        $range->setStart($this, 0);
        $range->setEnd($this, 0);

        return $range;
    }

    public function createTextNode($data)
    {
        $node = new Text($data);
        $node->nodeDocument = $this;

        return $node;
    }

    /**
     * Creates a new CDATA Section node, with data as its data.
     *
     * @param  string $data The node's content.
     *
     * @return CDATASection A CDATASection node.
     */
    public function createCDATASection($data)
    {
        // If context object is an HTML document, then throw a
        // NotSupportedError.
        if ($this instanceof HTMLDocument) {
            throw new NotSupportedError();
        }

        $data = Utils::DOMString($data);

        // If data contains the string "]]>", then throw an
        // InvalidCharacterError.
        if (\mb_strpos($data, ']]>') !== false) {
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
     * @param Node $root The root node of the DOM subtree being traversed.
     *
     * @param int $whatToShow Optional.  A bitmask of NodeFilter constants
     *     allowing the user to filter for specific node types.
     *
     * @param NodeFilter|callable|null $filter A user defined function to
     *     determine whether or not to accept a node that has passed the
     *     whatToShow check.
     *
     * @return TreeWalker
     */
    public function createTreeWalker(
        Node $root,
        $whatToShow = NodeFilter::SHOW_ALL,
        $filter = null
    ) {
        return new TreeWalker($root, $whatToShow, $filter);
    }

    /**
     * Removes a node from its parent and adopts it and all its children.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-adopt
     *
     * @param Node $node The node being adopted.
     */
    public function doAdoptNode(Node $node)
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
                if (\method_exists($descendant, 'doAdoptingSteps')) {
                    $descendant->doAdoptingSteps($oldDocument);
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function ownerDocument()
    {
        return null;
    }

    /**
     * Returns the Node's length, which is the number of child nodes.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-length
     * @see Node::getLength()
     *
     * @return int
     */
    public function getLength()
    {
        return \count($this->childNodes);
    }

    /**
     * Returns the special proxy document responsible for owning all of a
     * template element's content's children.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/scripting.html#appropriate-template-contents-owner-document
     *
     * @return Document
     */
    public function getAppropriateTemplateContentsOwnerDocument()
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
     * @return URLRecord
     */
    public function getBaseURL()
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
     * document.  This global document is the owning document for objects
     * instantiated using its constructor.  These objects are DocumentFragment,
     * Text, Comment, and ProcessingInstruction.
     *
     * @internal
     *
     * @return Document|null Returns the global document.  If null is returned,
     *     then no document existed before the user attempted to instantiate an
     *     object that has an owning document.
     */
    public static function getDefaultDocument()
    {
        return self::$defaultDocument;
    }

    public function getFallbackBaseURL()
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
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * Sets the document's ready state.
     *
     * @internal
     *
     * @param string $readyState
     */
    public function setReadyState($readyState)
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
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Sets the document's mode.
     *
     * @internal
     *
     * @param int $mode An integer representing the current mode.
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    public function isHTMLDocument()
    {
        return $this->isHTMLDocument;
    }

    public function isIframeSrcdoc()
    {
        return $this->isIframeSrcDoc;
    }

    public function markAsIframeSrcdoc()
    {
        $this->isIframeSrcDoc = true;
    }

    public function getNodeIteratorCollection()
    {
        return $this->nodeIteratorList;
    }

    public function importNode(Node $node, $deep = false)
    {
        if ($node instanceof Document || $node instanceof ShadowRoot) {
            throw new NotSupportedError();
        }

        return $node->doCloneNode($this, $deep);
    }

    /**
     * @internal
     *
     * Sets the document's character set.
     *
     * @param string $characterSet The document's character set
     */
    public function setCharacterSet($characterSet)
    {
        if (!\is_string($characterSet)) {
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
     */
    public function setContentType($type)
    {
        $this->contentType = $type;
    }

    /**
     * Sets flags on the document.
     *
     * @internal
     *
     * @param int $flag Bitwise flags.
     */
    public function setFlags($flag)
    {
        $this->flags |= $flag;
    }

    /**
     * Unsets bitwise flags on the document.
     *
     * @internal
     *
     * @param int $flag Bitwise flags.
     */
    public function unsetFlags($flag)
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
     * @return HTMLHeadElement|null
     */
    protected function getHeadElement()
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
     * @see Node::getNodeName
     */
    protected function getNodeName()
    {
        return '#document';
    }

    /**
     * @see Node::getNodeValue
     */
    protected function getNodeValue()
    {
        return null;
    }

    /**
     * @see Node::getTextContent
     */
    protected function getTextContent()
    {
        return null;
    }

    /**
     * Returns null if event’s type attribute value is "load" or document does
     * not have a browsing context, and the document’s associated Window
     * object otherwise.
     *
     * @see EventTarget::getTheParent
     *
     * @param Event $event An Event object
     *
     * @return Document|null
     */
    protected function getTheParent($event)
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
     * @return string
     */
    protected function getURL()
    {
        if (!isset($this->url)) {
            $ssl = isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == 'on';
            $port = \in_array($_SERVER['SERVER_PORT'], array(80, 443)) ?
                '' : ':' . $_SERVER['SERVER_PORT'];
            $url = ($ssl ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] .
                $port . $_SERVER['REQUEST_URI'];

            $this->url = URLParser::parseUrl($url);
        }


        return $this->url;
    }

    /**
     * @see Node::setNodeValue
     */
    protected function setNodeValue($newValue)
    {
        // Do nothing.
    }

    /**
     * @see Node::setTextContent
     */
    protected function setTextContent($newValue)
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
