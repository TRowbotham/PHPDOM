<?php
// https://developer.mozilla.org/en-US/docs/Web/API/Document
// https://html.spec.whatwg.org/#document

require_once 'Node.class.php';
require_once 'ParentNode.class.php';
require_once 'DOMImplementation.class.php';
require_once 'DocumentType.class.php';
require_once 'Attr.class.php';
require_once 'DocumentFragment.class.php';
require_once 'Event.class.php';
require_once 'Text.class.php';
require_once 'NonElementParentNode.class.php';
require_once 'Comment.class.php';
require_once 'URL.class.php';
require_once 'NodeFilter.class.php';
require_once 'NodeIterator.class.php';
require_once 'Range.class.php';
require_once 'TreeWalker.class.php';

class Document extends Node {
    use ParentNode, NonElementParentNode;

    protected static $mDefaultDocument = null;

    protected $mCharacterSet;
    protected $mContentType;
    protected $mDoctype; // DocumentType

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
        $this->mImplementation = new iDOMImplementation();
        $this->mNodeIteratorList = array();
        $this->mNodeName = '#document';
        $this->mNodeType = Node::DOCUMENT_NODE;
        $this->mOwnerDocument = null; // Documents own themselves.

        $ssl = isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == 'on';
        $port = in_array($_SERVER['SERVER_PORT'], array(80, 443)) ? '' : ':' . $_SERVER['SERVER_PORT'];
        $url = ($ssl ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];

        $this->mURL = new URL($url);
    }

    public function __get($aName) {
        switch ($aName) {
            case 'baseURI':
                return $this->mURL->href;
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
     * Creates an Element with the specified tag name.
     * @param  string       $aTagName   The name of the element to create.
     * @return HTMLElement              A known HTMLElement or HTMLUnknownElement.
     */
    public function createElement( $aTagName ) {
        $node = new Element($aTagName);
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

    /**
     * Returns a list of all the Element's that have all the given class names.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getelementsbyclassname
     *
     * @param  string       $aClassName A space delimited string containing the classNames to search for.
     *
     * @return Element[]
     */
    public function getElementsByClassName($aClassName) {
        return Element::_getElementsByClassName($this, $aClassName);
    }

    /**
     * Returns an array of Elements with the specified local name.
     *
     * @link https://dom.spec.whatwg.org/#dom-document-getelementsbytagname
     *
     * @param  string       $aLocalName The element's local name to search for.  If given '*',
     *                                  all element decendants will be returned.
     *
     * @return Element[]                A list of Elements with the specified local name.
     */
    public function getElementsByTagName($aLocalName) {
        return Element::_getElementsByTagName($this, $aLocalName);
    }

    /**
     * Returns a collection of Elements that match the given namespace and local name.
     *
     * @link https://dom.spec.whatwg.org/#dom-document-getelementsbytagnamens
     *
     * @param  string       $aNamespace The namespaceURI to search for.  If both namespace and local
     *                                  name are given '*', all element decendants will be returned.  If only
     *                                  namespace is given '*' all element decendants matching only local
     *                                  name will be returned.
     *
     * @param  string       $aLocalName The Element's local name to search for.  If both namespace and local
     *                                  name are given '*', all element decendants will be returned.  If only
     *                                  local name is given '*' all element decendants matching only namespace
     *                                  will be returned.
     *
     * @return Element[]
     */
    public function getElementsByTagNameNS($aNamespace, $aLocalName) {
        return Element::_getElementsByTagNameNS($this, $aNamespace, $aLocalName);
    }

    public function importNode(Node $aNode, $aDeep = false) {
        if ($aNode instanceof Document) {
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
        $oldDocument = $aNode->ownerDocument;

        if ($aNode->parentNode) {
            $aNode->parentNode->_removeChild($aNode);
        }

        $tw = $oldDocument->createTreeWalker($aNode);
        $node = $tw->root;

        while ($node) {
            $node->mOwnerDocument = $this;
            // TODO: Support adopting steps for nodes
            $node = $tw->nextNode();
        }
    }

    public function _getNodeIteratorCollection() {
        return $this->mNodeIteratorList;
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
}
