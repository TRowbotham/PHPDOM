<?php
namespace phpjs;

use phpjs\events\Event;
use phpjs\events\CustomEvent;
use phpjs\exceptions\HierarchyRequestError;
use phpjs\exceptions\InvalidCharacterError;
use phpjs\exceptions\NotSupportedError;

// https://developer.mozilla.org/en-US/docs/Web/API/Document
// https://html.spec.whatwg.org/#document
class Document extends Node {
    use GetElementsBy, NonElementParentNode, ParentNode;

    const NO_QUIRKS_MODE = 1;
    const LIMITED_QUIRKS_MODE = 2;
    const QUIRKS_MODE = 3;

    protected static $mDefaultDocument = null;

    protected $mCharacterSet;
    protected $mContentType;
    protected $mDoctype; // DocumentType
    protected $mMode;

    private $mCompatMode;
    private $mEvents;
    private $mImplementation;
    private $mNodeIteratorList;
    private $mURL;

    public function __construct() {
        parent::__construct();

        if (!static::$mDefaultDocument) {
            static::$mDefaultDocument = $this;
        }

        $this->mContentType = '';
        $this->mDoctype = null;
        $this->mEvents = array();
        $this->mImplementation = new DOMImplementation($this);
        $this->mMode = self::NO_QUIRKS_MODE;
        $this->mNodeIteratorList = array();
        $this->mNodeName = '#document';
        $this->mNodeType = self::DOCUMENT_NODE;
        $this->mOwnerDocument = null; // Documents own themselves.

        $ssl = isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == 'on';
        $port = in_array($_SERVER['SERVER_PORT'], array(80, 443)) ? '' : ':' . $_SERVER['SERVER_PORT'];
        $url = ($ssl ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];

        $this->mURL = new urls\URL($url);
    }

    public function __get($aName) {
        switch ($aName) {
            case 'characterSet':
                return $this->mCharacterSet;
            case 'childElementCount':
                return $this->getChildElementCount();
            case 'children':
                return $this->getChildren();
            case 'contentType':
                return $this->mContentType;
            case 'doctype':
                return $this->mDoctype;
            case 'documentElement':
                return $this->getFirstElementChild();
            case 'documentURI':
            case 'URL':
                return $this->mURL->href;
            case 'firstElementChild':
                return $this->getFirstElementChild();
            case 'implementation':
                return $this->mImplementation;
            case 'lastElementChild':
                return $this->getLastElementChild();
            case 'origin':
                return $this->mURL->origin;
            default:
                return parent::__get($aName);
        }
    }

    /**
     * Adopts the given Node and its subtree from a differnt Document allowing
     * the node to be used in this Document.
     *
     * @link https://dom.spec.whatwg.org/#dom-document-adoptnode
     *
     * @param  Node  $aNode The Node to be adopted.
     *
     * @return Node         The newly adopted Node.
     */
    public function adoptNode(Node $aNode) {
        if ($aNode instanceof Document) {
            throw new NotSupportedError;
        }

        if ($aNode instanceof ShadowRoot) {
            throw new HierarchyRequestError;
        }

        $this->_adoptNode($aNode);

        return $aNode;
    }

    /**
     * Creates an HTMLElement with the specified tag name.
     *
     * @link https://dom.spec.whatwg.org/#dom-document-createelement
     *
     * @param  string       $aLocalName   The name of the element to create.
     *
     * @return HTMLElement                A known HTMLElement or HTMLUnknownElement.
     */
    public function createElement($aLocalName) {
        // TODO: Make sure localName matches the name production

        $localName = strtolower($aLocalName);
        $interface = $this->getHTMLInterfaceFor($localName);
        $node = new $interface($localName, Namespaces::HTML);
        $node->mOwnerDocument = $this;

        return $node;
    }

    public function createElementNS($aNamespace, $aQualifiedName) {
        try {
            $parts = Namespaces::validateAndExtract($aNamespace, $aQualifiedName);
        } catch (Exception $e) {
            throw $e;
        }

        // We only support the HTML namespace currently.
        switch ($parts['namespace']) {
            case Namespaces::HTML:
                $interface = $this->getHTMLInterfaceFor($parts['localName']);

                break;

            default:
                $interface = 'phpjs\elements\Element';
        }

        $node = new $interface($parts['localName'], $parts['namespace'], $parts['prefix']);
        $node->mOwnerDocument = $this;

        return $node;
    }

