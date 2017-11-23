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

    const ELEMENT_NODE                = 1;
    const ATTRIBUTE_NODE              = 2;
    const TEXT_NODE                   = 3;
    const CDATA_SECTION_NODE          = 4;
    const ENTITY_REFERENCE_NODE       = 5;
    const ENTITY_NODE                 = 6;
    const PROCESSING_INSTRUCTION_NODE = 7;
    const COMMENT_NODE                = 8;
    const DOCUMENT_NODE               = 9;
    const DOCUMENT_TYPE_NODE          = 10;
    const DOCUMENT_FRAGMENT_NODE      = 11;
    const NOTATION_NODE               = 12;

    const DOCUMENT_POSITION_DISCONNECTED            = 0x01;
    const DOCUMENT_POSITION_PRECEDING               = 0x02;
    const DOCUMENT_POSITION_FOLLOWING               = 0x04;
    const DOCUMENT_POSITION_CONTAINS                = 0x08;
    const DOCUMENT_POSITION_CONTAINED_BY            = 0x10;
    const DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC = 0x20;

    protected $childNodes;
    protected $nodeType;
    protected $ownerDocument;
    protected $parentNode;
    protected $nextSibling;
    protected $nodeDocument;
    protected $nodeList;
    protected $previousSibling;

    protected function __construct()
    {
        parent::__construct();

        $this->childNodes = new OrderedSet();
        $this->nodeList = new NodeList($this->childNodes);
        $this->nodeType = '';
        $this->nodeDocument = Document::getDefaultDocument();
        $this->parentNode = null;
        $this->previousSibling = null;
        $this->nextSibling = null;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'baseURI':
                return $this->nodeDocument->getBaseURL()->serializeURL();

            case 'childNodes':
                return $this->nodeList;

            case 'firstChild':
                return $this->childNodes->first();

            case 'isConnected':
                $options = ['composed' => true];

                return $this->getRootNode($options) instanceof Document;

            case 'lastChild':
                return $this->childNodes->last();

            case 'nextSibling':
                return $this->nextSibling;

            case 'nodeName':
                return $this->getNodeName();

            case 'nodeType':
                return $this->nodeType;

            case 'nodeValue':
                return $this->getNodeValue();

            case 'ownerDocument':
                return static::ownerDocument();

            case 'parentElement':
                return $this->parentElement();

            case 'parentNode':
                return $this->parentNode;

            case 'previousSibling':
                return $this->previousSibling;

            case 'textContent':
                return $this->getTextContent();
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'nodeValue':
                $this->setNodeValue($value);

                break;

            case 'textContent':
                $this->setTextContent($value);
        }
    }

    /**
     * Appends a node to the parent node.  If the node being appended is already
     * associated with another parent node, it will be removed from that parent
     * node before being appended to the current parent node.
     *
     * @param Node $node A node representing an element on the page.
     *
     * @return Node The node that was just appended to the parent node.
     */
    public function appendChild(Node $node)
    {
        return $this->preinsertNode($node, null);
    }

    /**
     * Returns a copy of the node upon which the method was called.
     *
     * @see https://dom.spec.whatwg.org/#dom-node-clonenode
     *
     * @param boolean $deep If true, all child nodes and event listeners should
     *     be cloned as well.
     *
     * @return Node The copy of the node.
     *
     * @throws NotSupportedError If the node being cloned is a ShadowRoot.
     */
    public function cloneNode($deep = false)
    {
        if ($this instanceof ShadowRoot) {
            throw new NotSupportedError();
        }

        return $this->doCloneNode(null, $deep);
    }

    /**
     * Compares the position of a node against another node.
     *
     * @link https://dom.spec.whatwg.org/#dom-node-comparedocumentpositionother
     *
     * @param Node $otherNode Node to compare position against.
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
    public function compareDocumentPosition(Node $otherNode)
    {
        // If context object is other, then return zero.
        if ($this === $otherNode) {
            return 0;
        }

        $node1 = $otherNode;
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
        if ($node1 === null || $node2 === null
            || $node1->getRootNode() !== $node2Root
        ) {
            $ret = self::DOCUMENT_POSITION_DISCONNECTED
                | self::DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC;
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
        if (($node1->isAncestorOf($node2) && $attr1 === null)
            || ($node1 === $node2 && $attr1)
        ) {
            return self::DOCUMENT_POSITION_CONTAINS |
                self::DOCUMENT_POSITION_PRECEDING;
        }

        // If node1 is a descendant of node2 and attr2 is null, or node1 is
        // node2 and attr1 is non-null, then return the result of adding
        // DOCUMENT_POSITION_CONTAINED_BY to DOCUMENT_POSITION_FOLLOWING.
        if (($node1->isDescendantOf($node2) && $attr2 === null)
            || ($node1 === $node2 && $attr1)
        ) {
            return self::DOCUMENT_POSITION_CONTAINED_BY |
                self::DOCUMENT_POSITION_FOLLOWING;
        }

        $tw = new TreeWalker(
            $node2Root,
            NodeFilter::SHOW_ALL,
            function ($node) use ($node1) {
                if ($node === $node1) {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_SKIP;
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
     * @param Node $node A node that you wanted to compare its position of.
     *
     * @return boolean Returns true if $node is an inclusive descendant of a
     *     node.
     */
    public function contains(Node $node = null)
    {
        while ($node) {
            if ($node === $this) {
                return true;
            }

            $node = $node->parentNode;
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
     * @param Document|null $document The document that will own the cloned
     *     node.
     *
     * @param bool $cloneChildren Optional. If set, all children of the cloned
     *     node will also be cloned.
     *
     * @return Node The newly created node.
     */
    public function doCloneNode(
        Document $document = null,
        $cloneChildren = null
    ) {
        $node = $this;
        $document = $document ?: $node->nodeDocument;

        switch ($node->nodeType) {
            case self::ELEMENT_NODE:
                $copy = ElementFactory::create(
                    $document,
                    $node->localName,
                    $node->namespaceURI,
                    $node->prefix
                );

                foreach ($node->attributeList as $attr) {
                    $copyAttribute = $attr->doCloneNode();
                    $copy->attributeList->append($copyAttribute);
                }

                break;

            case self::DOCUMENT_NODE:
                $copy = new static();
                $copy->characterSet = $node->characterSet;
                $copy->contentType = $node->contentType;
                $copy->mode = $node->mode;

                break;

            case self::DOCUMENT_TYPE_NODE:
                $copy = new static(
                    $this->name,
                    $this->publicId,
                    $this->systemId
                );

                break;

            case self::ATTRIBUTE_NODE:
                // Set copy's namespace, namespace prefix, local name, and
                // value, to those of node.
                $copy = new static(
                    $this->localName,
                    $this->value,
                    $this->namespaceURI,
                    $this->prefix
                );

                break;

            case self::TEXT_NODE:
            case self::COMMENT_NODE:
                $copy = new static($node->data);

                break;

            case self::PROCESSING_INSTRUCTION_NODE:
                $copy = new static($node->target, $node->data);

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
            $this->doCloningSteps($copy, $document, $cloneChildren);
        }

        if ($cloneChildren) {
            foreach ($node->childNodes as $child) {
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
     * @param DocumentFragment|Node $node The nodes being inserted into the
     *     document tree.
     *
     * @param Node $child The reference node for where the new nodes should be
     *     inserted.
     *
     * @throws HierarchyRequestError
     *
     * @throws NotFoundError
     */
    public function ensurePreinsertionValidity($node, $child)
    {
        $parent = $this;

        // Only Documents, DocumentFragments, and Elements can be parent nodes.
        // Throw a HierarchyRequestError if parent is not one of these types.
        if (!($parent instanceof Document)
            && !($parent instanceof DocumentFragment)
            && !($parent instanceof Element)
        ) {
            throw new HierarchyRequestError();
        }

        // If node is a host-including inclusive ancestor of parent, throw a
        // HierarchyRequestError.
        if ($node->isHostIncludingInclusiveAncestorOf($parent)) {
            throw new HierarchyRequestError();
        }

        // If child is not null and its parent is not parent, then throw a
        // NotFoundError.
        if ($child !== null && $child->parentNode !== $parent) {
            throw new NotFoundError();
        }

        // If node is not a DocumentFragment, DocumentType, Element, Text,
        // ProcessingInstruction, or Comment node, throw a
        // HierarchyRequestError.
        if (!($node instanceof DocumentFragment)
            && !($node instanceof DocumentType)
            && !($node instanceof Element)
            && !($node instanceof Text)
            && !($node instanceof ProcessingInstruction)
            && !($node instanceof Comment)
        ) {
            throw new HierarchyRequestError();
        }

        // If either node is a Text node and parent is a document, or node is a
        // doctype and parent is not a document, throw a HierarchyRequestError.
        if (($node instanceof Text && $parent instanceof Document)
            || ($node instanceof DocumentType && !($parent instanceof Document))
        ) {
            throw new HierarchyRequestError();
        }

        if (!($parent instanceof Document)) {
            return;
        }

        if ($node instanceof DocumentFragment) {
            $elementChildren = 0;

            // Documents cannot contain more than one element child or text
            // nodes. Throw a HierarchyRequestError if the document fragment
            // has more than 1 element child or a text node.
            foreach ($node->childNodes as $childNode) {
                if ($childNode instanceof Element) {
                    $elementChildren++;

                    if ($elementChildren > 1) {
                        throw new HierarchyRequestError();
                    }
                }

                if ($elementChildren > 1 || $childNode instanceof Text) {
                    throw new HierarchyRequestError();
                }
            }

            if ($elementChildren == 0) {
                return;
            }

            // Documents cannot contain more than one element child. Throw a
            // HierarchyRequestError if both the document fragment and
            // document contain an element child.
            foreach ($parent->childNodes as $childNode) {
                if ($child->nodeType === self::ELEMENT_NODE) {
                    throw new HierarchyRequestError();
                }
            }

            // An element cannot preceed a doctype in the tree. Throw a
            // HierarchyRequestError if we try to insert an element before
            // the doctype.
            if ($child instanceof DocumentType) {
                throw new HierarchyRequestError();
            }

            if ($child === null) {
                return;
            }

            // The document element must follow the doctype in the tree.
            // Throw a HierarchyRequestError if we try to insert an element
            // before a node that preceedes the doctype.
            $tw = new TreeWalker($parent, NodeFilter::SHOW_DOCUMENT_TYPE);
            $tw->currentNode = $child;

            if ($tw->nextNode()) {
                throw new HierarchyRequestError();
            }
        } elseif ($node instanceof Element) {
            // A Document cannot contain more than 1 element child. Throw a
            // HierarchyRequestError if the parent already contains an element
            // child.
            foreach ($parent->childNodes as $childNode) {
                if ($childNode instanceof Element) {
                    throw new HierarchyRequestError();
                }
            }

            // The document element must follow the doctype in the tree. Throw
            // a HierarchyRequestError if we try to insert an element before the
            // doctype.
            if ($child instanceof DocumentType) {
                throw new HierarchyRequestError();
            }

            if ($child === null) {
                return;
            }

            // Again, the document element must follow the doctype in the tree.
            // Throw a HierarchyRequestError if we try to insert an element
            // before a node that preceedes the doctype.
            $tw = new TreeWalker($parent, NodeFilter::SHOW_DOCUMENT_TYPE);
            $tw->currentNode = $child;

            if ($tw->nextNode()) {
                throw new HierarchyRequestError();
            }
        } elseif ($node instanceof DocumentType) {
            // A document can only contain 1 doctype definition. Throw a
            // HierarchyRequestError if we try to insert a doctype into a
            // document that already contains a doctype.
            foreach ($parent->childNodes as $childNode) {
                if ($childNode instanceof DocumentType) {
                    throw new HierarchyRequestError();
                }
            }

            // The doctype must preceed any elements. Throw a
            // HierarchyRequestError if we try to insert a doctype before a
            // node that follows an element.
            if ($child !== null) {
                $tw = new TreeWalker($parent, NodeFilter::SHOW_ELEMENT);
                $tw->currentNode = $child;

                if ($tw->previousNode()) {
                    throw new HierarchyRequestError();
                }

                return;
            }

            // The doctype must preceed any elements. Throw a
            // HierarchyRequestError if we try to append a doctype to a parent
            // that already contains an element.
            foreach ($parent->childNodes as $childNode) {
                if ($childNode instanceof Element) {
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
        return $this->parentNode instanceof Element
            ? $this->parentNode
            : null;
    }

    /**
     * Gets the bottom most common ancestor of two nodes, if any.  If null is
     * returned, the two nodes do not have a common ancestor.
     *
     * @internal
     */
    public static function getCommonAncestor(Node $nodeA, Node $nodeB)
    {
        while ($nodeA) {
            $node = $nodeB;

            while ($node) {
                if ($node === $nodeA) {
                    break 2;
                }

                $node = $node->parentNode;
            }

            $nodeA = $nodeA->parentNode;
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
    public function getTreeIndex()
    {
        return $this->parentNode->childNodes->indexOf($this);
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
        return !$this->childNodes->isEmpty();
    }

    /**
     * Inserts a node before another node in a common parent node.
     *
     * @param Node $node The node to be inserted into the document.
     *
     * @param Node $child The node that the new node will be inserted before.
     *
     * @return Node The node that was inserted into the document.
     */
    public function insertBefore(Node $node, Node $child = null)
    {
        return $this->preinsertNode($node, $child);
    }

    /**
     * Inserts a node in to another node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-insert
     *
     * @param Node $node The nodes to be inserted into the document tree.
     *
     * @param Node|null $child Optional. A child node used as a reference to
     *     where the new node should be inserted.
     *
     * @param bool|null $suppressObservers Optional. If true, mutation events
     *     are ignored for this operation.
     */
    public function insertNode(
        Node $node,
        Node $child = null,
        $suppressObservers = null
    ) {
        $parent = $this;
        $nodeIsFragment = $node->nodeType === self::DOCUMENT_FRAGMENT_NODE;
        $count = $nodeIsFragment ? count($node->childNodes) : 1;

        if ($child) {
            $childIndex = $child->parentNode->childNodes->indexOf($child);

            foreach (Range::getRangeCollection() as $range) {
                $startContainer = $range->startContainer;
                $startOffset = $range->startOffset;
                $endContainer = $range->endContainer;
                $endOffset = $range->endOffset;

                if ($startContainer === $parent && $startOffset > $childIndex) {
                    $range->setStart($startContainer, $startOffset + $count);
                }

                if ($endContainer === $parent && $endOffset > $childIndex) {
                    $range->setEnd($endContainer, $endOffset + $count);
                }
            }
        }

        $nodes = $nodeIsFragment ? $node->childNodes->values() : [$node];

        if ($nodeIsFragment) {
            foreach (clone $node->childNodes as $childNode) {
                $node->removeNode($childNode, true);
            }

            // TODO: queue a mutation record of "childList" for node with
            // removedNodes nodes.
        }

        foreach ($nodes as $node) {
            if (!$child) {
                $last = $this->childNodes->last();
            }

            $node->parentNode = $parent;
            $parent->childNodes->insertBefore($child, $node);

            // TODO: For each inclusive descendant inclusiveDescendant of node,
            // in tree order, run the insertion steps with inclusiveDescendant
            // and parent.

            $iter = new NodeIterator($node);

            while (($descendant = $iter->nextNode())) {
                if (method_exists($descendant, 'doInsertingSteps')) {
                    $descendant->doInsertingSteps();
                }
            }

            if ($child) {
                $oldPreviousSibling = $child->previousSibling;
                $nextSibling = $child;
                $child->previousSibling = $node;
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

        if (!$suppressObservers) {
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
     * @param string|null $namespace A namespaceURI to check against.
     *
     * @return bool
     */
    public function isDefaultNamespace($namespace)
    {
        $namespace = Utils::DOMString($namespace, false, true);

        if ($namespace === '') {
            $namespace = null;
        }

        $defaultNamespace = $this->locateNamespace($this, null);

        return $defaultNamespace === $namespace;
    }

    /**
     * Compares two nodes to see if they are equal.
     *
     * @link https://dom.spec.whatwg.org/#dom-node-isequalnode
     * @link https://dom.spec.whatwg.org/#concept-node-equals
     *
     * @param Node $otherNode The node you want to compare the current node to.
     *
     * @return boolean Returns true if the two nodes are the same, otherwise
     *     false.
     */
    public function isEqualNode(Node $otherNode = null)
    {
        if (!$otherNode || $this->nodeType != $otherNode->nodeType) {
            return false;
        }

        if ($this instanceof DocumentType) {
            if ($this->name !== $otherNode->name
                || $this->publicId !== $otherNode->publicId
                || $this->systemId !== $otherNode->systemId
            ) {
                return false;
            }
        } elseif ($this instanceof Element) {
            if ($this->namespaceURI !== $otherNode->namespaceURI
                || $this->prefix !== $otherNode->prefix
                || $this->localName !== $otherNode->localName
                || $this->attributeList->count() !==
                $otherNode->attributes->length
            ) {
                return false;
            }
        } elseif ($this instanceof Attr) {
            if ($this->namespaceURI !== $otherNode->namespaceURI
                || $this->localName !== $otherNode->localName
                || $this->value !== $otherNode->value
            ) {
                return false;
            }
        } elseif ($this instanceof ProcessingInstruction) {
            if ($this->target !== $otherNode->target
                || $this->data !== $otherNode->data
            ) {
                return false;
            }
        } elseif ($this instanceof Text || $this instanceof Comment) {
            if ($this->data !== $otherNode->data) {
                return false;
            }
        }

        if ($this instanceof Element) {
            foreach ($this->attributeList as $i => $attribute) {
                $isEqual = $attribute->isEqualNode(
                    $otherNode->attributeList[$i]
                );

                if (!$isEqual) {
                    return false;
                }
            }
        }

        $childNodeCount = count($this->childNodes);

        if ($childNodeCount !== count($otherNode->childNodes)) {
            return false;
        }

        for ($i = 0; $i < $childNodeCount; $i++) {
            if (!$this->childNodes[$i]->isEqualNode(
                $otherNode->childNodes[$i]
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
     * @param Node|null $otherNode Optional. The node whose equality is to be
     *     checked.
     *
     * @return bool
     */
    public function isSameNode(Node $otherNode = null)
    {
        return $this === $otherNode;
    }

    /**
     * Finds the namespace associated with the given prefix.
     *
     * @link https://dom.spec.whatwg.org/#dom-node-lookupnamespaceuri
     *
     * @param string|null $prefix The prefix of the namespace to be found.
     *
     * @return string|null
     */
    public function lookupNamespaceURI($prefix)
    {
        $prefix = Utils::DOMString($prefix, false, true);

        if ($prefix === '') {
            $prefix = null;
        }

        return $this->locateNamespace($this, $prefix);
    }

    /**
     * Finds the namespace associated with the given prefix on the given node.
     *
     * @see https://dom.spec.whatwg.org/#locate-a-namespace
     *
     * @param \Rowbot\DOM\Node $node
     * @param ?string          $prefix
     *
     * @return ?string
     */
    private function locateNamespace(self $node, ?string $prefix): ?string
    {
        if ($node instanceof Element) {
            if ($node->namespaceURI !== null && $node->prefix === $prefix) {
                return $node->namespaceURI;
            }

            foreach ($node->getAttributeList() as $attr) {
                if ($attr->namespaceURI === Namespaces::XMLNS) {
                    $attrPrefix = $attr->prefix;
                    $localName = $attr->localName;

                    if (($attrPrefix === 'xmlns' && $localName === $prefix)
                        || ($attrPrefix === null && $localName === 'xmlns')
                    ) {
                        if ($attr->value !== '') {
                            return $attr->value;
                        }

                        return null;
                    }
                }
            }

            if ($node->parentElement === null) {
                return null;
            }

            return $this->locateNamespace($node->parentElement, $prefix);
        }

        if ($node instanceof Document) {
            if ($node->documentElement === null) {
                return null;
            }

            return $this->locateNamespace($node->documentElement, $prefix);
        }

        if ($node instanceof DocumentType
            || $node instanceof DocumentFragment
        ) {
            return null;
        }

        if ($node instanceof Attr) {
            if ($node->ownerElement === null) {
                return null;
            }

            return $this->locateNamespace($node->ownerElement, $prefix);
        }

        if ($node->parentElement === null) {
            return null;
        }

        return $this->locateNamespace($node->parentElement, $prefix);
    }

    /**
     * Finds the prefix associated with the given namespace on the given node.
     *
     * @link https://dom.spec.whatwg.org/#dom-node-lookupprefix
     *
     * @param string|null $namespace The namespace of the prefix to be found.
     *
     * @return string|null
     */
    public function lookupPrefix($namespace)
    {
        $namespace = Utils::DOMString($namespace, false, true);

        if ($namespace === null || $namespace === '') {
            return null;
        }

        switch ($this->nodeType) {
            case self::ELEMENT_NODE:
                return $this->locatePrefix($this, $namespace);

            case self::DOCUMENT_NODE:
                return $this->locatePrefix(
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
                    return $this->locatePrefix($ownerElement, $namespace);
                }

                return null;

            default:
                return ($parentElement = $this->parentElement())
                    ? $this->locatePrefix($parentElement, $namespace)
                    : null;
        }
    }

    /**
     * Locates the prefix associated with the given namespace on the given
     * element.
     *
     * @see https://dom.spec.whatwg.org/#locate-a-namespace-prefix
     *
     * @param \Rowbot\DOM\Element\Element $element
     * @param ?string                     $namespace
     *
     * @return ?string
     */
    private function locatePrefix(Element $element, ?string $namespace): ?string
    {
        if ($element->namespaceURI === $namespace
            && $element->prefix !== null
        ) {
            return $element->prefix;
        }

        foreach ($element->getAttributeList() as $attr) {
            if ($attr->prefix === 'xmlns' && $attr->value === $namespace) {
                return $attr->localName;
            }
        }

        if ($element->parentElement !== null) {
            return $this->locatePrefix($element, $namespace);
        }

        return null;
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
                $node->parentNode->removeNode($node);
                continue;
            }

            // Let data be the concatenation of the data of node’s contiguous
            // exclusive Text nodes (excluding itself), in tree order.
            $data = '';
            $contingiousTextNodes = [];
            $startNode = $node->previousSibling;

            while ($startNode) {
                if ($startNode->nodeType != self::TEXT_NODE) {
                    break;
                }

                $data .= $startNode->data;
                $contingiousTextNodes[] = $startNode;
                $startNode = $startNode->previousSibling;
            }

            $startNode = $node->nextSibling;

            while ($startNode) {
                if ($startNode->nodeType != self::TEXT_NODE) {
                    break;
                }

                $data .= $startNode->data;
                $contingiousTextNodes[] = $startNode;
                $startNode = $startNode->nextSibling;
            }

            // Replace data with node node, offset length, count 0, and data
            // data.
            $node->doReplaceData($length, 0, $data);
            $ranges = Range::getRangeCollection();

            foreach (clone $node->childNodes as $currentNode) {
                if ($currentNode->nodeType != self::TEXT_NODE) {
                    break;
                }

                $treeIndex = $currentNode->getTreeIndex();

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
                    if ($range->startContainer === $currentNode->parentNode
                        && $range->startOffset == $treeIndex
                    ) {
                        $range->setStart($node, $length);
                    }
                }

                // For each range whose end node is currentNode’s parent and end
                // offset is currentNode’s index, set its end node to node and
                // its end offset to length.
                foreach ($ranges as $range) {
                    if ($range->endContainer === $currentNode->parentNode
                        && $range->endOffset == $treeIndex
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
                $textNode->parentNode->removeNode($textNode);
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
     * @param Node $node The node being inserted.
     *
     * @param Node|null $child Optional.  A child node used as a reference to
     *     where the new node should be inserted.
     *
     * @return Node The node that was inserted.
     */
    public function preinsertNode(Node $node, Node $child = null)
    {
        $parent = $this;
        $parent->ensurePreinsertionValidity($node, $child);
        $referenceChild = $child;

        if ($referenceChild === $node) {
            $referenceChild = $node->nextSibling;
        }

        // The DOM4 spec states that nodes should be implicitly adopted
        $parent->nodeDocument->doAdoptNode($node);
        $parent->insertNode($node, $referenceChild);

        return $node;
    }

    /**
     * Removes the specified node from the current node.
     *
     * @param Node $child The node to be removed from the DOM.
     *
     * @return Node The node that was removed from the DOM.
     */
    public function removeChild(Node $child)
    {
        return $this->preremoveNode($child);
    }

    /**
     * Removes a node from its parent node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-remove
     *
     * @param Node $node The Node to be removed from the document tree.
     *
     * @param bool $suppressObservers Optional. If true, mutation events are
     *     ignored for this operation.
     */
    public function removeNode(
        Node $node,
        $suppressObservers = null
    ) {
        $parent = $this;
        $index = $parent->childNodes->indexOf($node);
        $ranges = Range::getRangeCollection();

        foreach ($ranges as $range) {
            $startContainer = $range->startContainer;

            if ($startContainer === $node || $node->contains($startContainer)) {
                $range->setStart($parent, $index);
            }
        }

        foreach ($ranges as $range) {
            $endContainer = $range->endContainer;

            if ($endContainer === $node || $node->contains($endContainer)) {
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

        $iterCollection = $node->nodeDocument->getNodeIteratorCollection();

        foreach ($iterCollection as $iter) {
            $iter->preremoveNode($node);
        }

        $oldPreviousSibling = $node->previousSibling;
        $oldNextSibling = $node->nextSibling;
        $parent->childNodes->remove($node);

        // For each inclusive descendant inclusiveDescendant of node, run
        // the removing steps with inclusiveDescendant and parent.
        $iter = new NodeIterator($node);

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

        $node->nextSibling = null;
        $node->previousSibling = null;
        $node->parentNode = null;

        // TODO: For each inclusive ancestor inclusiveAncestor of parent, if
        // inclusiveAncestor has any registered observers whose options' subtree
        // is true, then for each such registered observer registered, append a
        // transient registered observer whose observer and options are
        // identical to those of registered and source which is registered to
        // node’s list of registered observers.

        if (!$suppressObservers) {
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
     * @param Node $node The node to be inserted into the DOM.
     *
     * @param Node $child The node that is being replaced by the new node.
     *
     * @return Node The node that was replaced in the DOM.
     *
     * @throws HierarchyRequestError
     *
     * @throws NotFoundError
     */
    public function replaceChild(Node $node, Node $child)
    {
        return $this->replaceNode($node, $child);
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
     * @param Document $document The Document object that owns this Node.
     */
    public function setNodeDocument(Document $document)
    {
        if ($this->nodeType !== self::DOCUMENT_NODE) {
            $this->nodeDocument = $document;
        }
    }

    /**
     * Replaces all nodes within a parent.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-node-replace-all
     *
     * @param Node|null $node The node that is to be inserted.
     */
    public function replaceAllNodes(Node $node = null)
    {
        if ($node) {
            $this->nodeDocument->doAdoptNode($node);
        }

        $removedNodes = $this->childNodes->values();

        if (!$node) {
            $addedNodes = [];
        } elseif ($node instanceof DocumentFragment) {
            $addedNodes = $node->childNodes->values();
        } else {
            $addedNodes = [$node];
        }

        foreach ($removedNodes as $index => $removableNode) {
            $this->removeNode($removableNode, true);
        }

        if ($node) {
            $this->insertNode($node, null, true);
        }

        // TODO: Queue a mutation record for "childList"
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
     * @param array $options The only valid argument is a key named "composed"
     *     with a boolean value.
     *
     * @return Node If the value of the "composed" key is true, then the
     *     shadow-including root will be returned, otherwise, the root will be
     *     returned.
     */
    public function getRootNode($options = [])
    {
        $root = $this;

        while ($root->parentNode) {
            $root = $root->parentNode;
        }

        if (isset($options['composed']) && $options['composed'] === true &&
            $root instanceof ShadowRoot
        ) {
            $root = $root->host->getRootNode($options);
        }

        return $root;
    }

    /**
     * Returns node's assigned slot, if node is assigned, node's parent
     * otherwise.
     *
     * @see EventTarget::getTheParent
     *
     * @param Event $event An Event object.
     *
     * @return HTMLSlotElement|null
     */
    protected function getTheParent($event)
    {
        // We currently don't support the HTMLSlotElement, so this will always
        // return the node's parent.
        return $this->parentNode;
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
     * @param Node|null $otherNode A Node.
     *
     * @return bool
     */
    public function isAncestorOf($otherNode)
    {
        while ($otherNode) {
            if ($otherNode->parentNode === $this) {
                break;
            }

            $otherNode = $otherNode->parentNode;
        }

        return $otherNode !== null;
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-tree-inclusive-ancestor
     *
     * @param Node|null $otherNode A Node.
     *
     * @return bool
     */
    public function isInclusiveAncestorOf($otherNode)
    {
        return $otherNode === $this || $this->isAncestorOf($otherNode);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-tree-descendant
     *
     * @param Node|null $otherNode A Node.
     *
     * @return bool
     */
    public function isDescendantOf($otherNode)
    {
        return $otherNode->isAncestorOf($this);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-tree-inclusive-descendant
     *
     * @param Node|null $otherNode A Node.
     *
     * @return bool
     */
    public function isInclusiveDescendantOf($otherNode)
    {
        return $otherNode === $this || $this->isDescendantOf($otherNode);
    }

    /**
     * Checks if the node is an inclusive ancestor of the given node, including
     * any nodes that may be hosted.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-tree-host-including-inclusive-ancestor
     *
     * @param Node $node The potential descendant node.
     *
     * @return bool Whether the node is an inclusive ancestor or not.
     */
    protected function isHostIncludingInclusiveAncestorOf(Node $node = null)
    {
        $isInclusiveAncestor = $this->isInclusiveAncestorOf($node);
        $root = null;
        $host = null;

        if (!$isInclusiveAncestor && $node) {
            $root = $node->getRootNode();

            if ($root instanceof DocumentFragment) {
                $host = $root->getHost();
            }
        }

        return $isInclusiveAncestor || ($root && $host
            && $this->isHostIncludingInclusiveAncestorOf($host));
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-shadow-including-descendant
     *
     * @param Node|null $otherNode A Node.
     *
     * @return bool
     */
    public function isShadowIncludingDescendantOf($otherNode)
    {
        $isDescendant = $this->isDescendantOf($otherNode);
        $root = null;

        if (!$isDescendant) {
            $root = $this->getRootNode();
        }

        return $isDescendant || ($root && $root instanceof ShadowRoot
            && $root->host->isShadowIncludingInclusiveDescendantOf($otherNode));
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-shadow-including-inclusive-descendant
     *
     * @param Node|null $otherNode A Node.
     *
     * @return bool
     */
    public function isShadowIncludingInclusiveDescendantOf($otherNode)
    {
        return $this === $otherNode
            || $this->isShadowIncludingDescendantOf($otherNode);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-shadow-including-ancestor
     *
     * @param Node|null $otherNode A Node.
     *
     * @return bool
     */
    public function isShadowIncludingAncestorOf($otherNode)
    {
        return $otherNode->isShadowIncludingDescendantOf($this);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-shadow-including-inclusive-ancestor
     *
     * @param Node|null $otherNode A Node.
     *
     * @return bool
     */
    public function isShadowIncludingInclusiveAncestorOf($otherNode)
    {
        return $this === $otherNode
            || $this->isShadowIncludingAncestorOf($otherNode);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-closed-shadow-hidden
     *
     * @param Node|null $otherNode A Node.
     *
     * @return bool
     */
    public function isClosedShadowHiddenFrom($otherNode)
    {
        $root = $this->getRootNode();

        if ($root instanceof ShadowRoot
            && !$root->isShadowIncludingInclusiveAncestorOf($otherNode)
            && ($root->mode === 'closed'
            || $root->host->isClosedShadowHiddenFrom(
                $otherNode
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
     * @param Node $child The node being removed.
     *
     * @return Node The node that was removed.
     *
     * @throws NotFoundError If the parent of the node being removed does not
     *     match the given parent node.
     */
    protected function preremoveNode(Node $child)
    {
        $parent = $this;

        if ($child->parentNode !== $parent) {
            throw new NotFoundError();
        }

        $parent->removeNode($child);

        return $child;
    }

    /**
     * Replaces a node with another node inside this node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-replace
     *
     * @param Node $node The node being inserted.
     *
     * @param Node $child The node being replaced.
     *
     * @return Node The node that was replaced.
     */
    protected function replaceNode(
        Node $node,
        Node $child
    ) {
        $parent = $this;

        switch ($parent->nodeType) {
            case self::DOCUMENT_NODE:
            case self::DOCUMENT_FRAGMENT_NODE:
            case self::ELEMENT_NODE:
                break;

            default:
                throw new HierarchyRequestError();
        }

        if ($node->isHostIncludingInclusiveAncestorOf($parent)) {
            throw new HierarchyRequestError();
        }

        if ($child->parentNode !== $parent) {
            throw new NotFoundError();
        }

        switch ($node->nodeType) {
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

        if ($node->nodeType === self::TEXT_NODE
            && $parent->nodeType === self::DOCUMENT_NODE
        ) {
            throw new HierarchyRequestError();
        }

        if ($node->nodeType === self::DOCUMENT_TYPE_NODE
            && $parent->nodeType !== self::DOCUMENT_NODE
        ) {
            throw new HierarchyRequestError();
        }

        if ($parent->nodeType === self::DOCUMENT_NODE) {
            switch ($node->nodeType) {
                case self::DOCUMENT_FRAGMENT_NODE:
                    $elementChildren = 0;

                    foreach ($node->childNodes as $childNode) {
                        switch ($childNode->nodeType) {
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
                        foreach ($parent->childNodes as $childNode) {
                            if ($childNode->nodeType === self::ELEMENT_NODE
                                && $childNode !== $child
                            ) {
                                throw new HierarchyRequestError();
                            }
                        }

                        $tw = new TreeWalker(
                            $parent,
                            NodeFilter::SHOW_DOCUMENT_TYPE
                        );
                        $tw->currentNode = $child;

                        if ($tw->nextNode()) {
                            throw new HierarchyRequestError();
                        }
                    }

                    break;

                case self::ELEMENT_NODE:
                    foreach ($parent->childNodes as $childNode) {
                        if ($childNode->nodeType === self::ELEMENT_NODE
                            && $childNode !== $child
                        ) {
                            throw new HierarchyRequestError();
                        }
                    }

                    $tw = new TreeWalker(
                        $parent,
                        NodeFilter::SHOW_DOCUMENT_TYPE
                    );
                    $tw->currentNode = $child;

                    if ($tw->nextNode()) {
                        throw new HierarchyRequestError();
                    }

                    break;

                case self::DOCUMENT_TYPE_NODE:
                    foreach ($parent->childNodes as $childNode) {
                        if ($childNode->nodeType === self::DOCUMENT_TYPE_NODE
                            && $childNode !== $child
                        ) {
                            throw new HierarchyRequestError();
                        }
                    }

                    $tw = new TreeWalker(
                        $parent,
                        NodeFilter::SHOW_ELEMENT
                    );
                    $tw->currentNode = $child;

                    if ($tw->previousNode()) {
                        throw new HierarchyRequestError();
                    }
            }
        }

        $referenceChild = $child->nextSibling;

        if ($referenceChild === $node) {
            $referenceChild = $node->nextSibling;
        }

        $previousSibling = $child->previousSibling;
        $parent->nodeDocument->doAdoptNode($node);
        $removedNodes = [];

        if ($child->parentNode) {
            $removedNodes[] = $child;
            $child->parentNode->removeNode($child, true);
        }

        $nodes = $node->nodeType == self::DOCUMENT_FRAGMENT_NODE
            ? $node->childNodes->values()
            : [$node];
        $parent->insertNode($node, $referenceChild, true);

        // TODO: Queue a mutation record of "childList" for target parent with
        // addedNodes nodes, removedNodes removedNodes, nextSibling reference
        // child, and previousSibling previousSibling.

        return $child;
    }

    /**
     * Sets the node's value.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodevalue
     *
     * @param string $newValue The node's new value.
     */
    abstract protected function setNodeValue($newValue);

    /**
     * Sets the nodes text content.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-textcontent
     *
     * @param string|null $newValue The new text to be inserted into the node.
     */
    abstract protected function setTextContent($newValue);
}
