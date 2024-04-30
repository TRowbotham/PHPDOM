<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Rowbot\DOM\DynamicProperty\Getter;
use Rowbot\DOM\DynamicProperty\MethodGetter;
use Rowbot\DOM\DynamicProperty\MethodSetter;
use Rowbot\DOM\DynamicProperty\PropertyGetter;
use Rowbot\DOM\DynamicProperty\PropertySetter;
use Rowbot\DOM\DynamicProperty\Setter;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\NotFoundError;
use Rowbot\DOM\Exception\NotSupportedError;
use Rowbot\DOM\InternalEvent\NodeChildrenChangedEvent;
use Rowbot\DOM\InternalEvent\NodeClonedEvent;
use Rowbot\DOM\InternalEvent\NodeInsertedEvent;
use Rowbot\DOM\InternalEvent\NodeRemovedEvent;
use Rowbot\DOM\Support\Collection\NodeSet;
use SplDoublyLinkedList;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function assert;
use function count;
use function spl_object_hash;
use function strcmp;

/**
 * @see https://dom.spec.whatwg.org/#node
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Node
 *
 * @property string|null $nodeValue
 * @property string|null $textContent
 *
 * @property-read string                           $baseURI
 * @property-read \Rowbot\DOM\NodeList             $childNodes
 * @property-read \Rowbot\DOM\Node|null            $firstChild
 * @property-read \Rowbot\DOM\Node|null            $lastChild
 * @property-read \Rowbot\DOM\Node|null            $nextSibling
 * @property-read string                           $nodeName
 * @property-read bool                             $isConnected
 * @property-read \Rowbot\DOM\Document|null        $ownerDocument
 * @property-read \Rowbot\DOM\Node|null            $parentNode
 * @property-read \Rowbot\DOM\Element\Element|null $parentElement
 * @property-read \Rowbot\DOM\Node|null            $previousSibling
 */
abstract class Node
{
    public const ELEMENT_NODE                = 1;
    public const ATTRIBUTE_NODE              = 2;
    public const TEXT_NODE                   = 3;
    public const CDATA_SECTION_NODE          = 4;
    public const ENTITY_REFERENCE_NODE       = 5;
    public const ENTITY_NODE                 = 6;
    public const PROCESSING_INSTRUCTION_NODE = 7;
    public const COMMENT_NODE                = 8;
    public const DOCUMENT_NODE               = 9;
    public const DOCUMENT_TYPE_NODE          = 10;
    public const DOCUMENT_FRAGMENT_NODE      = 11;
    public const NOTATION_NODE               = 12;

    public const DOCUMENT_POSITION_DISCONNECTED            = 0x01;
    public const DOCUMENT_POSITION_PRECEDING               = 0x02;
    public const DOCUMENT_POSITION_FOLLOWING               = 0x04;
    public const DOCUMENT_POSITION_CONTAINS                = 0x08;
    public const DOCUMENT_POSITION_CONTAINED_BY            = 0x10;
    public const DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC = 0x20;

    /**
     * @var \Rowbot\DOM\Support\Collection\NodeSet<\Rowbot\DOM\Node>
     */
    protected NodeSet $childNodes_;

    /**
     * @var self::*_NODE
     */
    public readonly int $nodeType;

    /**
     * @var self|null
     */
    #[Getter('parentNode')]
    protected $parentNode;

    /**
     * @var self|null
     */
    #[Getter('nextSibling')]
    protected $nextSibling;

    protected Document $nodeDocument;

    protected EventDispatcherInterface $dispatcher;

    /**
     * @var \Rowbot\DOM\NodeList<\Rowbot\DOM\Node>
     */
    #[Getter('childNodes')]
    protected NodeList $nodeList;

    /**
     * @var self|null
     */
    #[Getter('previousSibling')]
    protected $previousSibling;

    /**
     * @var array<class-string<self>, array<string, \Rowbot\DOM\DynamicProperty\DynamicPropertyGetter>>
     */
    private static array $getters = [];

    /**
     * @var array<class-string<self>, array<string, \Rowbot\DOM\DynamicProperty\DynamicPropertySetter>>
     */
    private static array $setters = [];

    /**
     * @param self::*_NODE $nodeType
     */
    protected function __construct(Document $document, int $nodeType)
    {
        $this->nodeDocument = $document;
        $this->childNodes_ = new NodeSet();
        $this->nodeList = new LiveNodeList($this->childNodes_);
        $this->nodeType = $nodeType;
        $this->dispatcher = new EventDispatcher();
    }

    /**
     * Gets the name of the node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodename
     */
    #[Getter('nodeName')]
    abstract protected function getNodeName(): string;

    /**
     * Returns null if the node is a document, and the node's node document otherwise.
     */
    #[Getter('ownerDocument')]
    public function ownerDocument(): ?Document
    {
        return $this->nodeDocument;
    }

    /**
     * Gets a node's root.
     *
     * @see https://dom.spec.whatwg.org/#concept-tree-root
     *
     * @param array{composed?: bool} $options
     *
     * @return self If the value of the "composed" key is true, then theshadow-including root will be returned,
     *              otherwise, the root will be returned.
     */
    public function getRootNode(array $options = []): self
    {
        $root = $this;

        while ($root->parentNode) {
            $root = $root->parentNode;
        }

        if (
            isset($options['composed'])
            && $options['composed'] === true
            && $root instanceof ShadowRoot
        ) {
            $root = $root->getHost()->getRootNode($options);
        }

        return $root;
    }

    public function getDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    /**
     * Returns the node's parent element.
     *
     * @internal
     */
    #[Getter('parentElement')]
    public function parentElement(): ?Element
    {
        return $this->parentNode instanceof Element ? $this->parentNode : null;
    }

    /**
     * Returns a boolean indicating whether or not the current node contains any nodes.
     */
    public function hasChildNodes(): bool
    {
        return !$this->childNodes_->isEmpty();
    }

    /**
     * Gets the value of the node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodevalue
     */
    #[Getter('nodeValue')]
    abstract protected function getNodeValue(): ?string;

