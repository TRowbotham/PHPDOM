<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Event\EventTarget;
use Rowbot\DOM\Exception\DOMException;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\NotFoundError;
use Rowbot\DOM\Support\OrderedSet;
use Rowbot\DOM\Support\UniquelyIdentifiable;
use Rowbot\DOM\Support\UuidTrait;

/**
 * @see https://dom.spec.whatwg.org/#node
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Node
 *
 * @property-read string         $baseURI
 *
 * @property-read Node[]         $childNodes
 *
 * @property-read Node|null      $firstChild
 *
 * @property-read Node|null      $lastChild
 *
 * @property-read Node|null      $nextSibling
 *
 * @property-read string         $nodeName
 *
 * @property-read int            $nodeType
 *
 * @property string|null         $nodeValue
 *
 * @property-read bool           $isConnected
 *
 * @property-read Document|null  $ownerDocument
 *
 * @property-read Node|null      $parentNode
 *
 * @property-read Element|null   $parentElement
 *
 * @property-read Node|null      $previousSibling
 *
 * @property string|null         $textContent
 */
abstract class Node extends EventTarget implements UniquelyIdentifiable
{
    use UuidTrait;

    const ELEMENT_NODE = 1;
    const ATTRIBUTE_NODE = 2;
    const TEXT_NODE = 3;
    const CDATA_SECTION_NODE = 4;
    const ENTITY_REFERENCE_NODE = 5;
    const ENTITY_NODE = 6;
    const PROCESSING_INSTRUCTION_NODE = 7;
    const COMMENT_NODE = 8;
    const DOCUMENT_NODE = 9;
    const DOCUMENT_TYPE_NODE = 10;
    const DOCUMENT_FRAGMENT_NODE = 11;
    const NOTATION_NODE = 12;

    const DOCUMENT_POSITION_DISCONNECTED = 0x01;
    const DOCUMENT_POSITION_PRECEDING = 0x02;
    const DOCUMENT_POSITION_FOLLOWING = 0x04;
    const DOCUMENT_POSITION_CONTAINS = 0x08;
    const DOCUMENT_POSITION_CONTAINED_BY = 0x10;
    const DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC = 0x20;

    protected $mChildNodes;
    protected $mNodeType; // int
    protected $mOwnerDocument; // Document
    protected $mParentNode; // Node
    protected $nextSibling;
    protected $nodeDocument;
    protected $nodeList;
    protected $previousSibling;
    protected static $mRefCount = 0;

    protected function __construct()
    {
        parent::__construct();

        $this->mChildNodes = new OrderedSet();
        $this->nodeList = new NodeList($this->mChildNodes);
        $this->mNodeType = '';
        $this->nodeDocument = Document::_getDefaultDocument();
        $this->mParentNode = null;
        $this->previousSibling = null;
        $this->nextSibling = null;
        self::$mRefCount++;
    }