    public function createDocumentFragment() {
        $node = new DocumentFragment();
        $node->mOwnerDocument = $this;

        return $node;
    }

    public function createComment($aData) {
        $node = new Comment($aData);
        $node->mOwnerDocument = $this;

        return $node;
    }

    /**
     * Creates a new Event of the specified type and returns it.
     *
     * @link https://dom.spec.whatwg.org/#dom-document-createevent
     *
     * @param  string $aInterface The type of event interface to be created.
     *
     * @return Event
     *
     * @throws NotSupportedError
     */
    public function createEvent($aInterface) {
        $constructor = null;
        $interface = strtolower($aInterface);

        switch ($interface) {
            case 'customevent':
                $constructor = 'CustomEvent';

                break;

            case 'event':
            case 'events':
            case 'htmlevents':
                $constructor = 'Event';
        }

        if (!$constructor) {
            throw new NotSupportedError;
        }

        $event = new $constructor('');
        $event->_unsetFlag(Event::EVENT_INITIALIZED);

        return $event;
    }

    /**
     * Returns a new NodeIterator object, which represents an iterator over the members of a list of the nodes in a
     * subtree of the DOM.
     * @param  Node             $aRoot       The root node of the iterator object.
     * @param  int              $aWhatToShow Optional.  A bitmask of NodeFilter constants allowing the user
     *                                          to filter for specific node types.
     * @param  callable|null    $aFilter     A user defined function to determine whether or not to accept a node that has
     *                                          passed the whatToShow check.
     * @return NodeIterator
     */
    public function createNodeIterator(Node $aRoot, $aWhatToShow = NodeFilter::SHOW_ALL, callable $aFilter = null) {
        $iter = new NodeIterator($aRoot, $aWhatToShow, $aFilter);
        $this->mNodeIteratorList[] = $iter;

        return $iter;
    }

    public function createProcessingInstruction($aTarget, $aData) {
        // TODO: Make sure the Name matches the production

        if (strpos($aData, '?>') === false) {
            throw new InvalidCharacterError;
        }

        $pi = new ProcessingInstruction($aTarget, $aData);
        $pi->mOwnerDocument = $this;

        return $pi;
    }

    public function createRange() {
        $range = new Range();
        $range->setStart($this, 0);
        $range->setEnd($this, 0);

        return $range;
    }

    public function createTextNode($aData) {
        $node = new Text($aData);
        $node->mOwnerDocument = $this;

        return $node;
    }

    /**
     * Returns a new TreeWalker object, which represents the nodes of a document subtree and a position within them.
     * @param  Node             $aRoot       The root node of the DOM subtree being traversed.
     * @param  int              $aWhatToShow Optional.  A bitmask of NodeFilter constants allowing the user
     *                                          to filter for specific node types.
     * @param  callable|null    $aFilter     A user defined function to determine whether or not to accept a node that has
     *                                          passed the whatToShow check.
     * @return TreeWalker
     */
    public function createTreeWalker(Node $aRoot, $aWhatToShow = NodeFilter::SHOW_ALL, callable $aFilter = null) {
        return new TreeWalker($aRoot, $aWhatToShow, $aFilter);
    }

    public function importNode(Node $aNode, $aDeep = false) {
        if ($aNode instanceof Document || $aNode instanceof ShadowRoot) {
            throw new NotSupportedError;
        }

        $clone = $aNode->cloneNode($aDeep);
        $this->adoptNode($clone);

        return $clone;
    }

    /**
     * Removes the node from its parent and adopts it and all its children.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-node-adopt
     *
     * @param  Node   $aNode The Node to be adopted into this document.
     */
    public function _adoptNode(Node $aNode) {
        $oldDocument = $aNode->mOwnerDocument;

        if ($aNode->mParentNode) {
            $aNode->mParentNode->_removeChild($aNode);
        }

        $tw = $oldDocument->createTreeWalker($aNode);
        $node = $aNode;

        while ($node) {
            $node->mOwnerDocument = $this;
            // TODO: Support adopting steps for nodes
            $node = $tw->nextNode();
        }
    }