    /**
     * Sets the node's value.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodevalue
     */
    #[Setter('nodeValue')]
    abstract protected function setNodeValue(mixed $value): void;

    /**
     * Gets the concatenation of all descendant text nodes.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-textcontent
     */
    #[Getter('textContent')]
    abstract protected function getTextContent(): ?string;

    /**
     * Sets the nodes text content.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-textcontent
     */
    #[Setter('textContent')]
    abstract protected function setTextContent(?string $value): void;

    /**
     * "Normalizes" the node and its sub-tree so that there are no empty text
     * nodes present and there are no text nodes that appear consecutively.
     *
     * @see https://dom.spec.whatwg.org/#dom-node-normalize
     */
    public function normalize(): void
    {
        $node = $this->nextNode($this);

        while ($node !== null) {
            // We are only interested in actual Text nodes, not derived nodes like CDATASection.
            if ($node->nodeType !== self::TEXT_NODE) {
                $node = $node->nextNode($this);

                continue;
            }

            assert($node instanceof Text && !$node instanceof CDATASection);

            // 1. Let length be node’s length.
            $length = $node->getLength();

            // 2. If length is zero, then remove node and continue with the next exclusive Text node, if any.
            if ($length === 0) {
                $temp = $node->nextNode($this);
                $node->removeNode();
                $node = $temp;

                continue;
            }

            // 3. Let data be the concatenation of the data of node’s contiguous exclusive Text nodes
            // (excluding itself), in tree order.
            $data = '';
            $contingiousTextNodes = new SplDoublyLinkedList();
            $contingiousTextNodes->setIteratorMode(
                SplDoublyLinkedList::IT_MODE_FIFO | SplDoublyLinkedList::IT_MODE_DELETE
            );
            $startNode = $node->previousSibling;

            while ($startNode !== null && $startNode->nodeType === self::TEXT_NODE) {
                assert($startNode instanceof Text && !$startNode instanceof CDATASection);
                $data = $startNode->data . $data;
                $contingiousTextNodes->unshift($startNode);
                $startNode = $startNode->previousSibling;
            }

            $startNode = $node->nextSibling;

            while ($startNode !== null && $startNode->nodeType === self::TEXT_NODE) {
                assert($startNode instanceof Text && !$startNode instanceof CDATASection);
                $data .= $startNode->data;
                $contingiousTextNodes->push($startNode);
                $startNode = $startNode->nextSibling;
            }

            // 4. Replace data with node node, offset length, count 0, and data data.
            $node->doReplaceData($length, 0, $data);

            // 5. Let currentNode be node’s next sibling.
            $currentNode = $node->nextSibling;

            // 6. While currentNode is an exclusive Text node:
            while ($currentNode !== null && $currentNode->nodeType === self::TEXT_NODE) {
                $treeIndex = $currentNode->getTreeIndex();

                foreach (Range::getRangeCollection() as $range) {
                    // 6.1. For each live range whose start node is currentNode, add length to its
                    // start offset and set its start node to node.
                    if ($range->start->node === $currentNode) {
                        $range->start->offset += $length;
                        $range->start->node = $node;

                    // 6.3. For each live range whose start node is currentNode’s parent and start
                    // offset is currentNode’s index, set its start node to node and its start
                    // offset to length.
                    } elseif (
                        $range->start->node === $currentNode->parentNode
                        && $range->start->offset === $treeIndex
                    ) {
                        $range->start->node = $node;
                        $range->start->offset = $length;
                    }

                    // 6.2. For each live range whose end node is currentNode, add length to its end
                    // offset and set its end node to node.
                    if ($range->end->node === $currentNode) {
                        $range->end->offset += $length;
                        $range->end->node = $node;

                    // 6.4. For each live range whose end node is currentNode’s parent and end
                    // offset is currentNode’s index, set its end node to node and its end offset to
                    // length.
                    } elseif (
                        $range->end->node === $currentNode->parentNode
                        && $range->end->offset === $treeIndex
                    ) {
                        $range->end->node = $node;
                        $range->end->offset = $length;
                    }
                }

                // 6.5. Add currentNode’s length to length.
                $length += $currentNode->getLength();

                // 6.6. Set currentNode to its next sibling.
                $currentNode = $currentNode->nextSibling;
            }

            // 7. Remove node’s contiguous exclusive Text nodes (excluding itself), in tree order.
            foreach ($contingiousTextNodes as $textNode) {
                $textNode->removeNode();
            }

            $node = $node->nextNode($this);
        }
    }

    /**
     * Returns a copy of the node upon which the method was called.
     *
     * @see https://dom.spec.whatwg.org/#dom-node-clonenode
     *
     * @param bool $deep (optional) If true, all child nodes and event listeners should be cloned as well.
     *
     * @return static
     *
     * @throws \Rowbot\DOM\Exception\NotSupportedError If the node being cloned is a ShadowRoot.
     */
    public function cloneNode(bool $deep = false): self
    {
        if ($this instanceof ShadowRoot) {
            throw new NotSupportedError();
        }

        return $this->cloneNodeInternal(null, $deep);
    }

    /**
     * Compares two nodes to see if they are equal.
     *
     * @see https://dom.spec.whatwg.org/#dom-node-isequalnode
     * @see https://dom.spec.whatwg.org/#concept-node-equals
     *
     * @param ?self $otherNode The node you want to compare the current node to.
     *
     * @return bool Returns true if the two nodes are the same, otherwise false.
     */
    abstract public function isEqualNode(?self $otherNode): bool;