    public function __destruct()
    {
        $this->mChildNodes = null;
        $this->nodeDocument = null;
        $this->mParentNode = null;
        $this->previousSibling = null;
        $this->nextSibling = null;
        self::$mRefCount--;
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'baseURI':
                return $this->nodeDocument->getBaseURL()->serializeURL();

            case 'childNodes':
                return $this->nodeList;

            case 'firstChild':
                return $this->mChildNodes->first();

            case 'isConnected':
                $options = ['composed' => true];

                return $this->getRootNode($options) instanceof Document;

            case 'lastChild':
                return $this->mChildNodes->last();

            case 'nextSibling':
                return $this->nextSibling;

            case 'nodeName':
                return $this->getNodeName();

            case 'nodeType':
                return $this->mNodeType;

            case 'nodeValue':
                return $this->getNodeValue();

            case 'ownerDocument':
                return static::ownerDocument();

            case 'parentElement':
                return $this->parentElement();

            case 'parentNode':
                return $this->mParentNode;

            case 'previousSibling':
                return $this->previousSibling;

            case 'textContent':
                return $this->getTextContent();
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'nodeValue':
                $this->setNodeValue($aValue);

                break;

            case 'textContent':
                $this->setTextContent($aValue);
        }
    }

    /**
     * Appends a node to the parent node.  If the node being appended is already
     * associated with another parent node, it will be removed from that parent
     * node before being appended to the current parent node.
     *
     * @param Node $aNode A node representing an element on the page.
     *
     * @return Node The node that was just appended to the parent node.
     */
    public function appendChild(Node $aNode)
    {
        return $this->preinsertNode($aNode, null);
    }

    /**
     * Returns a copy of the node upon which the method was called.
     *
     * @see https://dom.spec.whatwg.org/#dom-node-clonenode
     *
     * @param boolean $aDeep If true, all child nodes and event listeners should
     *     be cloned as well.
     *
     * @return Node The copy of the node.
     *
     * @throws NotSupportedError If the node being cloned is a ShadowRoot.
     */
    public function cloneNode($aDeep = false)
    {
        if ($this instanceof ShadowRoot) {
            throw new NotSupportedError();
        }

        return $this->doCloneNode(null, $aDeep);
    }

    /**
     * Compares the position of a node against another node.
     *
     * @link https://dom.spec.whatwg.org/#dom-node-comparedocumentpositionother
     *
     * @param Node $aNode Node to compare position against.
     *
     * @return int A bitmask representing the nodes position.  Possible values
     *     are as follows:
     *         Node::DOCUMENT_POSITION_DISCONNECTED
     *         Node::DOCUMENT_POSITION_PRECEDING
     *         Node::DOCUMENT_POSITION_FOLLOWING
     *         Node::DOCUMENT_POSITION_CONTAINS
     *         Node::DOCUMENT_POSITION_CONTAINED_BY
     *         Node::DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC
     */
    public function compareDocumentPosition(Node $aOtherNode)
    {
        // If context object is other, then return zero.
        if ($this === $aOtherNode) {
            return 0;
        }

        $node1 = $aOtherNode;
        $node2 = $this;
        $attr1 = null;
        $attr2 = null;

        // If node1 is an attribute, then set attr1 to node1 and node1 to
        // attr1’s element.
        if ($node1 instanceof Attr) {
            $attr1 = $node1;
            $node1 = $attr1->ownerElement;
        }

        // If node2 is an attribute, then:
        if ($node2 instanceof Attr) {
            $attr2 = $node2;
            $node2 = $attr2->ownerElement;

            // If attr1 and node1 are non-null, and node2 is node1, then:
            if ($attr1 && $node1 && $node2 === $node1) {
                foreach ($node2->getAttributeList() as $attr) {
                    // If attr equals attr1, then return the result of adding
                    // DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC and
                    // DOCUMENT_POSITION_PRECEDING.
                    if ($attr->isEqualNode($attr1)) {
                        return self::DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC |
                            self::DOCUMENT_POSITION_PRECEDING;
                    }

                    // If attr equals attr2, then return the result of adding
                    // DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC and
                    // DOCUMENT_POSITION_FOLLOWING.
                    if ($attr->isEqualNode($attr2)) {
                        return self::DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC |
                            self::DOCUMENT_POSITION_FOLLOWING;
                    }
                }
            }
        }

        $node2Root = $node2->getRootNode();

        // If node1 or node2 is null, or node1’s root is not node2’s root, then
        // return the result of adding DOCUMENT_POSITION_DISCONNECTED,
        // DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC, and either
        // DOCUMENT_POSITION_PRECEDING or DOCUMENT_POSITION_FOLLOWING, with the
        // constraint that this is to be consistent, together.
        if ($node1 === null || $node2 === null ||
            $node1->getRootNode() !== $node2Root
        ) {
            $ret = self::DOCUMENT_POSITION_DISCONNECTED |
                self::DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC;
            $position = strcmp(
                spl_object_hash($node2),
                spl_object_hash($node1)
            );

            // Pointer comparison is supposed to be used to determine whether
            // a node is following or preceding another node in this case,
            // however, PHP does not have pointers. So, comparing their string
            // hashes is the closest thing we can do to get the desired result.
            // Testing shows that this intermittently returns the incorrect
            // result based on the object hash comparison, but I don't really
            // see any alternatives other than going back to always returning
            // the same value for everything.
            if ($position < 0) {
                return $ret | self::DOCUMENT_POSITION_PRECEDING;
            }

            return $ret | self::DOCUMENT_POSITION_FOLLOWING;
        }

        // If node1 is an ancestor of node2 and attr1 is null, or node1 is node2
        // and attr2 is non-null, then return the result of adding
        // DOCUMENT_POSITION_CONTAINS to DOCUMENT_POSITION_PRECEDING.
        if (($node1->isAncestorOf($node2) && $attr1 === null) ||
            ($node1 === $node2 && $attr1)
        ) {
            return self::DOCUMENT_POSITION_CONTAINS |
                self::DOCUMENT_POSITION_PRECEDING;
        }

        // If node1 is a descendant of node2 and attr2 is null, or node1 is
        // node2 and attr1 is non-null, then return the result of adding
        // DOCUMENT_POSITION_CONTAINED_BY to DOCUMENT_POSITION_FOLLOWING.
        if (($node1->isDescendantOf($node2) && $attr2 === null) ||
            ($node1 === $node2 && $attr1)
        ) {
            return self::DOCUMENT_POSITION_CONTAINED_BY |
                self::DOCUMENT_POSITION_FOLLOWING;
        }

        $tw = new TreeWalker(
            $node2Root,
            NodeFilter::SHOW_ALL,
            function ($aNode) use ($node1) {
                return $aNode === $node1 ? NodeFilter::FILTER_ACCEPT :
                    NodeFilter::FILTER_SKIP;
            }
        );
        $tw->currentNode = $node2;

        // If node1 is preceding node2, then return DOCUMENT_POSITION_PRECEDING.
        //
        // NOTE: Due to the way attributes are handled in this algorithm this
        // results in a node’s attributes counting as preceding that node’s
        // children, despite attributes not participating in a tree.
        if ($tw->previousNode()) {
            return self::DOCUMENT_POSITION_PRECEDING;
        }

        // Return DOCUMENT_POSITION_FOLLOWING.
        return self::DOCUMENT_POSITION_FOLLOWING;
    }

    /**
     * Returns whether or not a node is an inclusive descendant of another node.
     *
     * @param Node $aNode A node that you wanted to compare its position of.
     *
     * @return boolean Returns true if $aNode is an inclusive descendant of a
     *     node.
     */
    public function contains(Node $aNode = null)
    {
        $node = $aNode;

        while ($node) {
            if ($node === $this) {
                return true;
            }

            $node = $node->mParentNode;
        }

        return false;
    }

    /**
     * Clones the given node and performs any node specific cloning steps
     * if the interface defines them.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-clone
     *
     * @param Node $aNode The node being cloned.
     *
     * @param Document|null $aDocument The document that will own the cloned
     *     node.
     *
     * @param bool $aCloneChildren Optional. If set, all children of the cloned
     *     node will also be cloned.
     *
     * @return Node The newly created node.
     */
    public function doCloneNode(
        Document $aDocument = null,
        $aCloneChildren = null
    ) {
        $node = $this;
        $document = $aDocument ?: $node->nodeDocument;

        switch ($node->mNodeType) {
            case self::ELEMENT_NODE:
                $copy = ElementFactory::create(
                    $document,
                    $node->mLocalName,
                    $node->mNamespaceURI,
                    $node->mPrefix
                );

                foreach ($node->mAttributesList as $attr) {
                    $copyAttribute = $attr->doCloneNode();
                    $copy->mAttributesList->append($copyAttribute);
                }

                break;

            case self::DOCUMENT_NODE:
                $copy = new static();
                $copy->mCharacterSet = $node->mCharacterSet;
                $copy->mContentType = $node->mContentType;
                $copy->mMode = $node->mMode;

                break;

            case self::DOCUMENT_TYPE_NODE:
                $copy = new static(
                    $this->mName,
                    $this->mPublicId,
                    $this->mSystemId
                );

                break;

            case self::ATTRIBUTE_NODE:
                // Set copy's namespace, namespace prefix, local name, and
                // value, to those of node.
                $copy = new static(
                    $this->mLocalName,
                    $this->mValue,
                    $this->mNamespaceURI,
                    $this->mPrefix
                );

                break;

            case self::TEXT_NODE:
            case self::COMMENT_NODE:
                $copy = new static($node->mData);

                break;

            case self::PROCESSING_INSTRUCTION_NODE:
                $copy = new static($node->mTarget, $node->mData);

                break;

            default:
                // If we have reached this point, then we don't know what type
                // of Node is being cloned. This is probably a bad thing.
                $copy = new static();
        }

        if ($copy instanceof Document) {
            $copy->nodeDocument = $copy;
            $document = $copy;
        } else {
            $copy->nodeDocument = $document;
        }

        // If the node being cloned defines custom cloning steps, perform them
        // now.
        if (method_exists($node, 'doCloningSteps')) {
            $this->doCloningSteps($copy, $document, $aCloneChildren);
        }

        if ($aCloneChildren) {
            foreach ($node->mChildNodes as $child) {
                $copyChild = $child->doCloneNode($document, true);
                $copy->appendChild($copyChild);
            }
        }

        return $copy;
    }

    /**
     * Ensures that a node is allowed to be inserted into its parent.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-node-ensure-pre-insertion-validity
     *
     * @param DocumentFragment|Node $aNode The nodes being inserted into the
     *     document tree.
     *
     * @param Node $aChild The reference node for where the new nodes should be
     *     inserted.
     *
     * @throws HierarchyRequestError
     *
     * @throws NotFoundError
     */
    public function ensurePreinsertionValidity($aNode, $aChild)
    {
        $parent = $this;

        // Only Documents, DocumentFragments, and Elements can be parent nodes.
        // Throw a HierarchyRequestError if parent is not one of these types.
        if (!($parent instanceof Document) &&
            !($parent instanceof DocumentFragment) &&
            !($parent instanceof Element)
        ) {
            throw new HierarchyRequestError();
        }

        // If node is a host-including inclusive ancestor of parent, throw a
        // HierarchyRequestError.
        if ($aNode->isHostIncludingInclusiveAncestorOf($parent)) {
            throw new HierarchyRequestError();
        }

        // If child is not null and its parent is not parent, then throw a
        // NotFoundError.
        if ($aChild !== null && $aChild->mParentNode !== $parent) {
            throw new NotFoundError();
        }

        // If node is not a DocumentFragment, DocumentType, Element, Text,
        // ProcessingInstruction, or Comment node, throw a
        // HierarchyRequestError.
        if (!($aNode instanceof DocumentFragment) &&
            !($aNode instanceof DocumentType) &&
            !($aNode instanceof Element) &&
            !($aNode instanceof Text) &&
            !($aNode instanceof ProcessingInstruction) &&
            !($aNode instanceof Comment)
        ) {
            throw new HierarchyRequestError();
        }

        // If either node is a Text node and parent is a document, or node is a
        // doctype and parent is not a document, throw a HierarchyRequestError.
        if (($aNode instanceof Text && $parent instanceof Document) ||
            ($aNode instanceof DocumentType && !($parent instanceof Document))
        ) {
            throw new HierarchyRequestError();
        }

        if (!($parent instanceof Document)) {
            return;
        }

        if ($aNode instanceof DocumentFragment) {
            $elementChildren = 0;

            // Documents cannot contain more than one element child or text
            // nodes. Throw a HierarchyRequestError if the document fragment
            // has more than 1 element child or a text node.
            foreach ($aNode->mChildNodes as $child) {
                if ($child instanceof Element) {
                    $elementChildren++;

                    if ($elementChildren > 1) {
                        throw new HierarchyRequestError();
                    }
                }

                if ($elementChildren > 1 || $child instanceof Text) {
                    throw new HierarchyRequestError();
                }
            }

            if ($elementChildren == 0) {
                return;
            }

            // Documents cannot contain more than one element child. Throw a
            // HierarchyRequestError if both the document fragment and
            // document contain an element child.
            foreach ($parent->mChildNodes as $child) {
                if ($child->mNodeType === self::ELEMENT_NODE) {
                    throw new HierarchyRequestError();
                }
            }

            // An element cannot preceed a doctype in the tree. Throw a
            // HierarchyRequestError if we try to insert an element before
            // the doctype.
            if ($aChild instanceof DocumentType) {
                throw new HierarchyRequestError();
            }

            if ($aChild === null) {
                return;
            }

            // The document element must follow the doctype in the tree.
            // Throw a HierarchyRequestError if we try to insert an element
            // before a node that preceedes the doctype.
            $tw = new TreeWalker($parent, NodeFilter::SHOW_DOCUMENT_TYPE);
            $tw->currentNode = $aChild;

            if ($tw->nextNode()) {
                throw new HierarchyRequestError();
            }
        } elseif ($aNode instanceof Element) {
            // A Document cannot contain more than 1 element child. Throw a
            // HierarchyRequestError if the parent already contains an element
            // child.
            foreach ($parent->mChildNodes as $child) {
                if ($child instanceof Element) {
                    throw new HierarchyRequestError();
                }
            }

            // The document element must follow the doctype in the tree. Throw
            // a HierarchyRequestError if we try to insert an element before the
            // doctype.
            if ($aChild instanceof DocumentType) {
                throw new HierarchyRequestError();
            }

            if ($aChild === null) {
                return;
            }

            // Again, the document element must follow the doctype in the tree.
            // Throw a HierarchyRequestError if we try to insert an element
            // before a node that preceedes the doctype.
            $tw = new TreeWalker($parent, NodeFilter::SHOW_DOCUMENT_TYPE);
            $tw->currentNode = $aChild;

            if ($tw->nextNode()) {
                throw new HierarchyRequestError();
            }
        } elseif ($aNode instanceof DocumentType) {
            // A document can only contain 1 doctype definition. Throw a
            // HierarchyRequestError if we try to insert a doctype into a
            // document that already contains a doctype.
            foreach ($parent->mChildNodes as $child) {
                if ($child instanceof DocumentType) {
                    throw new HierarchyRequestError();
                }
            }

            // The doctype must preceed any elements. Throw a
            // HierarchyRequestError if we try to insert a doctype before a
            // node that follows an element.
            if ($aChild !== null) {
                $tw = new TreeWalker($parent, NodeFilter::SHOW_ELEMENT);
                $tw->currentNode = $aChild;

                if ($tw->previousNode()) {
                    throw new HierarchyRequestError();
                }

                return;
            }

            // The doctype must preceed any elements. Throw a
            // HierarchyRequestError if we try to append a doctype to a parent
            // that already contains an element.
            foreach ($parent->mChildNodes as $child) {
                if ($child instanceof Element) {
                    throw new HierarchyRequestError();
                }
            }
        }
    }

    /**
     * Returns null if the node is a document, and the node's node document
     * otherwise.
     *
     * @return Document|null
     */
    public function ownerDocument()
    {
        return $this->nodeDocument;
    }

    /**
     * Returns the node's parent element.
     *
     * @internal
     *
     * @return Element|null
     */
    public function parentElement()
    {
        return $this->mParentNode instanceof Element
            ? $this->mParentNode
            : null;
    }

    /**
     * Gets the bottom most common ancestor of two nodes, if any.  If null is
     * returned, the two nodes do not have a common ancestor.
     *
     * @internal
     */
    public static function _getCommonAncestor(Node $aNodeA, Node $aNodeB)
    {
        $nodeA = $aNodeA;

        while ($nodeA) {
            $nodeB = $aNodeB;

            while ($nodeB) {
                if ($nodeB === $nodeA) {
                    break 2;
                }

                $nodeB = $nodeB->mParentNode;
            }

            $nodeA = $nodeA->mParentNode;
        }

        return $nodeA;
    }

    /**
     * Returns the Node's length.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-length
     *
     * @return int
     */
    abstract public function getLength();

    /**
     * Returns the Node's index.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-tree-index
     *
     * @return int
     */
    public function _getTreeIndex()
    {
        return $this->mParentNode->mChildNodes->indexOf($this);
    }

    /**
     * Returns a boolean indicating whether or not the current node contains any
     * nodes.
     *
     * @return boolean Returns true if at least one child node is present,
     *     otherwise false.
     */
    public function hasChildNodes()
    {
        return !$this->mChildNodes->isEmpty();
    }

    /**
     * Inserts a node before another node in a common parent node.
     *
     * @param Node $aNewNode The node to be inserted into the document.
     *
     * @param Node $aRefNode The node that the new node will be inserted before.
     *
     * @return Node The node that was inserted into the document.
     */
    public function insertBefore(Node $aNewNode, Node $aRefNode = null)
    {
        return $this->preinsertNode($aNewNode, $aRefNode);
    }

    /**
     * Inserts a node in to another node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-insert
     *
     * @param Node $aNode The nodes to be inserted into the document tree.
     *
     * @param Node|null $aChild Optional. A child node used as a reference to
     *     where the new node should be inserted.
     *
     * @param bool|null $aSuppressObservers Optional. If true, mutation events
     *     are ignored for this operation.
     */
    public function insertNode(
        Node $aNode,
        Node $aChild = null,
        $aSuppressObservers = null
    ) {
        $parent = $this;
        $nodeIsFragment = $aNode->mNodeType === self::DOCUMENT_FRAGMENT_NODE;
        $count = $nodeIsFragment ? count($aNode->mChildNodes) : 1;

        if ($aChild) {
            $childIndex = $aChild->mParentNode->mChildNodes->indexOf($aChild);

            foreach (Range::_getRangeCollection() as $range) {
                $startContainer = $range->startContainer;
                $startOffset = $range->startOffset;
                $endContainer = $range->endContainer;
                $endOffset = $range->endOffset;

                if ($startContainer === $parent &&
                    $startOffset > $childIndex
                ) {
                    $range->setStart($startContainer, $startOffset + $count);
                }

                if ($endContainer === $parent &&
                    $endOffset > $childIndex
                ) {
                    $range->setEnd($endContainer, $endOffset + $count);
                }
            }
        }

        $nodes = $nodeIsFragment ? $aNode->mChildNodes->values() : [$aNode];

        if ($nodeIsFragment) {
            foreach (clone $aNode->mChildNodes as $child) {
                $aNode->removeNode($child, true);
            }

            // TODO: queue a mutation record of "childList" for node with
            // removedNodes nodes.
        }

        foreach ($nodes as $node) {
            if (!$aChild) {
                $last = $this->mChildNodes->last();
            }

            $node->mParentNode = $parent;
            $parent->mChildNodes->insertBefore($aChild, $node);

            // TODO: For each inclusive descendant inclusiveDescendant of node,
            // in tree order, run the insertion steps with inclusiveDescendant
            // and parent.

            $iter = new NodeIterator($node);

            while (($descendant = $iter->nextNode())) {
                if (method_exists($descendant, 'doInsertingSteps')) {
                    $descendant->doInsertingSteps();
                }
            }

            if ($aChild) {
                $oldPreviousSibling = $aChild->previousSibling;
                $nextSibling = $aChild;
                $aChild->previousSibling = $node;
            } else {
                $oldPreviousSibling = $last;
                $nextSibling = null;
            }

            if ($oldPreviousSibling) {
                $oldPreviousSibling->nextSibling = $node;
            }

            $node->previousSibling = $oldPreviousSibling;
            $node->nextSibling = $nextSibling;
        }

        if (!$aSuppressObservers) {
            // TODO: If suppress observers flag is unset, queue a mutation
            // record of "childList" for parent with addedNodes nodes,
            // nextSibling child, and previousSibling child’s previous
            // sibling or parent’s last child if child is null.
        }
    }

    /**
     * Returns whether or not the namespace of the node is the node's default
     * namespace.
     *
     * @link https://dom.spec.whatwg.org/#dom-node-isdefaultnamespace
     *
     * @param string|null $aNamespace A namespaceURI to check against.
     *
     * @return bool
     */
    public function isDefaultNamespace($aNamespace)
    {
        $namespace = Utils::DOMString($aNamespace, false, true);

        if ($namespace === '') {
            $namespace = null;
        }

        $defaultNamespace = Namespaces::locateNamespace($this, null);

        return $defaultNamespace === $namespace;
    }

    /**
     * Compares two nodes to see if they are equal.
     *
     * @link https://dom.spec.whatwg.org/#dom-node-isequalnode
     * @link https://dom.spec.whatwg.org/#concept-node-equals
     *
     * @param Node $aNode The node you want to compare the current node to.
     *
     * @return boolean Returns true if the two nodes are the same, otherwise
     *     false.
     */
    public function isEqualNode(Node $aOtherNode = null)
    {
        if (!$aOtherNode || $this->mNodeType != $aOtherNode->mNodeType) {
            return false;
        }

        if ($this instanceof DocumentType) {
            if (
                strcmp($this->name, $aOtherNode->name) !== 0 ||
                strcmp($this->publicId, $aOtherNode->publicId) !== 0 ||
                strcmp($this->systemId, $aOtherNode->systemId) !== 0
            ) {
                return false;
            }
        } elseif ($this instanceof Element) {
            if (
                strcmp($this->namespaceURI, $aOtherNode->namespaceURI) !== 0 ||
                strcmp($this->prefix, $aOtherNode->prefix) !== 0 ||
                strcmp($this->localName, $aOtherNode->localName) !== 0 ||
                $this->mAttributesList->count() !==
                $aOtherNode->attributes->length
            ) {
                return false;
            }
        } elseif ($this instanceof Attr) {
            if ($this->namespaceURI !== $aOtherNode->namespaceURI ||
                $this->localName !== $aOtherNode->localName ||
                $this->value !== $aOtherNode->value
            ) {
                return false;
            }
        } elseif ($this instanceof ProcessingInstruction) {
            if (strcmp($this->target, $aOtherNode->target) !== 0 ||
                strcmp($this->data, $aOtherNode->data) !== 0) {
                return false;
            }
        } elseif ($this instanceof Text || $this instanceof Comment) {
            if (strcmp($this->data, $aOtherNode->data) !== 0) {
                return false;
            }
        }

        if ($this instanceof Element) {
            foreach ($this->mAttributesList as $i => $attribute) {
                $isEqual = $attribute->isEqualNode(
                    $aOtherNode->mAttributesList[$i]
                );

                if (!$isEqual) {
                    return false;
                }
            }
        }

        $childNodeCount = count($this->mChildNodes);

        if ($childNodeCount !== count($aOtherNode->childNodes)) {
            return false;
        }

        for ($i = 0; $i < $childNodeCount; $i++) {
            if (!$this->mChildNodes[$i]->isEqualNode(
                    $aOtherNode->childNodes[$i]
                )) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if another node is the same node as this node.  This is equivilant
     * to using the strict equality operator (===).
     *
     * @see https://dom.spec.whatwg.org/#dom-node-issamenode
     *
     * @param Node|null $aOtherNode Optional. The node whose equality is to be
     *     checked.
     *
     * @return bool
     */
    public function isSameNode(Node $aOtherNode = null)
    {
        return $this === $aOtherNode;
    }

    /**
     * Finds the namespace associated with the given prefix.
     *
     * @link https://dom.spec.whatwg.org/#dom-node-lookupnamespaceuri
     *
     * @param string|null $aPrefix The prefix of the namespace to be found.
     *
     * @return string|null
     */
    public function lookupNamespaceURI($aPrefix)
    {
        $prefix = Utils::DOMString($aPrefix, false, true);

        if ($prefix === '') {
            $prefix = null;
        }

        return Namespaces::locateNamespace($this, $prefix);
    }

    /**
     * Finds the prefix associated with the given namespace on the given node.
     *
     * @link https://dom.spec.whatwg.org/#dom-node-lookupprefix
     *
     * @param string|null $aNamespace The namespace of the prefix to be found.
     *
     * @return string|null
     */
    public function lookupPrefix($aNamespace)
    {
        $namespace = Utils::DOMString($aNamespace, false, true);

        if ($namespace === null || $namespace === '') {
            return null;
        }

        switch ($this->mNodeType) {
            case self::ELEMENT_NODE:
                return Namespaces::locatePrefix($this, $namespace);

            case self::DOCUMENT_NODE:
                return Namespaces::locatePrefix(
                    $this->getFirstElementChild(),
                    $namespace
                );

            case self::DOCUMENT_TYPE_NODE:
            case self::DOCUMENT_FRAGMENT_NODE:
                return null;

            case self::ATTRIBUTE_NODE:
                $ownerElement = $this->ownerElement;

                // Return the result of locating a namespace prefix for its
                // element, if its element is non-null, and null otherwise.
                if ($ownerElement) {
                    return Namespaces::locatePrefix($ownerElement, $namespace);
                }

                return null;

            default:
                return ($parentElement = $this->parentElement())
                    ? Namespaces::locatePrefix($parentElement, $namespace)
                    : null;
        }
    }

    /**
     * "Normalizes" the node and its sub-tree so that there are no empty text
     * nodes present and there are no text nodes that appear consecutively.
     *
     * @see https://dom.spec.whatwg.org/#dom-node-normalize
     */
    public function normalize()
    {
        $iter = $this->nodeDocument->createNodeIterator(
            $this,
            NodeFilter::SHOW_TEXT
        );

        while ($node = $iter->nextNode()) {
            $length = $node->getLength();

            // If length is zero, then remove node and continue with the next
            // exclusive Text node, if any.
            if ($length == 0) {
                $node->mParentNode->removeNode($node);
                continue;
            }

            // Let data be the concatenation of the data of node’s contiguous
            // exclusive Text nodes (excluding itself), in tree order.
            $data = '';
            $contingiousTextNodes = [];
            $startNode = $node->previousSibling;

            while ($startNode) {
                if ($startNode->mNodeType != self::TEXT_NODE) {
                    break;
                }

                $data .= $startNode->data;
                $contingiousTextNodes[] = $startNode;
                $startNode = $startNode->previousSibling;
            }

            $startNode = $node->nextSibling;

            while ($startNode) {
                if ($startNode->mNodeType != self::TEXT_NODE) {
                    break;
                }

                $data .= $startNode->data;
                $contingiousTextNodes[] = $startNode;
                $startNode = $startNode->nextSibling;
            }

            // Replace data with node node, offset length, count 0, and data
            // data.
            $node->doReplaceData($length, 0, $data);
            $ranges = Range::_getRangeCollection();

            foreach (clone $node->mChildNodes as $currentNode) {
                if ($currentNode->mNodeType != self::TEXT_NODE) {
                    break;
                }

                $treeIndex = $currentNode->_getTreeIndex();

                // For each range whose start node is currentNode, add length to
                // its start offset and set its start node to node.
                foreach ($ranges as $range) {
                    if ($range->startContainer === $currentNode) {
                        $range->setStart($node, $range->startOffset + $length);
                    }
                }

                // For each range whose end node is currentNode, add length to
                // its end offset and set its end node to node.
                foreach ($ranges as $range) {
                    if ($range->endContainer === $currentNode) {
                        $range->setEnd($node, $range->endOffset + $length);
                    }
                }

                // For each range whose start node is currentNode’s parent and
                // start offset is currentNode’s index, set its start node to
                // node and its start offset to length.
                foreach ($ranges as $range) {
                    if ($range->startContainer === $currentNode->mParentNode &&
                        $range->startOffset == $treeIndex
                    ) {
                        $range->setStart($node, $length);
                    }
                }

                // For each range whose end node is currentNode’s parent and end
                // offset is currentNode’s index, set its end node to node and
                // its end offset to length.
                foreach ($ranges as $range) {
                    if ($range->endContainer === $currentNode->mParentNode &&
                        $range->endOffset == $treeIndex
                    ) {
                        $range->setEnd($node, $length);
                    }
                }

                // Add currentNode’s length to length.
                $length += $currentNode->getLength();
            }

            // Remove node’s contiguous exclusive Text nodes (excluding itself),
            // in tree order.
            foreach ($contingiousTextNodes as $textNode) {
                $textNode->mParentNode->removeNode($textNode);
            }
        }
    }

    /**
     * Performs additional validation and preparation steps prior to inserting
     * a node in to a parent node, optionally, in relation to another child
     * node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-pre-insert
     *
     * @param Node $aNode The node being inserted.
     *
     * @param Node|null $aChild Optional.  A child node used as a reference to
     *     where the new node should be inserted.
     *
     * @return Node The node that was inserted.
     */
    public function preinsertNode(
        Node $aNode,
        Node $aChild = null
    ) {
        $parent = $this;
        $parent->ensurePreinsertionValidity($aNode, $aChild);
        $referenceChild = $aChild;

        if ($referenceChild === $aNode) {
            $referenceChild = $aNode->nextSibling;
        }

        // The DOM4 spec states that nodes should be implicitly adopted
        $parent->nodeDocument->doAdoptNode($aNode);
        $parent->insertNode($aNode, $referenceChild);

        return $aNode;
    }

    /**
     * Removes the specified node from the current node.
     *
     * @param Node $aNode The node to be removed from the DOM.
     *
     * @return Node The node that was removed from the DOM.
     */
    public function removeChild(Node $aNode)
    {
        return $this->preremoveNode($aNode);
    }

    /**
     * Removes a node from its parent node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-remove
     *
     * @param Node $aNode The Node to be removed from the document tree.
     *
     * @param bool $aSuppressObservers Optional. If true, mutation events are
     *     ignored for this operation.
     */
    public function removeNode(
        Node $aNode,
        $aSuppressObservers = null
    ) {
        $parent = $this;
        $index = $parent->mChildNodes->indexOf($aNode);
        $ranges = Range::_getRangeCollection();

        foreach ($ranges as $range) {
            $startContainer = $range->startContainer;

            if ($startContainer === $aNode ||
                $aNode->contains($startContainer)
            ) {
                $range->setStart($parent, $index);
            }
        }

        foreach ($ranges as $range) {
            $endContainer = $range->endContainer;

            if ($endContainer === $aNode || $aNode->contains($endContainer)) {
                $range->setEnd($parent, $index);
            }
        }

        foreach ($ranges as $range) {
            $startContainer = $range->startContainer;
            $startOffset = $range->startOffset;

            if ($startContainer === $parent && $startOffset > $index) {
                $range->setStart($startContainer, $startOffset - 1);
            }
        }

        foreach ($ranges as $range) {
            $endContainer = $range->endContainer;
            $endOffset = $range->endOffset;

            if ($endContainer === $parent && $endOffset > $index) {
                $range->setEnd($endContainer, $endOffset - 1);
            }
        }

        $iterCollection = $aNode->nodeDocument->_getNodeIteratorCollection();

        foreach ($iterCollection as $iter) {
            $iter->_preremove($aNode);
        }

        $oldPreviousSibling = $aNode->previousSibling;
        $oldNextSibling = $aNode->nextSibling;
        $parent->mChildNodes->remove($aNode);

        // For each inclusive descendant inclusiveDescendant of node, run
        // the removing steps with inclusiveDescendant and parent.
        $iter = new NodeIterator($aNode);

        while (($descendant = $iter->nextNode())) {
            if (method_exists($descendant, 'doRemovingSteps')) {
                $descendant->doRemovingSteps($parent);
            }
        }

        if ($oldPreviousSibling) {
            $oldPreviousSibling->nextSibling = $oldNextSibling;
        }

        if ($oldNextSibling) {
            $oldNextSibling->previousSibling = $oldPreviousSibling;
        }

        $aNode->nextSibling = null;
        $aNode->previousSibling = null;
        $aNode->mParentNode = null;

        // TODO: For each inclusive ancestor inclusiveAncestor of parent, if
        // inclusiveAncestor has any registered observers whose options' subtree
        // is true, then for each such registered observer registered, append a
        // transient registered observer whose observer and options are
        // identical to those of registered and source which is registered to
        // node’s list of registered observers.

        if (!$aSuppressObservers) {
            // TODO: If suppress observers flag is unset, queue a mutation
            // record of "childList" for parent with removedNodes a list solely
            // containing node, nextSibling oldNextSibling, and previousSibling
            // oldPreviousSibling.
        }
    }

    /**
     * Replaces a node with another node.
     *
     * @see https://dom.spec.whatwg.org/#dom-node-replacechild
     *
     * @param Node $aNewNode The node to be inserted into the DOM.
     *
     * @param Node $aOldNode The node that is being replaced by the new node.
     *
     * @return Node The node that was replaced in the DOM.
     *
     * @throws HierarchyRequestError
     *
     * @throws NotFoundError
     */
    public function replaceChild(Node $aNewNode, Node $aOldNode)
    {
        return $this->replaceNode($aNewNode, $aOldNode);
    }

    /**
     * Gets the node's node document.
     *
     * @internal
     *
     * @return Document
     */
    public function getNodeDocument()
    {
        return $this->nodeDocument;
    }

    /**
     * Sets the node's node document.
     *
     * @internal
     *
     * @param Document $aNode The Document object that owns this Node.
     */
    public function setNodeDocument(Document $aDocument)
    {
        if ($this->mNodeType !== self::DOCUMENT_NODE) {
            $this->nodeDocument = $aDocument;
        }
    }

    /**
     * Replaces all nodes within a parent.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-node-replace-all
     *
     * @param Node|null $aNode The node that is to be inserted.
     */
    public function _replaceAll(Node $aNode = null)
    {
        if ($aNode) {
            $this->nodeDocument->doAdoptNode($aNode);
        }

        $removedNodes = $this->mChildNodes->values();

        if (!$aNode) {
            $addedNodes = array();
        } elseif ($aNode instanceof DocumentFragment) {
            $addedNodes = $aNode->mChildNodes->values();
        } else {
            $addedNodes = array($aNode);
        }

        foreach ($removedNodes as $index => $node) {
            $this->removeNode($node, true);
        }

        if ($aNode) {
            $this->insertNode($aNode, null, true);
        }

        // TODO: Queue a mutation record for "childList"
    }

    /**
     * Converts an array of Nodes and strings and creates a single node,
     * such as a DocumentFragment.  Any strings contained in the array will be
     * turned in to Text nodes.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#converting-nodes-into-a-node
     *
     * @param array $aNodes An array of Nodes and strings.
     *
     * @param Document $aDocument Context object's node document.
     *
     * @return DocumentFragment|Node If $aNodes > 1, then a DocumentFragment is
     *     returned, otherwise a single Node is returned.
     */
    protected static function convertNodesToNode($aNodes, $aDocument)
    {
        $node = null;

        // Replace each string in nodes with a new Text node whose data is the
        // string and node document is document.
        foreach ($aNodes as &$potentialNode) {
            if (!($potentialNode instanceof self)) {
                $potentialNode = new Text(Utils::DOMString($potentialNode));
                $potentialNode->nodeDocument = $aDocument;
            }
        }

        // If nodes contains one node, set node to that node. Otherwise, set
        // node to a new DocumentFragment whose node document is document, and
        // then append each node in nodes, if any, to it. Rethrow any
        // exceptions.
        if (count($aNodes) == 1) {
            $node = $aNodes[0];
        } else {
            $node = new DocumentFragment();
            $node->nodeDocument = $aDocument;

            try {
                foreach ($aNodes as $child) {
                    $node->appendChild($child);
                }
            } catch (DOMException $e) {
                throw $e;
            }
        }

        return $node;
    }

    /**
     * Gets the name of the node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodename
     *
     * @return string
     */
    abstract protected function getNodeName();

    /**
     * Gets the value of the node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodevalue
     *
     * @return string|null
     */
    abstract protected function getNodeValue();

    /**
     * Gets a node's root.
     *
     * @see https://dom.spec.whatwg.org/#concept-tree-root
     *
     * @param array $aOptions The only valid argument is a key named "composed"
     *     with a boolean value.
     *
     * @return Node If the value of the "composed" key is true, then the
     *     shadow-including root will be returned, otherwise, the root will be
     *     returned.
     */
    public function getRootNode($aOptions = [])
    {
        $root = $this;

        while ($root->mParentNode) {
            $root = $root->mParentNode;
        }

        if (isset($aOptions['composed']) && $aOptions['composed'] === true &&
            $root instanceof ShadowRoot
        ) {
            $root = $root->host->getRootNode($aOptions);
        }

        return $root;
    }

    /**
     * Returns node's assigned slot, if node is assigned, node's parent
     * otherwise.
     *
     * @see EventTarget::getTheParent
     *
     * @param Event $aEvent An Event object.
     *
     * @return HTMLSlotElement|null
     */
    protected function getTheParent($aEvent)
    {
        // We currently don't support the HTMLSlotElement, so this will always
        // return the node's parent.
        return $this->mParentNode;
    }

    /**
     * Gets the concatenation of all descendant text nodes.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-textcontent
     *
     * @return string|null
     */
    abstract protected function getTextContent();

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-tree-ancestor
     *
     * @param Node|null $aOtherNode A Node.
     *
     * @return bool
     */
    public function isAncestorOf($aOtherNode)
    {
        while ($aOtherNode) {
            if ($aOtherNode->mParentNode === $this) {
                break;
            }

            $aOtherNode = $aOtherNode->mParentNode;
        }

        return $aOtherNode !== null;
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-tree-inclusive-ancestor
     *
     * @param Node|null $aOtherNode A Node.
     *
     * @return bool
     */
    public function isInclusiveAncestorOf($aOtherNode)
    {
        return $aOtherNode === $this || $this->isAncestorOf($aOtherNode);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-tree-descendant
     *
     * @param Node|null $aOtherNode A Node.
     *
     * @return bool
     */
    public function isDescendantOf($aOtherNode)
    {
        return $aOtherNode->isAncestorOf($this);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-tree-inclusive-descendant
     *
     * @param Node|null $aOtherNode A Node.
     *
     * @return bool
     */
    public function isInclusiveDescendantOf($aOtherNode)
    {
        return $aOtherNode === $this || $this->isDescendantOf($aOtherNode);
    }

    /**
     * Checks if the node is an inclusive ancestor of the given node, including
     * any nodes that may be hosted.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-tree-host-including-inclusive-ancestor
     *
     * @param Node $aNode The potential descendant node.
     *
     * @return bool Whether the node is an inclusive ancestor or not.
     */
    protected function isHostIncludingInclusiveAncestorOf(Node $aNode = null)
    {
        $isInclusiveAncestor = $this->isInclusiveAncestorOf($aNode);
        $root = null;
        $host = null;

        if (!$isInclusiveAncestor && $aNode) {
            $root = $aNode->getRootNode();

            if ($root instanceof DocumentFragment) {
                $host = $root->getHost();
            }
        }

        return $isInclusiveAncestor || ($root && $host &&
            $this->isHostIncludingInclusiveAncestorOf($host));
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-shadow-including-descendant
     *
     * @param Node|null $aOtherNode A Node.
     *
     * @return bool
     */
    public function isShadowIncludingDescendantOf($aOtherNode)
    {
        $isDescendant = $this->isDescendantOf($aOtherNode);
        $root = null;

        if (!$isDescendant) {
            $root = $this->getRootNode();
        }

        return $isDescendant || ($root && $root instanceof ShadowRoot &&
            $root->host->isShadowIncludingInclusiveDescendantOf($aOtherNode));
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-shadow-including-inclusive-descendant
     *
     * @param Node|null $aOtherNode A Node.
     *
     * @return bool
     */
    public function isShadowIncludingInclusiveDescendantOf($aOtherNode)
    {
        return $this === $aOtherNode ||
            $this->isShadowIncludingDescendantOf($aOtherNode);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-shadow-including-ancestor
     *
     * @param Node|null $aOtherNode A Node.
     *
     * @return bool
     */
    public function isShadowIncludingAncestorOf($aOtherNode)
    {
        return $aOtherNode->isShadowIncludingDescendantOf($this);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-shadow-including-inclusive-ancestor
     *
     * @param Node|null $aOtherNode A Node.
     *
     * @return bool
     */
    public function isShadowIncludingInclusiveAncestorOf($aOtherNode)
    {
        return $this === $aOtherNode ||
            $this->isShadowIncludingAncestorOf($aOtherNode);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-closed-shadow-hidden
     *
     * @param Node|null $aOtherNode A Node.
     *
     * @return bool
     */
    public function isClosedShadowHiddenFrom($aOtherNode)
    {
        $root = $this->getRootNode();

        if ($root instanceof ShadowRoot &&
            !$root->isShadowIncludingInclusiveAncestorOf($aOtherNode) &&
            ($root->mode === 'closed' || $root->host->isClosedShadowHiddenFrom(
                $aOtherNode
            ))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Removes a node from another node after making sure that they share
     * the same parent node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-pre-remove
     *
     * @param Node $aChild The node being removed.
     *
     * @return Node The node that was removed.
     *
     * @throws NotFoundError If the parent of the node being removed does not
     *     match the given parent node.
     */
    protected function preremoveNode(Node $aChild)
    {
        $parent = $this;

        if ($aChild->mParentNode !== $parent) {
            throw new NotFoundError();
        }

        $parent->removeNode($aChild);

        return $aChild;
    }

    /**
     * Replaces a node with another node inside this node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-replace
     *
     * @param Node $aNode The node being inserted.
     *
     * @param Node $aChild The node being replaced.
     *
     * @return Node The node that was replaced.
     */
    protected function replaceNode(
        Node $aNode,
        Node $aChild
    ) {
        $parent = $this;

        switch ($parent->mNodeType) {
            case self::DOCUMENT_NODE:
            case self::DOCUMENT_FRAGMENT_NODE:
            case self::ELEMENT_NODE:
                break;

            default:
                throw new HierarchyRequestError();
        }

        if ($aNode->isHostIncludingInclusiveAncestorOf($parent)) {
            throw new HierarchyRequestError();
        }

        if ($aChild->mParentNode !== $parent) {
            throw new NotFoundError();
        }

        switch ($aNode->mNodeType) {
            case self::DOCUMENT_FRAGMENT_NODE:
            case self::DOCUMENT_TYPE_NODE:
            case self::ELEMENT_NODE:
            case self::TEXT_NODE:
            case self::PROCESSING_INSTRUCTION_NODE:
            case self::COMMENT_NODE:
                break;

            default:
                throw new HierarchyRequestError();
        }

        if ($aNode->mNodeType === self::TEXT_NODE &&
            $parent->mNodeType === self::DOCUMENT_NODE
        ) {
            throw new HierarchyRequestError();
        }

        if ($aNode->mNodeType === self::DOCUMENT_TYPE_NODE &&
            $parent->mNodeType !== self::DOCUMENT_NODE
        ) {
            throw new HierarchyRequestError();
        }

        if ($parent->mNodeType === self::DOCUMENT_NODE) {
            switch ($aNode->mNodeType) {
                case self::DOCUMENT_FRAGMENT_NODE:
                    $elementChildren = 0;

                    foreach ($aNode->mChildNodes as $child) {
                        switch ($child->mNodeType) {
                            case self::ELEMENT_NODE:
                                $elementChildren++;

                                if ($elementChildren > 1) {
                                    throw new HierarchyRequestError();
                                }

                                break;

                            case self::TEXT_NODE:
                                throw new HierarchyRequestError();
                        }
                    }

                    if ($elementChildren === 1) {
                        foreach ($parent->mChildNodes as $child) {
                            if ($child->mNodeType === self::ELEMENT_NODE &&
                                $child !== $aChild
                            ) {
                                throw new HierarchyRequestError();
                            }
                        }

                        $tw = new TreeWalker(
                            $parent,
                            NodeFilter::SHOW_DOCUMENT_TYPE
                        );
                        $tw->currentNode = $aChild;

                        if ($tw->nextNode()) {
                            throw new HierarchyRequestError();
                        }
                    }

                    break;

                case self::ELEMENT_NODE:
                    foreach ($parent->mChildNodes as $child) {
                        if ($child->mNodeType === self::ELEMENT_NODE &&
                            $child !== $aChild
                        ) {
                            throw new HierarchyRequestError();
                        }
                    }

                    $tw = new TreeWalker(
                        $parent,
                        NodeFilter::SHOW_DOCUMENT_TYPE
                    );
                    $tw->currentNode = $aChild;

                    if ($tw->nextNode()) {
                        throw new HierarchyRequestError();
                    }

                    break;

                case self::DOCUMENT_TYPE_NODE:
                    foreach ($parent->mChildNodes as $child) {
                        if ($child->mNodeType === self::DOCUMENT_TYPE_NODE &&
                            $child !== $aChild
                        ) {
                            throw new HierarchyRequestError();
                        }
                    }

                    $tw = new TreeWalker(
                        $parent,
                        NodeFilter::SHOW_ELEMENT
                    );
                    $tw->currentNode = $aChild;

                    if ($tw->previousNode()) {
                        throw new HierarchyRequestError();
                    }
            }
        }

        $referenceChild = $aChild->nextSibling;

        if ($referenceChild === $aNode) {
            $referenceChild = $aNode->nextSibling;
        }

        $previousSibling = $aChild->previousSibling;
        $parent->nodeDocument->doAdoptNode($aNode);
        $removedNodes = [];

        if ($aChild->mParentNode) {
            $removedNodes[] = $aChild;
            $aChild->mParentNode->removeNode($aChild, true);
        }

        $nodes = $aNode->mNodeType == self::DOCUMENT_FRAGMENT_NODE
            ? $aNode->mChildNodes->values()
            : [$aNode];
        $parent->insertNode($aNode, $referenceChild, true);

        // TODO: Queue a mutation record of "childList" for target parent with
        // addedNodes nodes, removedNodes removedNodes, nextSibling reference
        // child, and previousSibling previousSibling.

        return $aChild;
    }

    /**
     * Sets the node's value.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodevalue
     *
     * @param string $aNewValue The node's new value.
     */
    abstract protected function setNodeValue($aNewValue);

    /**
     * Sets the nodes text content.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-textcontent
     *
     * @param string|null $aNewValue The new text to be inserted into the node.
     */
    abstract protected function setTextContent($aNewValue);
}