    /**
     * Gets the value of the document's mode.
     *
     * @internal
     *
     * @return int
     */
    public function _getMode() {
        return $this->mMode;
    }

    public function _getNodeIteratorCollection() {
        return $this->mNodeIteratorList;
    }

    /**
     * Sets the document's content type.
     *
     * @internal
     *
     * @param string $aType The MIME content type of the document.
     */
    public function _setContentType($aType) {
        $this->mContentType = $aType;
    }

    /**
     * Associates a DocumentType node with the document.
     *
     * @internal
     *
     * @param DocumentType $aDoctype The DocumentType node of the document.
     */
    public function _setDoctype(DocumentType $aDoctype = null) {
        $this->mDoctype = $aDoctype;
    }

    /**
     * Sets the document's mode.
     *
     * @internal
     *
     * @param int $aMode An integer representing the current mode.
     */
    public function _setMode($aMode) {
        $this->mMode = $aMode;
    }

    /**
     * @internal
     *
     * Sets the document's character set.
     *
     * @param string $aCharacterSet The document's character set
     */
    public function _setCharacterSet($aCharacterSet) {
        if (!is_string($aCharacterSet)) {
            return;
        }

        $this->mCharacterSet = $aCharacterSet;
    }

    /**
     * @internal
     * Returns the first document created, which is assumed to be the global
     * document.  This global document is the owning document for objects instantiated
     * using its constructor.  These objects are DocumentFragment, Text, Comment, and
     * ProcessingInstruction.
     * @return Document|null Returns the global document.  If null is returned, then no
     *                          document existed before the user attempted to instantiate
     *                          an object that has an owning document.
     */
    public static function _getDefaultDocument() {
        return static::$mDefaultDocument;
    }

    public function _printTree() {
        return $this->_traverseTree($this->mChildNodes, 0);
    }

    private function _traverseTree($aNodes, $aLevel = 0) {
        if (empty($aNodes)) {
            return '';
        }

        $html = '<div class="tree-level">';
        foreach ($aNodes as $node) {
            $name = $node->nodeName ? strtolower($node->nodeName) : get_class($node);
            $html .= '<div class="tree-branch">';
            $html .= htmlspecialchars('<' . $name);
            if ($node instanceof Element) {
                foreach($node->attributes as $attribute) {
                    $html .= ' ' . $attribute->name;

                    if (!Attr::_isBool($attribute->name)) {
                        $html .= '="' . $attribute->value . '"';
                    }
                }
            }
            $html .= '></div>';
            $html .= $this->_traverseTree($node->childNodes, ++$aLevel);
        }
        $html .= '</div>';

        return $html;
    }

    public function prettyPrintTree($aNode = null) {
        $node = ($aNode instanceof Node ? [$aNode] : (is_array($aNode) ? $aNode : [$this]));

        if (empty($node)) {
            return '';
        }

        $html = '<ul class="level">';

        foreach ($node as $childNode) {
            switch ($childNode->nodeType) {
                case Node::ELEMENT_NODE:
                    $tagName = strtolower($childNode->nodeName);
                    $html .= '<li>&lt;' . $tagName;

                    foreach($childNode->attributes as $attribute) {
                        $html .= ' ' . $attribute->name;

                        if (!Attr::_isBool($attribute->name)) {
                            $html .= '="' . $attribute->value . '"';
                        }
                    }

                    $html .= '&gt;' . $this->prettyPrintTree($childNode->childNodes);

                    if (!$childNode->_isEndTagOmitted()) {
                        $html .= '&lt;/' . $tagName . '&gt;</li>';
                    }

                    break;

                case Node::TEXT_NODE:
                    $html .= '<li>' . $childNode->data . '<li>';

                    break;

                case Node::PROCESSING_INSTRUCTION_NODE:
                    // TODO
                    break;

                case Node::COMMENT_NODE:
                    $html .= '<li>&lt;!-- ' . $childNode->data . ' --&gt;</li>';

                    break;

                case Node::DOCUMENT_TYPE_NODE:
                    $html .= '<li>' . htmlentities($childNode->toHTML()) . '</li>';

                    break;

                case Node::DOCUMENT_NODE:
                case Node::DOCUMENT_FRAGMENT_NODE:
                    $html = $this->prettyPrintTree($childNode->childNodes);

                    break;

                default:
                    # code...
                    break;
            }
        }

        $html .= '</ul>';

        return $html;
    }

