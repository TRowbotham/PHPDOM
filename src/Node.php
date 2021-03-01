<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Event\Event;
use Rowbot\DOM\Event\EventTarget;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\NotFoundError;
use Rowbot\DOM\Exception\NotSupportedError;
use Rowbot\DOM\Support\Collection\NodeSet;

use function assert;
use function count;
use function method_exists;
use function range;
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
 * @property-read int                              $nodeType
 * @property-read bool                             $isConnected
 * @property-read \Rowbot\DOM\Document|null        $ownerDocument
 * @property-read \Rowbot\DOM\Node|null            $parentNode
 * @property-read \Rowbot\DOM\Element\Element|null $parentElement
 * @property-read \Rowbot\DOM\Node|null            $previousSibling
 */
abstract class Node extends EventTarget
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
    protected $childNodes;

    /**
     * @var int
     */
    protected $nodeType;

    /**
     * @var self|null
     */
    protected $parentNode;

    /**
     * @var self|null
     */
    protected $nextSibling;

    /**
     * @var \Rowbot\DOM\Document
     */
    protected $nodeDocument;

    /**
     * @var \Rowbot\DOM\NodeList
     */
    protected $nodeList;

    /**
     * @var self|null
     */
    protected $previousSibling;

    protected function __construct(Document $document)
    {
        parent::__construct();

        $this->nodeDocument = $document;
        $this->childNodes = new NodeSet();
        $this->nodeList = new NodeList($this->childNodes);
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
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
                return $this->ownerDocument();

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

    /**
     * @param mixed $value
     */
    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'nodeValue':
                $this->setNodeValue($value === null ? $value : (string) $value);

                break;

            case 'textContent':
                $this->setTextContent($value === null ? $value : (string) $value);
        }
    }

    /**
     * Gets the name of the node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodename
     */
    abstract protected function getNodeName(): string;

    /**
     * Returns null if the node is a document, and the node's node document otherwise.
     */
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

    /**
     * Returns the node's parent element.
     *
     * @internal
     */
    public function parentElement(): ?Element
    {
        return $this->parentNode instanceof Element ? $this->parentNode : null;
    }

    /**
     * Returns a boolean indicating whether or not the current node contains any nodes.
     */
    public function hasChildNodes(): bool
    {
        return !$this->childNodes->isEmpty();
    }

    /**
     * Gets the value of the node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodevalue
     */
    abstract protected function getNodeValue(): ?string;

    /**
     * Sets the node's value.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodevalue
     */
    abstract protected function setNodeValue(?string $value): void;

    /**
     * Gets the concatenation of all descendant text nodes.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-textcontent
     */
    abstract protected function getTextContent(): ?string;

    /**
     * Sets the nodes text content.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-textcontent
     */
    abstract protected function setTextContent(?string $value): void;

    /**
     * "Normalizes" the node and its sub-tree so that there are no empty text
     * nodes present and there are no text nodes that appear consecutively.
     *
     * @see https://dom.spec.whatwg.org/#dom-node-normalize
     */
    public function normalize(): void
    {
        $iter = $this->nodeDocument->createNodeIterator($this, NodeFilter::SHOW_TEXT);

        while ($node = $iter->nextNode()) {
            $length = $node->getLength();

            // If length is zero, then remove node and continue with the next
            // exclusive Text node, if any.
            if ($length === 0) {
                $node->removeNode();

                continue;
            }

            // Let data be the concatenation of the data of node’s contiguous
            // exclusive Text nodes (excluding itself), in tree order.
            $data = '';
            $contingiousTextNodes = [];
            $startNode = $node->previousSibling;

            while ($startNode) {
                if ($startNode->nodeType !== self::TEXT_NODE) {
                    break;
                }

                $data .= $startNode->data;
                $contingiousTextNodes[] = $startNode;
                $startNode = $startNode->previousSibling;
            }

            $startNode = $node->nextSibling;

            while ($startNode) {
                if ($startNode->nodeType !== self::TEXT_NODE) {
                    break;
                }

                $data .= $startNode->data;
                $contingiousTextNodes[] = $startNode;
                $startNode = $startNode->nextSibling;
            }

            // Replace data with node node, offset length, count 0, and data
            // data.
            $node->doReplaceData($length, 0, $data);

            foreach (clone $node->childNodes as $currentNode) {
                if ($currentNode->nodeType !== self::TEXT_NODE) {
                    break;
                }

                $treeIndex = $currentNode->getTreeIndex();

                foreach (Range::getRangeCollection() as $range) {
                    // 6.1. For each live range whose start node is currentNode, add length to its
                    // start offset and set its start node to node.
                    if ($range->startNode === $currentNode) {
                        $range->startOffset += $length;
                        $range->startNode = $node;

                    // 6.3. For each live range whose start node is currentNode’s parent and start
                    // offset is currentNode’s index, set its start node to node and its start
                    // offset to length.
                    } elseif (
                        $range->startNode === $currentNode->parentNode
                        && $range->startOffset === $treeIndex
                    ) {
                        $range->startNode = $node;
                        $range->startOffset = $length;
                    }

                    // 6.2. For each live range whose end node is currentNode, add length to its end
                    // offset and set its end node to node.
                    if ($range->endNode === $currentNode) {
                        $range->endOffset += $length;
                        $range->endNode = $node;

                    // 6.4. For each live range whose end node is currentNode’s parent and end
                    // offset is currentNode’s index, set its end node to node and its end offset to
                    // length.
                    } elseif (
                        $range->endNode === $currentNode->parentNode
                        && $range->endOffset === $treeIndex
                    ) {
                        $range->endNode = $node;
                        $range->endOffset = $length;
                    }
                }

                // Add currentNode’s length to length.
                $length += $currentNode->getLength();
            }

            // Remove node’s contiguous exclusive Text nodes (excluding itself),
            // in tree order.
            foreach ($contingiousTextNodes as $textNode) {
                $textNode->removeNode();
            }
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
    public function isEqualNode(?self $otherNode): bool
    {
        if (!$otherNode || $this->nodeType !== $otherNode->nodeType) {
            return false;
        }

        if ($this instanceof DocumentType) {
            if (
                $this->name !== $otherNode->name
                || $this->publicId !== $otherNode->publicId
                || $this->systemId !== $otherNode->systemId
            ) {
                return false;
            }
        } elseif ($this instanceof Element) {
            if (
                $this->namespaceURI !== $otherNode->namespaceURI
                || $this->prefix !== $otherNode->prefix
                || $this->localName !== $otherNode->localName
                || $this->attributeList->count() !==
                $otherNode->attributes->length
            ) {
                return false;
            }
        } elseif ($this instanceof Attr) {
            if (
                $this->namespaceURI !== $otherNode->getNamespace()
                || $this->localName !== $otherNode->getLocalName()
                || $this->value !== $otherNode->getValue()
            ) {
                return false;
            }
        } elseif ($this instanceof ProcessingInstruction) {
            if ($this->target !== $otherNode->target || $this->data !== $otherNode->data) {
                return false;
            }
        } elseif ($this instanceof Text || $this instanceof Comment) {
            if ($this->data !== $otherNode->data) {
                return false;
            }
        }

        if ($this instanceof Element) {
            $attributeCount = $this->attributeList->count();

            if (count($otherNode->attributeList) < $attributeCount) {
                return false;
            }

            $indexes = range(0, $attributeCount - 1);

            foreach ($this->attributeList as $attribute) {
                $match = false;

                foreach ($indexes as $i) {
                    if ($attribute->isEqualNode($otherNode->attributeList[$i])) {
                        $match = true;
                        unset($indexes[$i]);

                        break;
                    }
                }

                if (!$match) {
                    return false;
                }
            }
        }

        $childNodeCount = count($this->childNodes);

        if ($childNodeCount !== count($otherNode->childNodes)) {
            return false;
        }

        for ($i = 0; $i < $childNodeCount; $i++) {
            if (!$this->childNodes[$i]->isEqualNode($otherNode->childNodes[$i])) {
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
        $preceedingNode = $node2->previousNode();

        while ($preceedingNode) {
            if ($preceedingNode === $node1) {
                return self::DOCUMENT_POSITION_PRECEDING;
            }

            $preceedingNode = $preceedingNode->previousNode();
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
            foreach ($node->childNodes as $childNode) {
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
            foreach ($parent->childNodes as $childNode) {
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
            foreach ($parent->childNodes as $childNode) {
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
            foreach ($parent->childNodes as $childNode) {
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
        $nodes = $nodeIsFragment ? $node->childNodes->all() : [$node];

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
                if ($range->startNode === $this && $range->startOffset > $index) {
                    $range->startOffset += $count;
                }

                // 5.2. For each live range whose end node is parent and end offset is greater than
                // child’s index, increase its end offset by count.
                if ($range->endNode === $this && $range->endOffset > $index) {
                    $range->endOffset += $count;
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
                $oldPreviousSibling = $this->childNodes->last();
                $this->childNodes->append($node);
                $nextSibling = null;

            // 7.3. Otherwise, insert node into parent’s children before child’s index.
            } else {
                $this->childNodes->insertBefore($child, $node);
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
                if (method_exists($inclusiveDescendant, 'doInsertingSteps')) {
                    $inclusiveDescendant->doInsertingSteps($inclusiveDescendant);
                }

                $inclusiveDescendant = $inclusiveDescendant->nextNode($node);
            } while ($inclusiveDescendant);
        }

        // TODO: 9. Run the children changed steps for parent.
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
                foreach ($node->childNodes as $childNode) {
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
                    foreach ($parent->childNodes as $childNode) {
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
                foreach ($parent->childNodes as $childNode) {
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
                foreach ($parent->childNodes as $childNode) {
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
        $nodes = $node instanceof DocumentFragment ? $node->childNodes->all() : [$node];

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
        $removedNodes = $this->childNodes->all();

        // 2. Let addedNodes be the empty set.
        $addedNodes = [];

        // 3. If node is a DocumentFragment node, then set addedNodes to node’s children.
        if ($node instanceof DocumentFragment) {
            $addedNodes = $node->childNodes->all();

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
        $index = $parent->childNodes->indexOf($this);

        foreach (Range::getRangeCollection() as $range) {
            // 4. For each live range whose start node is an inclusive descendant of node, set its
            // start to (parent, index).
            if ($range->startNode === $this || $this->contains($range->startNode)) {
                $range->startNode = $parent;
                $range->startOffset = $index;
            }

            // 5. For each live range whose end node is an inclusive descendant of node, set its end
            // to (parent, index).
            if ($range->endNode === $this || $this->contains($range->endNode)) {
                $range->endNode = $parent;
                $range->endOffset = $index;
            }

            // 6. For each live range whose start node is parent and start offset is greater than
            // index, decrease its start offset by 1.
            if ($range->startNode === $parent && $range->startOffset > $index) {
                $range->startOffset -= 1;
            }

            // 7. For each live range whose end node is parent and end offset is greater than index,
            // decrease its end offset by 1.
            if ($range->endNode === $parent && $range->endOffset > $index) {
                $range->endOffset -= 1;
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
        $parent->childNodes->remove($this);

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
        if (method_exists($this, 'doRemovingSteps')) {
            $this->doRemovingSteps($parent);
        }

        $descendant = $this;

        // 18. For each shadow-including descendant descendant of node, in shadow-including tree
        // order, then:
        do {
            // 19. Run the removing steps with descendant.
            if (method_exists($descendant, 'doRemovingSteps')) {
                $descendant->doRemovingSteps();
            }

            $descendant = $descendant->nextNode($this);
        } while ($descendant);

        // TODO: 21. Run the children changed steps for parent.
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

        return $this->parentNode->childNodes->indexOf($this);
    }

    /**
     * Returns the next Node in tree-order, if any. If a root is given, then it will only find nodes
     * within the given root.
     *
     * @internal
     */
    public function nextNode(self $root = null): ?self
    {
        $node = $this->childNodes->first();

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

            while ($node !== null && $node->childNodes->first()) {
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
    abstract public function cloneNodeInternal(
        Document $document = null,
        bool $cloneChildren = false
    ): self;

    /**
     * Performs steps after cloning a node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-clone
     */
    protected function postCloneNode(
        Node $copy,
        Document $document,
        bool $cloneChildren
    ): void {
        if ($copy instanceof Document) {
            $copy->setNodeDocument($copy);
            $document = $copy;
        } else {
            $copy->setNodeDocument($document);
        }

        if (method_exists($this, 'onCloneNode')) {
            $this->onCloneNode($copy, $document, $cloneChildren);
        }

        if ($cloneChildren) {
            foreach ($this->childNodes as $child) {
                $copyChild = $child->cloneNodeInternal($document, true);
                $copy->appendChild($copyChild);
            }
        }
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
     * Returns node's assigned slot, if node is assigned, node's parent otherwise.
     *
     * @see \Rowbot\DOM\Event\EventTarget::getTheParent
     *
     * @param \Rowbot\DOM\Event\Event $event An Event object.
     *
     * @return ((\Rowbot\DOM\Element\HTML\HTMLSlotElement|self)&\Rowbot\DOM\Event\EventTarget)|null
     */
    protected function getTheParent(Event $event): ?EventTarget
    {
        // We currently don't support the HTMLSlotElement, so this will always
        // return the node's parent.
        return $this->parentNode;
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
        return $otherNode->isAncestorOf($this);
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
        return $otherNode->isShadowIncludingDescendantOf($this);
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
}