    protected function hasEqualChildNodes(self $otherNode): bool
    {
        // A and B have the same number of children.
        if (count($this->childNodes_) !== count($otherNode->childNodes_)) {
            return false;
        }

        // Each child of A equals the child of B at the identical index.
        foreach ($this->childNodes_ as $i => $child) {
            if (!$child->isEqualNode($otherNode->childNodes_[$i])) {
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
     */
    public function isSameNode(?self $otherNode): bool
    {
        return $this === $otherNode;
    }

    /**
     * Compares the position of a node against another node.
     *
     * @see https://dom.spec.whatwg.org/#dom-node-comparedocumentpositionother
     *
     * @return int A bitmask representing the nodes position. Possible values are as follows:
     *                 - Node::DOCUMENT_POSITION_DISCONNECTED
     *                 - Node::DOCUMENT_POSITION_PRECEDING
     *                 - Node::DOCUMENT_POSITION_FOLLOWING
     *                 - Node::DOCUMENT_POSITION_CONTAINS
     *                 - Node::DOCUMENT_POSITION_CONTAINED_BY
     *                 - Node::DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC
     */
    public function compareDocumentPosition(self $otherNode): int
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

        // If node1 or node2 is null, or node1’s root is not node2’s root, then
        // return the result of adding DOCUMENT_POSITION_DISCONNECTED,
        // DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC, and either
        // DOCUMENT_POSITION_PRECEDING or DOCUMENT_POSITION_FOLLOWING, with the
        // constraint that this is to be consistent, together.
        if ($node1 === null || $node2 === null || $node1->getRootNode() !== $node2->getRootNode()) {
            $ret = self::DOCUMENT_POSITION_DISCONNECTED
                | self::DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC;
            $position = 0;

            if ($node1 !== null && $node2 !== null) {
                $position = strcmp(spl_object_hash($node2), spl_object_hash($node1));
            }

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
        if (($node1->isAncestorOf($node2) && $attr1 === null) || ($node1 === $node2 && $attr1)) {
            return self::DOCUMENT_POSITION_CONTAINS | self::DOCUMENT_POSITION_PRECEDING;
        }

        // If node1 is a descendant of node2 and attr2 is null, or node1 is
        // node2 and attr1 is non-null, then return the result of adding
        // DOCUMENT_POSITION_CONTAINED_BY to DOCUMENT_POSITION_FOLLOWING.
        if (($node1->isDescendantOf($node2) && $attr2 === null) || ($node1 === $node2 && $attr1)) {
            return self::DOCUMENT_POSITION_CONTAINED_BY | self::DOCUMENT_POSITION_FOLLOWING;
        }

        // If node1 is preceding node2, then return DOCUMENT_POSITION_PRECEDING.
        //
        // NOTE: Due to the way attributes are handled in this algorithm this
        // results in a node’s attributes counting as preceding that node’s
        // children, despite attributes not participating in a tree.
        if ($node1->precedesNode($node2)) {
            return self::DOCUMENT_POSITION_PRECEDING;
        }

        // Return DOCUMENT_POSITION_FOLLOWING.
        return self::DOCUMENT_POSITION_FOLLOWING;
    }

    /**
     * Returns whether or not a node is an inclusive descendant of another node.
     *
     * @param ?self $node A node that you wanted to compare its position of.
     *
     * @return bool Returns true if $node is an inclusive descendant of a node.
     */
    public function contains(?self $node): bool
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
     * Locates the prefix associated with the given namespace on the given node.
     *
     * @see https://dom.spec.whatwg.org/#dom-node-lookupprefix
     */
    public function lookupPrefix(?string $namespace): ?string
    {
        if ($namespace === null || $namespace === '') {
            return null;
        }

        if ($this instanceof Element) {
            return $this->locatePrefix($this, $namespace);
        }

        if ($this instanceof Document) {
            $documentElement = $this->documentElement;

            if ($documentElement !== null) {
                return $this->locatePrefix($documentElement, $namespace);
            }

            return null;
        }

        if ($this instanceof DocumentType || $this instanceof DocumentFragment) {
            return null;
        }

        if ($this instanceof Attr) {
            $ownerElement = $this->ownerElement;

            if ($ownerElement !== null) {
                return $this->locatePrefix($ownerElement, $namespace);
            }

            return null;
        }

        $parentElement = $this->parentElement;

        if ($parentElement !== null) {
            return $this->locatePrefix($parentElement, $namespace);
        }

        return null;
    }

    /**
     * Finds the namespace associated with the given prefix.
     *
     * @see https://dom.spec.whatwg.org/#dom-node-lookupnamespaceuri
     */
    public function lookupNamespaceURI(?string $prefix): ?string
    {
        if ($prefix === '') {
            $prefix = null;
        }

        return $this->locateNamespace($this, $prefix);
    }

    /**
     * Returns whether or not the namespace of the node is the node's default
     * namespace.
     *
     * @see https://dom.spec.whatwg.org/#dom-node-isdefaultnamespace
     */
    public function isDefaultNamespace(?string $namespace): bool
    {
        if ($namespace === '') {
            $namespace = null;
        }

        $defaultNamespace = $this->locateNamespace($this, null);

        return $defaultNamespace === $namespace;
    }

    /**
     * Inserts a node before another node in a common parent node.
     *
     * @see https://dom.spec.whatwg.org/#dom-node-insertbefore
     *
     * @param \Rowbot\DOM\Node      $node  The node to be inserted into the document.
     * @param \Rowbot\DOM\Node|null $child The node that the new node will be inserted before.
     *
     * @return \Rowbot\DOM\Node The node that was inserted into the document.
     */
    public function insertBefore(Node $node, ?Node $child): self
    {
        return $this->preinsertNode($node, $child);
    }

    /**
     * Appends a node to the parent node.  If the node being appended is already
     * associated with another parent node, it will be removed from that parent
     * node before being appended to the current parent node.
     *
     * @param self $node A node representing an element on the page.
     *
     * @return self The node that was just appended to the parent node.
     */
    public function appendChild(self $node): self
    {
        return $this->preinsertNode($node, null);
    }

    /**
     * Replaces a node with another node.
     *
     * @see https://dom.spec.whatwg.org/#dom-node-replacechild
     *
     * @param self $node  The node to be inserted into the DOM.
     * @param self $child The node that is being replaced by the new node.
     *
     * @return self The node that was replaced in the DOM.
     *
     * @throws \Rowbot\DOM\Exception\HierarchyRequestError
     * @throws \Rowbot\DOM\Exception\NotFoundError
     */
    public function replaceChild(self $node, self $child): self
    {
        return $this->replaceNode($node, $child);
    }

    /**
     * Removes the specified node from the current node.
     *
     * @return self The node that was removed from the DOM.
     */
    public function removeChild(self $child): self
    {
        return $this->preremoveNode($child);
    }

    /**
     * Ensures that a node is allowed to be inserted into its parent.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-ensure-pre-insertion-validity
     *
     * @param \Rowbot\DOM\DocumentFragment|\Rowbot\DOM\Node $node  The nodes being inserted into the document tree.
     * @param ?self                                         $child The reference node for where the new nodes should be
     *                                                             inserted.
     *
     * @throws \Rowbot\DOM\Exception\HierarchyRequestError
     * @throws \Rowbot\DOM\Exception\NotFoundError
     */
    public function ensurePreinsertionValidity(self $node, ?self $child): void
    {
        $parent = $this;

        // Only Documents, DocumentFragments, and Elements can be parent nodes.
        // Throw a HierarchyRequestError if parent is not one of these types.
        if (
            !$parent instanceof Document
            && !$parent instanceof DocumentFragment
            && !$parent instanceof Element
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
        if (
            !$node instanceof DocumentFragment
            && !$node instanceof DocumentType
            && !$node instanceof Element
            && !$node instanceof Text
            && !$node instanceof ProcessingInstruction
            && !$node instanceof Comment
        ) {
            throw new HierarchyRequestError();
        }

        // If either node is a Text node and parent is a document, or node is a
        // doctype and parent is not a document, throw a HierarchyRequestError.
        if (
            ($node instanceof Text && $parent instanceof Document)
            || ($node instanceof DocumentType && !$parent instanceof Document)
        ) {
            throw new HierarchyRequestError();
        }

        if (!$parent instanceof Document) {
            return;
        }

        if ($node instanceof DocumentFragment) {
            $elementChildren = 0;

            // Documents cannot contain more than one element child or text
            // nodes. Throw a HierarchyRequestError if the document fragment
            // has more than 1 element child or a text node.
            foreach ($node->childNodes_ as $childNode) {
                if ($childNode instanceof Element) {
                    if (++$elementChildren > 1) {
                        throw new HierarchyRequestError();
                    }

                    continue;
                }

                if ($childNode instanceof Text) {
                    throw new HierarchyRequestError();
                }
            }

            if ($elementChildren === 0) {
                return;
            }

            // Documents cannot contain more than one element child. Throw a
            // HierarchyRequestError if both the document fragment and
            // document contain an element child.
            foreach ($parent->childNodes_ as $childNode) {
                if ($childNode->nodeType === self::ELEMENT_NODE) {
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
            $followingNode = $child->nextNode($parent);

            while ($followingNode) {
                if ($followingNode instanceof DocumentType) {
                    throw new HierarchyRequestError();
                }

                $followingNode = $followingNode->nextNode($parent);
            }
        } elseif ($node instanceof Element) {
            // A Document cannot contain more than 1 element child. Throw a
            // HierarchyRequestError if the parent already contains an element
            // child.
            foreach ($parent->childNodes_ as $childNode) {
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
            $followingNode = $child->nextNode($parent);

            while ($followingNode) {
                if ($followingNode instanceof DocumentType) {
                    throw new HierarchyRequestError();
                }

                $followingNode = $followingNode->nextNode($parent);
            }
        } elseif ($node instanceof DocumentType) {
            // A document can only contain 1 doctype definition. Throw a
            // HierarchyRequestError if we try to insert a doctype into a
            // document that already contains a doctype.
            foreach ($parent->childNodes_ as $childNode) {
                if ($childNode instanceof DocumentType) {
                    throw new HierarchyRequestError();
                }
            }

            // The doctype must preceed any elements. Throw a
            // HierarchyRequestError if we try to insert a doctype before a
            // node that follows an element.
            if ($child !== null) {
                $preceedingNode = $child->previousNode($parent);

                while ($preceedingNode) {
                    if ($preceedingNode instanceof Element) {
                        throw new HierarchyRequestError();
                    }

                    $preceedingNode = $preceedingNode->previousNode($parent);
                }

                return;
            }

            // The doctype must preceed any elements. Throw a
            // HierarchyRequestError if we try to append a doctype to a parent
            // that already contains an element.
            foreach ($parent->childNodes_ as $childNode) {
                if ($childNode instanceof Element) {
                    throw new HierarchyRequestError();
                }
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
     * @param self      $node  The node being inserted.
     * @param self|null $child (optional) A child node used as a reference to where the new node should be inserted.
     *
     * @return self The node that was inserted.
     */
    public function preinsertNode(self $node, self $child = null): self
    {
        $parent = $this;

        // 1. Ensure pre-insertion validity of node into parent before child.
        $parent->ensurePreinsertionValidity($node, $child);

        // 2. Let referenceChild be child.
        $referenceChild = $child;

        // 3. If referenceChild is node, then set referenceChild to node’s next sibling.
        if ($referenceChild === $node) {
            $referenceChild = $node->nextSibling;
        }

        // 4. Insert node into parent before referenceChild.
        $parent->insertNode($node, $referenceChild);

        // 5. Return node.
        return $node;
    }

    /**
     * Inserts a node in to another node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-insert
     *
     * @param \Rowbot\DOM\Node      $node              The nodes to be inserted into the document tree.
     * @param \Rowbot\DOM\Node|null $child             A child node used as a reference to where the new node should be
     *                                                 inserted.
     * @param bool                  $suppressObservers (optional) If true, mutation events are ignored for this
     *                                                 operation.
     */
    public function insertNode(Node $node, ?Node $child, bool $suppressObservers = false): void
    {
        $nodeIsFragment = $node instanceof DocumentFragment;

        // 1. Let nodes be node’s children, if node is a DocumentFragment node; otherwise « node ».
        $nodes = $nodeIsFragment ? $node->childNodes_->all() : [$node];

        // 2. Let count be nodes’s size.
        $count = count($nodes);

        // 3. If count is 0, then return.
        if ($count === 0) {
            return;
        }

        // 4. If node is a DocumentFragment node, then:
        if ($nodeIsFragment) {
            // 4.1. Remove its children with the suppress observers flag set.
            foreach ($nodes as $childNode) {
                $childNode->removeNode(true);
            }
        }

        // 5. If child is non-null, then:
        if ($child) {
            $index = $child->getTreeIndex();

            foreach (Range::getRangeCollection() as $range) {
                // 5.1. For each live range whose start node is parent and start offset is greater
                // than child’s index, increase its start offset by count.
                if ($range->start->node === $this && $range->start->offset > $index) {
                    $range->start->offset += $count;
                }

                // 5.2. For each live range whose end node is parent and end offset is greater than
                // child’s index, increase its end offset by count.
                if ($range->end->node === $this && $range->end->offset > $index) {
                    $range->end->offset += $count;
                }
            }
        }

        // 6. Let previousSibling be child’s previous sibling or parent’s last child if child is null.
        $previousSibling = $child ? $child->previousSibling : $this->lastChild;

        // 7. For each node in nodes, in tree order:
        // Overwriting $node is intentional
        foreach ($nodes as $node) {
            // 7.1. Adopt node into parent’s node document.
            $this->nodeDocument->doAdoptNode($node);

            // 7.2. If child is null, then append node to parent’s children.
            if (!$child) {
                $oldPreviousSibling = $this->childNodes_->last();
                $this->childNodes_->append($node);
                $nextSibling = null;

            // 7.3. Otherwise, insert node into parent’s children before child’s index.
            } else {
                $this->childNodes_->insertBefore($child, $node);
                $oldPreviousSibling = $child->previousSibling;
                $nextSibling = $child;
                $child->previousSibling = $node;
            }

            $node->parentNode = $this;

            if ($oldPreviousSibling) {
                $oldPreviousSibling->nextSibling = $node;
            }

            $node->previousSibling = $oldPreviousSibling;
            $node->nextSibling = $nextSibling;

            $inclusiveDescendant = $node;

            // 7.7 For each shadow-including inclusive descendant inclusiveDescendant of node, in
            // shadow-including tree order:
            do {
                // 7.7.1 Run the insertion steps with inclusiveDescendant.
                $event = new NodeInsertedEvent($inclusiveDescendant);
                $inclusiveDescendant->dispatcher->dispatch($event, 'node.inserted');
                $inclusiveDescendant = $inclusiveDescendant->nextNode($node);
            } while ($inclusiveDescendant);
        }

        // 9. Run the children changed steps for parent.
        $this->dispatcher->dispatch(new NodeChildrenChangedEvent(), 'node.children.changed');
    }

    /**
     * Replaces a node with another node inside this node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-replace
     *
     * @param self $node  The node being inserted.
     * @param self $child The node being replaced.
     *
     * @return self The node that was replaced.
     */
    protected function replaceNode(self $node, self $child): self
    {
        $parent = $this;

        // 1. If parent is not a Document, DocumentFragment, or Element node, then throw a
        // "HierarchyRequestError" DOMException.
        if (
            !$parent instanceof Document
            && !$parent instanceof DocumentFragment
            && !$parent instanceof Element
        ) {
            throw new HierarchyRequestError();
        }

        // 2. If node is a host-including inclusive ancestor of parent, then throw a
        // "HierarchyRequestError" DOMException.
        if ($node->isHostIncludingInclusiveAncestorOf($parent)) {
            throw new HierarchyRequestError();
        }

        // 3. If child’s parent is not parent, then throw a "NotFoundError" DOMException.
        if ($child->parentNode !== $parent) {
            throw new NotFoundError();
        }

        //4. If node is not a DocumentFragment, DocumentType, Element, Text, ProcessingInstruction,
        // or Comment node, then throw a "HierarchyRequestError" DOMException.
        if (
            !$node instanceof DocumentFragment
            && !$node instanceof DocumentType
            && !$node instanceof Element
            && !$node instanceof Text
            && !$node instanceof ProcessingInstruction
            && !$node instanceof Comment
        ) {
            throw new HierarchyRequestError();
        }

        // 5. If either node is a Text node and parent is a document, or node is a doctype and
        // parent is not a document, then throw a "HierarchyRequestError" DOMException.
        if ($node instanceof Text && $parent instanceof Document) {
            throw new HierarchyRequestError();
        }

        if ($node instanceof DocumentType && !$parent instanceof Document) {
            throw new HierarchyRequestError();
        }

        // 6. If parent is a document, and any of the statements below, switched on node, are true,
        // then throw a "HierarchyRequestError" DOMException.
        if ($parent instanceof Document) {
            if ($node instanceof DocumentFragment) {
                $elementChildren = 0;

                // If node has more than one element child or has a Text node child.
                foreach ($node->childNodes_ as $childNode) {
                    if ($childNode instanceof Element) {
                        ++$elementChildren;
                    }

                    if ($elementChildren > 1 || $childNode instanceof Text) {
                        throw new HierarchyRequestError();
                    }
                }

                // Otherwise, if node has one element child and either parent has an element child
                // that is not child or a doctype is following child.
                if ($elementChildren === 1) {
                    foreach ($parent->childNodes_ as $childNode) {
                        if ($childNode instanceof Element && $childNode !== $child) {
                            throw new HierarchyRequestError();
                        }
                    }

                    $followingNode = $child->nextNode($parent);

                    while ($followingNode) {
                        if ($followingNode instanceof DocumentType) {
                            throw new HierarchyRequestError();
                        }

                        $followingNode = $followingNode->nextNode($parent);
                    }
                }
            } elseif ($node instanceof Element) {
                // parent has an element child that is not child or a doctype is following child.
                foreach ($parent->childNodes_ as $childNode) {
                    if ($childNode instanceof Element && $childNode !== $child) {
                        throw new HierarchyRequestError();
                    }
                }

                $followingNode = $child->nextNode($parent);

                while ($followingNode) {
                    if ($followingNode instanceof DocumentType) {
                        throw new HierarchyRequestError();
                    }

                    $followingNode = $followingNode->nextNode($parent);
                }
            } elseif ($node instanceof DocumentType) {
                // parent has a doctype child that is not child, or an element is preceding child.
                foreach ($parent->childNodes_ as $childNode) {
                    if ($childNode instanceof DocumentType && $childNode !== $child) {
                        throw new HierarchyRequestError();
                    }
                }

                $preceedingNode = $child->previousNode($parent);

                while ($preceedingNode) {
                    if ($preceedingNode instanceof Element) {
                        throw new HierarchyRequestError();
                    }

                    $preceedingNode = $preceedingNode->previousNode($parent);
                }
            }
        }

        // 7. Let referenceChild be child’s next sibling.
        $referenceChild = $child->nextSibling;

        // 8. If referenceChild is node, then set referenceChild to node’s next sibling.
        if ($referenceChild === $node) {
            $referenceChild = $node->nextSibling;
        }

        // 9. Let previousSibling be child’s previous sibling.
        $previousSibling = $child->previousSibling;

        // 10. Let removedNodes be the empty set.
        $removedNodes = [];

        // 11. If child’s parent is non-null, then:
        if ($child->parentNode) {
            // 11.1. Set removedNodes to « child ».
            $removedNodes = [$child];

            // 11.2. Remove child with the suppress observers flag set.
            $child->removeNode(true);
        }

        // 12. Let nodes be node’s children if node is a DocumentFragment node; otherwise « node ».
        $nodes = $node instanceof DocumentFragment ? $node->childNodes_->all() : [$node];

        // 13. Insert node into parent before referenceChild with the suppress observers flag set.
        $parent->insertNode($node, $referenceChild, true);

        // 15. Return child
        return $child;
    }

    /**
     * Replaces all nodes within a parent.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-replace-all
     *
     * @param ?self $node The node that is to be inserted.
     */
    public function replaceAllNodes(?self $node): void
    {
        // 1. Let removedNodes be parent’s children.
        $removedNodes = $this->childNodes_->all();

        // 2. Let addedNodes be the empty set.
        $addedNodes = [];

        // 3. If node is a DocumentFragment node, then set addedNodes to node’s children.
        if ($node instanceof DocumentFragment) {
            $addedNodes = $node->childNodes_->all();

        // 4. Otherwise, if node is non-null, set addedNodes to « node ».
        } elseif ($node) {
            $addedNodes = [$node];
        }

        // 5. Remove all parent’s children, in tree order, with the suppress observers flag set.
        foreach ($removedNodes as $removableNode) {
            $removableNode->removeNode(true);
        }

        // 6. If node is non-null, then insert node into parent before null with the suppress
        // observers flag set.
        if ($node) {
            $this->insertNode($node, null, true);
        }
    }

    /**
     * Removes a node from another node after making sure that they share
     * the same parent node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-pre-remove
     *
     * @param self $child The node being removed.
     *
     * @return self The node that was removed.
     *
     * @throws \Rowbot\DOM\Exception\NotFoundError If the parent of the node being removed does not match the given
     *                                             parent node.
     */
    protected function preremoveNode(self $child): self
    {
        $parent = $this;

        // 1. If child’s parent is not parent, then throw a "NotFoundError" DOMException.
        if ($child->parentNode !== $parent) {
            throw new NotFoundError();
        }

        // 2. Remove child.
        $child->removeNode();

        // 3. Return child.
        return $child;
    }

    /**
     * Removes a node from its parent node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-remove
     *
     * @param bool $suppressObservers (optional) If true, mutation events are ignored for this operation.
     */
    public function removeNode(bool $suppressObservers = false): void
    {
        // Let parent be node’s parent
        $parent = $this->parentNode;

        // 2. Assert: parent is non-null.
        assert($parent !== null);

        // 3. Let index be node’s index.
        $index = $parent->childNodes_->indexOf($this);

        foreach (Range::getRangeCollection() as $range) {
            // 4. For each live range whose start node is an inclusive descendant of node, set its
            // start to (parent, index).
            if ($range->start->node === $this || $this->contains($range->start->node)) {
                $range->start->node = $parent;
                $range->start->offset = $index;
            }

            // 5. For each live range whose end node is an inclusive descendant of node, set its end
            // to (parent, index).
            if ($range->end->node === $this || $this->contains($range->end->node)) {
                $range->end->node = $parent;
                $range->end->offset = $index;
            }

            // 6. For each live range whose start node is parent and start offset is greater than
            // index, decrease its start offset by 1.
            if ($range->start->node === $parent && $range->start->offset > $index) {
                $range->start->offset -= 1;
            }

            // 7. For each live range whose end node is parent and end offset is greater than index,
            // decrease its end offset by 1.
            if ($range->end->node === $parent && $range->end->offset > $index) {
                $range->end->offset -= 1;
            }
        }

        // 8. For each NodeIterator object iterator whose root’s node document is node’s node
        // document, run the NodeIterator pre-removing steps given node and iterator.
        foreach (NodeIteratorContext::getIterators() as $iter) {
            if ($iter->root->nodeDocument === $this->nodeDocument) {
                $iter->adjustIteratorPosition($this);
            }
        }

        // 9. Let oldPreviousSibling be node’s previous sibling.
        $oldPreviousSibling = $this->previousSibling;

        // 10. Let oldNextSibling be node’s next sibling.
        $oldNextSibling = $this->nextSibling;

        // 11. Remove node from its parent’s children.
        $parent->childNodes_->remove($this);

        if ($oldPreviousSibling) {
            $oldPreviousSibling->nextSibling = $oldNextSibling;
        }

        if ($oldNextSibling) {
            $oldNextSibling->previousSibling = $oldPreviousSibling;
        }

        $this->nextSibling = null;
        $this->previousSibling = null;
        $this->parentNode = null;

        // 15. Run the removing steps with node and parent.
        $this->dispatcher->dispatch(new NodeRemovedEvent($this, $parent), 'node.removed');

        $descendant = $this;

        // 18. For each shadow-including descendant descendant of node, in shadow-including tree
        // order, then:
        do {
            // 19. Run the removing steps with descendant.
            $descendant->dispatcher->dispatch(new NodeRemovedEvent($descendant, null), 'node.removed');

            $descendant = $descendant->nextNode($this);
        } while ($descendant);

        // 21. Run the children changed steps for parent.
        $parent->dispatcher->dispatch(new NodeChildrenChangedEvent(), 'node.children.changed');
    }

    /**
     * Gets the node's node document.
     *
     * @internal
     */
    public function getNodeDocument(): Document
    {
        return $this->nodeDocument;
    }

    /**
     * Sets the node's node document.
     *
     * @internal
     */
    public function setNodeDocument(Document $document): void
    {
        if ($this->nodeType !== self::DOCUMENT_NODE) {
            $this->nodeDocument = $document;
        }
    }

    /**
     * Returns the Node's length.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-length
     */
    abstract public function getLength(): int;

    /**
     * Returns the Node's index.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-tree-index
     */
    public function getTreeIndex(): int
    {
        if ($this->parentNode === null) {
            return 0;
        }

        return $this->parentNode->childNodes_->indexOf($this);
    }

    /**
     * Returns the next Node in tree-order, if any. If a root is given, then it will only find nodes
     * within the given root.
     *
     * @internal
     */
    public function nextNode(self $root = null): ?self
    {
        $node = $this->childNodes_->first();

        if ($node !== null) {
            return $node;
        }

        $node = $this;

        while ($node && !$node->nextSibling) {
            if ($node === $root) {
                return null;
            }

            $node = $node->parentNode;
        }

        if ($node === null || $node === $root) {
            return null;
        }

        return $node->nextSibling;
    }

    /**
     * Returns the last Node that's before node in tree order, or null if node is
     * the first Node. If a root is given, then it will only find nodes within the given root.
     *
     * @internal
     */
    public function previousNode(self $root = null): ?self
    {
        $node = $this;

        if ($node->previousSibling) {
            $node = $node->previousSibling;

            while ($node !== null && $node->childNodes_->first()) {
                if ($node === $root) {
                    return null;
                }

                $node = $node->lastChild;
            }

            return $node;
        }

        if ($node === $root) {
            return null;
        }

        return $node->parentNode;
    }

    /**
     * @internal
     */
    public function precedesNode(self $node): bool
    {
        $nodeA = $this;
        $nodeB = $node;
        $commonAncestor = self::getCommonAncestor($nodeA, $nodeB);

        if ($commonAncestor === null) {
            return false;
        }

        if ($nodeA === $commonAncestor) {
            return true;
        }

        if ($nodeB === $commonAncestor) {
            return false;
        }

        while ($nodeA->parentNode !== $commonAncestor) {
            /** @var \Rowbot\DOM\Node $nodeA */
            $nodeA = $nodeA->parentNode;
        }

        while ($nodeB->parentNode !== $commonAncestor) {
            /** @var \Rowbot\DOM\Node $nodeB */
            $nodeB = $nodeB->parentNode;
        }

        return $nodeA->getTreeIndex() < $nodeB->getTreeIndex();
    }

    /**
     * @internal
     */
    public function followsNode(self $node): bool
    {
        $nodeA = $this;
        $nodeB = $node;
        $commonAncestor = self::getCommonAncestor($nodeA, $nodeB);

        if ($commonAncestor === null) {
            return false;
        }

        if ($nodeA === $commonAncestor) {
            return false;
        }

        if ($nodeB === $commonAncestor) {
            return true;
        }

        while ($nodeA->parentNode !== $commonAncestor) {
            /** @var \Rowbot\DOM\Node $nodeA */
            $nodeA = $nodeA->parentNode;
        }

        while ($nodeB->parentNode !== $commonAncestor) {
            /** @var \Rowbot\DOM\Node $nodeB */
            $nodeB = $nodeB->parentNode;
        }

        return $nodeA->getTreeIndex() > $nodeB->getTreeIndex();
    }

    /**
     * Locates the prefix associated with the given namespace on the given
     * element.
     *
     * @see https://dom.spec.whatwg.org/#locate-a-namespace-prefix
     */
    private function locatePrefix(Element $element, ?string $namespace): ?string
    {
        if ($element->namespaceURI === $namespace && $element->prefix !== null) {
            return $element->prefix;
        }

        foreach ($element->getAttributeList() as $attr) {
            if ($attr->prefix === 'xmlns' && $attr->getValue() === $namespace) {
                return $attr->getLocalName();
            }
        }

        if ($element->parentElement !== null) {
            return $this->locatePrefix($element->parentElement, $namespace);
        }

        return null;
    }

    /**
     * Finds the namespace associated with the given prefix on the given node.
     *
     * @see https://dom.spec.whatwg.org/#locate-a-namespace
     */
    private function locateNamespace(self $node, ?string $prefix): ?string
    {
        if ($node instanceof Element) {
            if ($node->namespaceURI !== null && $node->prefix === $prefix) {
                return $node->namespaceURI;
            }

            foreach ($node->getAttributeList() as $attr) {
                if ($attr->namespaceURI === Namespaces::XMLNS) {
                    $localName = $attr->localName;

                    if (
                        ($attr->prefix === 'xmlns' && $localName === $prefix)
                        || ($prefix === null && $localName === 'xmlns')
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

        if ($node instanceof DocumentType || $node instanceof DocumentFragment) {
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
     * Clones the given node and performs any node specific cloning steps
     * if the interface defines them.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-clone
     *
     * @param \Rowbot\DOM\Document|null $document      (optional) The document that will own the cloned node.
     * @param bool                      $cloneChildren (optional) If set, all children of the cloned node will also be
     *                                                 cloned.
     *
     * @return static The newly created node.
     */
    public function cloneNodeInternal(Document $document = null, bool $cloneChildren = false)
    {
        $document = $document ?? $this->nodeDocument;
        $copy = clone $this;

        if ($copy instanceof Document) {
            $copy->nodeDocument = $copy;
            $document = $copy;
        } else {
            $copy->nodeDocument = $document;
        }

        $this->dispatcher->dispatch(new NodeClonedEvent($copy, $this, $document, $cloneChildren), 'node.cloned');

        if ($cloneChildren) {
            foreach ($this->childNodes_ as $child) {
                $copyChild = $child->cloneNodeInternal($document, true);
                $copy->appendChild($copyChild);
            }
        }

        return $copy;
    }

    /**
     * Gets the bottom most common ancestor of two nodes, if any. If null is returned, the two nodes do not have a
     * common ancestor.
     *
     * @internal
     */
    public static function getCommonAncestor(Node $nodeA, Node $nodeB): ?self
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
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-tree-ancestor
     */
    public function isAncestorOf(?self $otherNode): bool
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
     */
    public function isInclusiveAncestorOf(?self $otherNode): bool
    {
        return $otherNode === $this || $this->isAncestorOf($otherNode);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-tree-descendant
     */
    public function isDescendantOf(?self $otherNode): bool
    {
        return $otherNode !== null && $otherNode->isAncestorOf($this);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-tree-inclusive-descendant
     */
    public function isInclusiveDescendantOf(?self $otherNode): bool
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
     */
    protected function isHostIncludingInclusiveAncestorOf(?self $node): bool
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

        return $isInclusiveAncestor
            || ($root && $host && $this->isHostIncludingInclusiveAncestorOf($host));
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-shadow-including-descendant
     */
    public function isShadowIncludingDescendantOf(?self $otherNode): bool
    {
        $isDescendant = $this->isDescendantOf($otherNode);
        $root = null;

        if (!$isDescendant) {
            $root = $this->getRootNode();
        }

        return $isDescendant
            || ($root
                && $root instanceof ShadowRoot
                && $root->host->isShadowIncludingInclusiveDescendantOf($otherNode)
            );
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-shadow-including-inclusive-descendant
     */
    public function isShadowIncludingInclusiveDescendantOf(?self $otherNode): bool
    {
        return $this === $otherNode || $this->isShadowIncludingDescendantOf($otherNode);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-shadow-including-ancestor
     */
    public function isShadowIncludingAncestorOf(?self $otherNode): bool
    {
        return $otherNode !== null && $otherNode->isShadowIncludingDescendantOf($this);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-shadow-including-inclusive-ancestor
     */
    public function isShadowIncludingInclusiveAncestorOf(?self $otherNode): bool
    {
        return $this === $otherNode || $this->isShadowIncludingAncestorOf($otherNode);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-closed-shadow-hidden
     */
    public function isClosedShadowHiddenFrom(?self $otherNode): bool
    {
        $root = $this->getRootNode();

        return $root instanceof ShadowRoot
            && !$root->isShadowIncludingInclusiveAncestorOf($otherNode)
            && ($root->mode === 'closed' || $root->host->isClosedShadowHiddenFrom($otherNode));
    }

    #[Getter('baseURI')]
    private function getBaseURI(): string
    {
        return $this->nodeDocument->getBaseURL()->serializeURL();
    }

    #[Getter('firstChild')]
    private function getFirstChild(): ?self
    {
        return $this->childNodes_->first();
    }

    #[Getter('isConnected')]
    private function isConnected(): bool
    {
        return $this->getRootNode(['composed' => true]) instanceof Document;
    }

    #[Getter('lastChild')]
    private function getLastChild(): ?self
    {
        return $this->childNodes_->last();
    }

    private function registerDynamicPropertyGetters(ReflectionClass $reflection): void
    {
        $filter = ReflectionMethod::IS_PRIVATE | ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PUBLIC;

        foreach ($reflection->getMethods($filter) as $method) {
            foreach ($method->getAttributes(Getter::class) as $attribute) {
                $instance = $attribute->newInstance();
                self::$getters[static::class][$instance->name] = new MethodGetter($method->getName());
            }
        }

        $filter = ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC;

        foreach ($reflection->getProperties($filter) as $property) {
            foreach ($property->getAttributes(Getter::class) as $attribute) {
                $instance = $attribute->newInstance();
                self::$getters[static::class][$instance->name] = new PropertyGetter($property->getName());
            }
        }
    }

    private function registerDynamicPropertySetters(ReflectionClass $reflection): void
    {
        $filter = ReflectionMethod::IS_PRIVATE | ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PUBLIC;

        foreach ($reflection->getMethods($filter) as $method) {
            foreach ($method->getAttributes(Setter::class) as $attribute) {
                $instance = $attribute->newInstance();
                self::$setters[static::class][$instance->name] = new MethodSetter($method->getName());
            }
        }

        $filter = ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC;

        foreach ($reflection->getProperties($filter) as $property) {
            foreach ($property->getAttributes(Setter::class) as $attribute) {
                $instance = $attribute->newInstance();
                self::$setters[static::class][$instance->name] = new PropertySetter($property->getName());
            }
        }
    }

    public function __get(string $name)
    {
        if (!isset(self::$getters[static::class])) {
            self::$getters[static::class] = [];
            $reflection = new ReflectionClass($this);

            do {
                $this->registerDynamicPropertyGetters($reflection);
            } while (($reflection = $reflection->getParentClass()) !== false);
        }

        if (!isset(self::$getters[static::class][$name])) {
            return null;
        }

        return self::$getters[static::class][$name]->getValue($this);
    }

    public function __set(string $name, mixed $value): void
    {
        if (!isset(self::$setters[static::class])) {
            self::$setters[static::class] = [];
            $reflection = new ReflectionClass($this);

            do {
                $this->registerDynamicPropertySetters($reflection);
            } while (($reflection = $reflection->getParentClass()) !== false);
        }

        if (!isset(self::$setters[static::class][$name])) {
            return;
        }

        self::$setters[static::class][$name]->setValue($this, $value);
    }

    protected function __clone()
    {
        $this->parentNode = null;
        $this->nextSibling = null;
        $this->previousSibling = null;
        $this->childNodes_ = new NodeSet();
        $this->nodeList = new LiveNodeList($this->childNodes_);
        $this->dispatcher = new EventDispatcher();
    }
}