    protected function getHTMLInterfaceFor($aLocalName) {
        switch($aLocalName) {
            /**
             * These are elements whose tag name differs
             * from its DOM interface name, so map the tag
             * name to the interface name.
             */
            case 'a':
                $interfaceName = 'Anchor';

                break;

            case 'br':
                $interfaceName = 'BR';

                break;

            case 'datalist':
                $interfaceName = 'DataList';

                break;

            case 'dl':
                $interfaceName = 'DList';

                break;

            case 'fieldset':
                $interfaceName = 'FieldSet';

                break;

            case 'hr':
                $interfaceName = 'HR';

                break;

            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                $interfaceName = 'Heading';

                break;

            case 'iframe':
                $interfaceName = 'IFrame';

                break;

            case 'img':
                $interfaceName = 'Image';

                break;

            case 'ins':
            case 'del':
                $interfaceName = 'Mod';

                break;

            case 'li':
                $interfaceName = 'LI';

                break;

            case 'ol':
                $interfaceName = 'OList';

                break;

            case 'optgroup':
                $interfaceName = 'OptGroup';

                break;

            case 'p':
                $interfaceName = 'Paragraph';

                break;

            case 'blockquote':
            case 'cite':
            case 'q':
                $interfaceName = 'Quote';

                break;

            case 'caption':
                $interfaceName = 'TableCaption';

                break;

            case 'td':
                $interfaceName = 'TableDataCell';

                break;

            case 'th':
                $interfaceName = 'TableHeaderCell';

                break;

            case 'col':
            case 'colgroup':
                $interfaceName = 'TableCol';

                break;

            case 'tr':
                $interfaceName = 'TableRow';

                break;

            case 'tbody':
            case 'thead':
            case 'tfoot':
                $interfaceName = 'TableSection';

                break;

            case 'textarea':
                $interfaceName = 'TextArea';

                break;

            case 'ul':
                $interfaceName = 'UList';

                break;

            /**
             * These are known HTML elements that don't have their
             * own DOM interface, but should not be classified as
             * HTMLUnknownElements.
             */
            case 'abbr':
            case 'address':
            case 'article':
            case 'aside':
            case 'b':
            case 'bdi':
            case 'bdo':
            case 'cite':
            case 'code':
            case 'dd':
            case 'dfn':
            case 'dt':
            case 'em':
            case 'figcaption':
            case 'figure':
            case 'footer':
            case 'header':
            case 'hrgroup':
            case 'i':
            case 'kbd':
            case 'main':
            case 'mark':
            case 'nav':
            case 'rp':
            case 'rt':
            case 'rtc':
            case 'ruby':
            case 's':
            case 'samp':
            case 'section':
            case 'small':
            case 'strong':
            case 'sub':
            case 'sup':
            case 'u':
            case 'var':
            case 'wbr':
                $interfaceName = '';

                break;

            /**
             * These are known HTML elements that have their own
             * DOM interface and their names do not differ from
             * their interface names.
             */
            case 'area':
            case 'audio':
            case 'base':
            case 'body':
            case 'button':
            case 'canvas':
            case 'data':
            case 'div':
            case 'embed':
            case 'form':
            case 'head':
            case 'html':
            case 'input':
            case 'keygen':
            case 'label':
            case 'legend':
            case 'link':
            case 'map':
            case 'meta':
            case 'meter':
            case 'object':
            case 'option':
            case 'output':
            case 'param':
            case 'picture':
            case 'pre':
            case 'progress':
            case 'script':
            case 'select':
            case 'source':
            case 'span':
            case 'style':
            case 'table':
            case 'time':
            case 'title':
            case 'track':
            case 'video':
                $interfaceName = ucfirst($aLocalName);

                break;

            default:
                $interfaceName = 'Unknown';
        }

        return 'phpjs\\elements\\html\\HTML' . $interfaceName . 'Element';
    }
}
