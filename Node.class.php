<?php
namespace phpjs;

use phpjs\elements\Element;
use phpjs\events\Event;
use phpjs\events\EventTarget;
use phpjs\exceptions\HierarchyRequestError;
use phpjs\exceptions\InvalidStateError;
use phpjs\exceptions\NotFoundError;
use phpjs\urls\URLInternal;

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
abstract class Node implements EventTarget
{
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

    protected static $mBaseURI;
    protected $mChildNodes; // NodeList
    protected $mFirstChild; // Node
    protected $mLastChild; // Node
    protected $mNextSibling; // Node
    protected $mNodeName; // String
    protected $mNodeType; // int
    protected $mNodeValue; // String
    protected $mOwnerDocument; // Document
    protected $mParentNode; // Node
    protected $mParentElement; // Element
    protected $mPreviousSibling; // Node
    protected $mTextContent; // String

    private $mEvents;

    protected function __construct()
    {
        if (!self::$mBaseURI) {
            self::$mBaseURI = URLInternal::basicURLParser($this->getBaseURI());
        }

        $this->mChildNodes = array();
        $this->mEvents = array();
        $this->mFirstChild = null;
        $this->mLastChild = null;
        $this->mNextSibling = null;
        $this->mNodeName = '';
        $this->mNodeType = '';
        $this->mNodeValue = null;
        $this->mOwnerDocument = Document::_getDefaultDocument();
        $this->mParentElement = null;
        $this->mParentNode = null;
        $this->mPreviousSibling = null;
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'baseURI':
                return $this->getBaseURI();

            case 'childNodes':
                return $this->mChildNodes;

            case 'firstChild':
                return $this->mFirstChild;

            case 'lastChild':
                return $this->mLastChild;

            case 'nextSibling':
                return $this->mNextSibling;

            case 'nodeName':
                return $this->mNodeName;

            case 'nodeType':
                return $this->mNodeType;

            case 'nodeValue':
                return $this->mNodeValue;

            case 'ownerDocument':
                return $this->mOwnerDocument;

            case 'parentElement':
                return $this->mParentElement;

            case 'parentNode':
                return $this->mParentNode;

            case 'previousSibling':
                return $this->mPreviousSibling;

            case 'rootNode':
                return self::getRootNode($this);

            case 'textContent':
                switch ($this->mNodeType) {
                    case self::DOCUMENT_FRAGMENT_NODE:
                    case self::ELEMENT_NODE:
                        $tw = new TreeWalker($this, NodeFilter::SHOW_TEXT);
                        $textContent = '';

                        while ($node = $tw->nextNode()) {
                            $textContent .= $node->data;
                        }

                        return $textContent;

                    case self::TEXT_NODE:
                    case self::PROCESSING_INSTRUCTION_NODE:
                    case self::COMMENT_NODE:
                        return $this->mData;

                    default:
                        return null;
                }
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'textContent':
                $value = $aValue === null ? '' : $aValue;

                switch ($this->mNodeType) {
                    case self::DOCUMENT_FRAGMENT_NODE:
                    case self::ELEMENT_NODE:
                        $node = null;

                        if ($value !== '') {
                            $node = new Text($value);
                        }

                        $this->_replaceAll($node);

                        break;

                    case self::TEXT_NODE:
                    case self::PROCESSING_INSTRUCTION_NODE:
                    case self::COMMENT_NODE:
                        $this->replaceData(0, $this->length, $value);

                        break;
                }
        }
    }

    /**
     * Registers a callback for a specified event on the current node.
     *
     * @param string $aEventName The name of the event to listen for.
     *
     * @param callable|EventListener $aCallback A callback that will be executed
     *     when the event occurs.  If an object that inherits from the
     *     EventListener interface is given, it will use the handleEvent method
     *     on the object as the callback.
     *
     * @param boolean $aUseCapture Optional. Specifies whether or not the event
     *     should be handled during the capturing or bubbling phase.
     */
    public function addEventListener(
        $aEventName,
        $aCallback,
        $aUseCapture = false
    ) {
        if (!$aCallback) {
            return;
        }

        if (is_object($aCallback) && $aCallback instanceof EventListener) {
            $callback = array($aCallback, 'handleEvent');
        } else {
            $callback = $aCallback;
        }

        $listener = array(
            'type' => $aEventName,
            'callback' => $aCallback,
            'capture' => $aUseCapture
        );

        if (!in_array($listener, $this->mEvents)) {
            array_unshift($this->mEvents, $listener);
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
        return self::preinsertNode($aNode, $this, null);
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
        $reference = $this;

        if ($reference === $aOtherNode) {
            return 0;
        }

        $referenceRoot = self::getRootNode($reference);

        if ($referenceRoot !== self::getRootNode($aOtherNode)) {
            $ret = self::DOCUMENT_POSITION_DISCONNECTED |
                self::DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC;
            $position = strcmp(
                spl_object_hash($this),
                spl_object_hash($aOtherNode)
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
                $ret |= self::DOCUMENT_POSITION_PRECEDING;
            } else {
                $ret |= self::DOCUMENT_POSITION_FOLLOWING;
            }

            return $ret;
        }

        if ($aOtherNode->contains($reference)) {
            return self::DOCUMENT_POSITION_CONTAINS |
                self::DOCUMENT_POSITION_PRECEDING;
        }

        if ($reference->contains($aOtherNode)) {
            return self::DOCUMENT_POSITION_CONTAINED_BY |
                self::DOCUMENT_POSITION_FOLLOWING;
        }

        $tw = new TreeWalker(
            $referenceRoot,
            NodeFilter::SHOW_ALL,
            function ($aNode) use ($aOtherNode) {
                return $aNode === $aOtherNode ? NodeFilter::FILTER_ACCEPT :
                    NodeFilter::FILTER_SKIP;
            }
        );
        $tw->currentNode = $reference;

        if ($tw->previousNode()) {
            return self::DOCUMENT_POSITION_PRECEDING;
        }

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
     * Dispatches an event at the current EventTarget, which will then invoke
     * any event listeners on the node and its ancestors.
     *
     * @param Event $aEvent An object representing the specific event dispatched
     *     with information regarding that event.
     *
     * @return boolean Returns true if the event is not cancelable or if the
     *     preventDefault() method is not invoked, otherwise it returns false.
     */
    public function dispatchEvent(Event $aEvent)
    {
        $flags = $aEvent->_getFlags();
        $eventState = $flags & Event::EVENT_DISPATCHED ||
            $flags & Event::EVENT_INITIALIZED;

        if ($eventState) {
            throw new InvalidStateError();
        }

        $aEvent->_setIsTrusted(false);
        $aEvent->_setFlag(Event::EVENT_DISPATCHED);
        $aEvent->_setTarget($this);
        $eventPath = array();
        $node = $this->mParentNode;

        while ($node) {
            $eventPath[] = $node;
            $node = $node->mParentNode;
        }

        $aEvent->_setEventPhase(Event::CAPTURING_PHASE);

        foreach ($eventPath as $eventTarget) {
            if ($aEvent->_getFlags() & Event::EVENT_STOP_PROPAGATION) {
                break;
            }

            $this->invokeEventListener($aEvent, $eventTarget);
        }

        $aEvent->_setEventPhase(Event::AT_TARGET);

        if (!($aEvent->_getFlags() & Event::EVENT_STOP_PROPAGATION)) {
            $this->invokeEventListener($aEvent, $aEvent->target);
        }

        if ($aEvent->bubbles) {
            $aEvent->_setEventPhase(Event::BUBBLING_PHASE);

            foreach (array_reverse($eventPath) as $eventTarget) {
                if ($aEvent->_getFlags() & Event::EVENT_STOP_PROPAGATION) {
                    break;
                }

                $this->invokeEventListener($aEvent, $eventTarget);
            }
        }

        $aEvent->_unsetFlag(Event::EVENT_DISPATCHED);
        $aEvent->_setEventPhase(Event::NONE);
        $aEvent->_setCurrentTarget(null);

        return !$aEvent->cancelable ||
            !($aEvent->_getFlags() & Event::EVENT_CANCELED);
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
        $document = $aDocument ?: $node->mOwnerDocument;

        switch ($node->mNodeType) {
            case self::ELEMENT_NODE:
                $copy = static::create(
                    $node->mLocalName,
                    $node->mNamespaceURI,
                    $node->mPrefix
                );

                foreach ($node->mAttributesList as $attr) {
                    $copyAttr = new Attr(
                        $attr->localName,
                        $attr->value,
                        $attr->namespaceURI,
                        $attr->prefix
                    );
                    $copy->mAttributesList->appendAttr($copyAttr, $copy);
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
            $copy->mOwnerDocument = $copy;
            $document = $copy;
        } else {
            $copy->mOwnerDocument = $document;
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
     * Returns a boolean indicating whether or not the current node contains any
     * nodes.
     *
     * @return boolean Returns true if at least one child node is present,
     *     otherwise false.
     */
    public function hasChildNodes()
    {
        return !empty($this->mChildNodes);
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
        return self::preinsertNode($aNewNode, $this, $aRefNode);
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
        $namespace = $aNamespace === '' ? null : $aNamespace;
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
            for ($i = 0; $i < count($this->mAttributesList); $i++) {
                if (
                    strcmp(
                        $this->mAttributesList[$i]->namespaceURI,
                        $aOtherNode->attributes[$i]->namespaceURI
                    ) !== 0 ||
                    strcmp(
                        $this->mAttributesList[$i]->prefix,
                        $aOtherNode->attributes[$i]->prefix
                    ) !== 0 ||
                    strcmp(
                        $this->mAttributesList[$i]->localName,
                        $aOtherNode->attributes[$i]->localName
                    ) !== 0
                ) {
                    return false;
                }
            }
        }

        if (count($this->mChildNodes) !== count($aOtherNode->childNodes)) {
            return false;
        }

        for ($i = 0; $i < count($this->mChildNodes); $i++) {
            if (!$this->mChildNodes[$i]->isEqualNode(
                    $aOtherNode->childNodes[$i]
                )) {
                return false;
            }
        }

        return true;
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
     * @param Node $aParent The node that will play host to the node being
     *     inserted.
     *
     * @param Node|null $aChild Optional. A child node used as a reference to
     *     where the new node should be inserted.
     *
     * @param bool|null $aSuppressObservers Optional. If true, mutation events
     *     are ignored for this operation.
     */
    public static function insertNode(
        Node $aNode,
        Node $aParent,
        Node $aChild = null,
        $aSuppressObservers = null
    ) {
        $nodeIsFragment = $aNode->mNodeType === self::DOCUMENT_FRAGMENT_NODE;
        $count = $nodeIsFragment ? count($aNode->mChildNodes) : 1;

        if ($aChild) {
            $childIndex = $aChild->_getTreeIndex();

            foreach (Range::_getRangeCollection() as $range) {
                $startContainer = $range->startContainer;
                $startOffset = $range->startOffset;
                $endContainer = $range->endContainer;
                $endOffset = $range->endOffset;

                if (
                    $startContainer === $aParent &&
                    $startOffset > $childIndex
                ) {
                    $range->setStart($startContainer, $startOffset + $count);
                }

                if (
                    $endContainer === $aParent &&
                    $endOffset > $childIndex
                ) {
                    $range->setEnd($endContainer, $endOffset + $count);
                }
            }
        }

        $nodes = $nodeIsFragment ? $aNode->mChildNodes : [$aNode];

        if ($nodeIsFragment) {
            foreach ($aNode->mChildNodes as $child) {
                self::removeNode($child, $aNode, true);
            }

            // TODO: queue a mutation record of "childList" for node with
            // removedNodes nodes.
        }

        $index = $aChild ?
            array_search($aChild, $aParent->mChildNodes, true) :
            count($aParent->mChildNodes);
        $parentElement = $aParent->mNodeType === self::ELEMENT_NODE ?
            $aParent : null;

        if ($index === 0) {
            $aParent->mFirstChild = $nodes[0];
        }

        foreach ($nodes as $node) {
            $node->mParentNode = $aParent;
            $node->mParentElement = $parentElement;

            if ($aChild) {
                $node->mPreviousSibling = $aChild->mPreviousSibling;
                $node->mNextSibling = $aChild;

                if ($aChild->mPreviousSibling) {
                    $aChild->mPreviousSibling->mNextSibling = $node;
                }

                $aChild->mPreviousSibling = $node;
            } else {
                $node->mPreviousSibling = $aParent->mLastChild;

                if ($aParent->mLastChild) {
                    $aParent->mLastChild->mNextSibling = $node;
                }

                $aParent->mLastChild = $node;
            }

            array_splice($aParent->mChildNodes, $index++, 0, [$node]);

            // TODO: For each inclusive descendant inclusiveDescendant of node,
            // in tree order, run the insertion steps with inclusiveDescendant
            // and parent.
        }

        if (!$aSuppressObservers) {
            // TODO: If suppress observers flag is unset, queue a mutation
            // record of "childList" for parent with addedNodes nodes,
            // nextSibling child, and previousSibling child’s previous
            // sibling or parent’s last child if child is null.
        }
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
        $prefix = $aPrefix === '' ? null : $aPrefix;

        return Namespaces::locateNamespace($this, $aPrefix);
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
        if ($aNamespace === null || $aNamespace === '') {
            return null;
        }

        switch ($this->mNodeType) {
            case self::ELEMENT_NODE:
                return Namespaces::locatePrefix($this, $aNamespace);

            case self::DOCUMENT_NODE:
                return Namespaces::locatePrefix(
                    $this->mDoumentElement,
                    $aNamespace
                );

            case self::DOCUMENT_TYPE_NODE:
            case self::DOCUMENT_FRAGMENT_NODE:
                return null;

            default:
                return $this->mParentElement ?
                    Namespaces::locatePrefix(
                        $this->mParentElement,
                        $aNamespace
                    ) : null;
        }
    }

    /**
     * "Normalizes" the node and its sub-tree so that there are no empty text
     * nodes present and there are no text nodes that appear consecutively.
     *
     * @link https://dom.spec.whatwg.org/#dom-node-normalize
     */
    public function normalize()
    {
        $ownerDocument = $this->mOwnerDocument ?: $this;
        $nextSibling = $this->mNextSibling;
        $tw = $ownerDocument->createTreeWalker($this, NodeFilter::SHOW_TEXT);

        while ($node = $tw->nextNode()) {
            $length = $node->length;

            if (!$length) {
                self::removeNode($node, $this);
                continue;
            }

            $data = '';
            $contingiousTextNodes = array();
            $startNode = $node->mPreviousSibling;

            while ($startNode) {
                if (!($startNode instanceof Text)) {
                    break;
                }

                $data .= $startNode->data;
                $contingiousTextNodes[] = $startNode;
                $startNode = $startNode->mPreviousSibling;
            }

            $startNode = $node->mNextSibling;

            while ($startNode) {
                if (!($startNode instanceof Text)) {
                    break;
                }

                $data .= $startNode->data;
                $contingiousTextNodes[] = $startNode;
                $startNode = $startNode->mNextSibling;
            }

            $node->replaceData($length, 0, $data);
            $currentNode = $node->mNextSibling;

            while ($currentNode instanceof Text) {
                $ranges = Range::_getRangeCollection();
                $treeIndex = $currentNode->_getTreeIndex();

                foreach ($ranges as $range) {
                    if ($range->startContainer === $currentNode) {
                        $range->setStart($node, $range->startOffset + $length);
                    }
                }

                foreach ($ranges as $range) {
                    if ($range->endContainer === $currentNode) {
                        $range->setStart($node, $range->endOffset + $length);
                    }
                }

                foreach ($ranges as $range) {
                    if (
                        $range->startContainer === $currentNode->mParentNode &&
                        $range->startOffset == $treeIndex
                    ) {
                        $range->setStart($node, $length);
                    }
                }

                foreach ($ranges as $range) {
                    if (
                        $range->endContainer === $currentNode->mParentNode &&
                        $range->endOffset == $treeIndex
                    ) {
                        $range->setEnd($node, $length);
                    }
                }

                $length += $currentNode->length;
                $currentNode = $currentNode->mPextSibling;
            }

            foreach ($contingiousTextNodes as $textNode) {
                self::removeNode($textNode, $textNode->mParentNode);
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
     * @param Node $aParent The node that will play host to the inserted node.
     *
     * @param Node|null $aChild Optional.  A child node used as a reference to
     *     where the new node should be inserted.
     *
     * @return Node The node that was inserted.
     */
    public static function preinsertNode(
        Node $aNode,
        Node $aParent,
        Node $aChild = null
    ) {
        $aParent->_ensurePreinsertionValidity($aNode, $aChild);
        $referenceChild = $aChild;

        if ($referenceChild === $aNode) {
            $referenceChild = $aNode->mNextSibling;
        }

        // The DOM4 spec states that nodes should be implicitly adopted
        $ownerDocument = $aParent->mOwnerDocument ?: $aParent;
        Document::adopt($aNode, $ownerDocument);
        self::insertNode($aNode, $aParent, $referenceChild);

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
        return self::preremoveNode($aNode, $this);
    }

    /**
     * Unregisters a callback for a specified event on the current node.
     *
     * @param string $aEventName The name of the event to listen for.
     *
     * @param callable|EventListener $aCallback A callback that will be executed
     *     when the event occurs.  If an object that inherits from the
     *     EventListener interface is given, it will use the handleEvent method
     *     on the object as the callback.
     *
     * @param boolean $aUseCapture Optional. Specifies whether or not the event
     *     should be handled during the capturing or bubbling phase.
     */
    public function removeEventListener(
        $aEventName,
        $aCallback,
        $aUseCapture = false
    ) {
        if (is_object($aCallback) && $aCallback instanceof EventListener) {
            $callback = array($aCallback, 'handleEvent');
        } else {
            $callback = $aCallback;
        }

        $listener = array(
            'type' => $aEventName,
            'callback' => $callback,
            'capture' => $aUseCapture
        );
        $index = array_search($listener, $this->mEvents);

        if ($index !== false) {
            array_splice($this->mEvents, $index, 1);
        }
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
    public static function removeNode(
        Node $aNode,
        Node $aParent,
        $aSuppressObservers = null
    ) {
        $index = array_search($aNode, $aParent->mChildNodes);
        $ranges = Range::_getRangeCollection();

        foreach ($ranges as $range) {
            $startContainer = $range->startContainer;

            if (
                $startContainer === $aNode ||
                $aNode->contains($startContainer)
            ) {
                $range->setStart($aParent, $index);
            }
        }

        foreach ($ranges as $range) {
            $endContainer = $range->endContainer;

            if ($endContainer === $aNode || $aNode->contains($endContainer)) {
                $range->setEnd($aParent, $index);
            }
        }

        foreach ($ranges as $range) {
            $startContainer = $range->startContainer;
            $startOffset = $range->startOffset;

            if ($startContainer === $aParent && $startOffset > $index) {
                $range->setStart($startContainer, $startOffset - 1);
            }
        }

        foreach ($ranges as $range) {
            $endContainer = $range->endContainer;
            $endOffset = $range->endOffset;

            if ($endContainer === $aParent && $endOffset > $index) {
                $range->setEnd($endContainer, $endOffset - 1);
            }
        }

        $iterCollection = $aNode->mOwnerDocument->_getNodeIteratorCollection();

        foreach ($iterCollection as $iter) {
            $iter->_preremove($aNode);
        }

        $oldPreviousSibling = $aNode->mPreviousSibling;
        $oldNextSibling = $aNode->mNextSibling;

        array_splice($aParent->mChildNodes, $index, 1);

        // TODO: For each inclusive descendant inclusiveDescendant of node, run
        // the removing steps with inclusiveDescendant and parent.

        if ($aParent->mFirstChild === $aNode) {
            $aParent->mFirstChild = $aNode->mNextSibling;
        }

        if ($aParent->mLastChild === $aNode) {
            $aParent->mLastChild = $aNode->mPreviousSibling;
        }

        if ($aNode->mPreviousSibling) {
            $aNode->mPreviousSibling->mNextSibling = $aNode->mNextSibling;
        }

        if ($aNode->mNextSibling) {
            $aNode->mNextSibling->mPreviousSibling = $aNode->mPreviousSibling;
        }

        $aNode->mPreviousSibling = null;
        $aNode->mNextSibling = null;
        $aNode->mParentElement = null;
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
     * @link https://dom.spec.whatwg.org/#concept-node-replace
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
        if (
            !($this instanceof Document) &&
            !($this instanceof DocumentFragment) &&
            !($this instanceof Element)
        ) {
            throw new HierarchyRequestError();
        }

        if ($this === $aNewNode || $aNewNode->contains($this)) {
            throw new HierarchyRequestError();
        }

        if ($aOldNode->parentNode !== $this) {
            throw new NotFoundError();
        }

        if (
            !($aNewNode instanceof DocumentFragment) &&
            !($aNewNode instanceof DocumentType) &&
            !($aNewNode instanceof Element) &&
            !($aNewNode instanceof Text) &&
            !($aNewNode instanceof ProcessingInstruction) &&
            !($aNewNode instanceof Comment)
        ) {
            throw new HierarchyRequestError();
        }

        if (
            ($aNewNode instanceof Text && $this instanceof Document) ||
            ($aNewNode instanceof DocumentType && !($this instanceof Document))
        ) {
            throw new HierarchyRequestError();
        }

        if ($this instanceof Document) {
            if ($aNewNode instanceof DocumentFragment) {
                $hasTextNode = array_filter(
                    $this->mChildNodes,
                    function ($node) {
                        return $node instanceof Text;
                    }
                );

                if ($aNewNode->childElementCount > 1 || $hasTextNode) {
                    throw new HierarchyRequestError();
                } else {
                    $node = $aOldNode->nextSibling;
                    $docTypeFollowsChild = false;

                    while ($node) {
                        if ($node instanceof DocumentType) {
                            $docTypeFollowsChild = true;
                            break;
                        }

                        $node = $node->nextSibling;
                    }

                    if (
                        $this->childElementCount == 1 &&
                        ($this->children[0] !== $aOldNode ||
                            $docTypeFollowsChild)
                    ) {
                        throw new HierarchyRequestError();
                    }
                }
            } elseif ($aNewNode instanceof Element) {
                $node = $aOldNode->nextSibling;
                $docTypeFollowsChild = false;

                while ($node) {
                    if ($node instanceof DocumentType) {
                        $docTypeFollowsChild = true;
                        break;
                    }

                    $node = $node->nextSibling;
                }

                if ($this->childElementCount > 1 || $docTypeFollowsChild) {
                    throw new HierarchyRequestError();
                }
            } elseif ($aNewNode instanceof DocumentType) {
                $hasDocTypeNotChild = false;

                foreach ($this->mChildNodes as $node) {
                    if ($node instanceof DocumentType &&
                        $node !== $aOldNode) {
                        $hasDocTypeNotChild = true;
                    }
                }

                $node = $aOldNode->previousSibling;
                $elementPrecedesChild = false;

                while ($node) {
                    if ($node instanceof Element) {
                        $elementPrecedesChild = true;
                        break;
                    }

                    $node = $node->previousSibling;
                }

                if ($hasDocTypeNotChild || $elementPrecedesChild) {
                    throw new HierarchyRequestError();
                }
            }
        }

        $referenceChild = $aOldNode->nextSibling;

        if ($referenceChild === $aNewNode) {
            $referenceChild = $aNewNode->nextSibling;
        }

        // The DOM4 spec states that nodes should be implicitly adopted
        $ownerDocument = $this->mOwnerDocument ?: $this;
        Document::adopt($aNewNode, $ownerDocument);

        self::removeNode($aOldNode, $this, true);

        $nodes = $aNewNode instanceof DocumentFragment ?
            $aNewNode->childNodes : array($aNewNode);
        self::insertNode($aNewNode, $this, $referenceChild, true);

        // TODO: Queue a mutation record for "childList"

        return $aOldNode;
    }

    /**
     * Sets the node's owner document.
     *
     * @internal
     *
     * @param Document $aNode The Document object that owns this Node.
     */
    public function setOwnerDocument(Document $aDocument)
    {
        if ($this->mNodeType !== self::DOCUMENT_NODE) {
            $this->mOwnerDocument = $aDocument;
        }
    }

    /**
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
    public function _ensurePreinsertionValidity($aNode, $aChild)
    {
        if (
            !($this instanceof Document) &&
            !($this instanceof DocumentFragment) &&
            !($this instanceof Element)
        ) {
            throw new HierarchyRequestError();
        }

        if ($this === $aNode || $aNode->contains($this)) {
            throw new HierarchyRequestError;
        }

        if ($aChild !== null && $this !== $aChild->mParentNode) {
            throw new NotFoundError;
        }

        if (!($aNode instanceof DocumentFragment) &&
            !($aNode instanceof DocumentType) &&
            !($aNode instanceof Element) &&
            !($aNode instanceof Text) &&
            !($aNode instanceof ProcessingInstruction) &&
            !($aNode instanceof Comment)
        ) {
            throw new HierarchyRequestError();
        }

        if (
            ($aNode instanceof Text && $this instanceof Document) ||
            ($aNode instanceof DocumentType && !($this instanceof Document))
        ) {
            throw new HierarchyRequestError();
        }

        if ($this instanceof Document) {
            if ($aNode instanceof DocumentFragment) {
                $hasTextNode = false;

                foreach ($aNode->mChildNodes as $node) {
                    if ($node instanceof Text) {
                        $hasTextNode = true;

                        break;
                    }
                }

                if ($aNode->childElementCount > 1 || $hasTextNode) {
                    throw new HierarchyRequestError();
                } else {
                    if (
                        $aNode->childElementCount == 1 &&
                        ($this->childElementCount ||
                            $aChild instanceof DocumentType)
                    ) {
                        throw new HierarchyRequestError();
                    }

                    if ($aChild !== null) {
                        $tw = new TreeWalker(
                            $aChild,
                            NodeFilter::SHOW_DOCUMENT_TYPE
                        );

                        if ($tw->nextNode() !== null) {
                            throw new HierarchyRequestError();
                        }
                    }
                }
            } elseif ($aNode instanceof Element) {
                $parentHasElementChild = false;

                foreach ($this->mChildNodes as $node) {
                    if ($node instanceof Element) {
                        $parentHasElementChild = true;

                        break;
                    }
                }

                if ($parentHasElementChild || $aChild instanceof DocumentType) {
                    throw new HierarchyRequestError();
                }

                if ($aChild !== null) {
                    $tw = new TreeWalker(
                        $aChild,
                        NodeFilter::SHOW_DOCUMENT_TYPE
                    );

                    if ($tw->nextNode() !== null) {
                        throw new HierarchyRequestError();
                    }
                }
            } elseif ($aNode instanceof DocumentType) {
                $parentHasDocTypeChild = false;

                foreach ($this->mChildNodes as $node) {
                    if ($node instanceof DocumentType) {
                        $parentHasDocTypeChild = true;

                        break;
                    }
                }

                if ($aChild !== null) {
                    $ownerDocument = $this->mOwnerDocument ?: $this;
                    $tw = new TreeWalker(
                        $ownerDocument,
                        NodeFilter::SHOW_ELEMENT
                    );
                    $tw->currentNode = $aChild;

                    if ($tw->previousNode() !== null) {
                        throw new HierarchyRequestError();
                    }
                }

                $parentHasElementChild = false;

                foreach ($this->mChildNodes as $node) {
                    if ($node instanceof Element) {
                        $parentHasElementChild = true;

                        break;
                    }
                }

                if (
                    $parentHasDocTypeChild ||
                    ($aChild !== null && $parentHasElementChild)
                ) {
                    throw new HierarchyRequestError();
                }
            }
        }
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
     * Gets the root element of the given node.
     *
     * @link https://html.spec.whatwg.org/multipage/infrastructure.html#root-element
     *
     * @param Node $aNode The node whose root element is to be found.
     *
     * @return Element|null
     */
    public static function _getRootElement(Node $aNode)
    {
        if ($aNode instanceof Document) {
            return $aNode->firstElementChild;
        }

        if (!$aNode->mParentElement) {
            return $aNode;
        }

        $node = $aNode->mParentElement;

        while ($node && $node->mParentElement) {
            $node = $node->mParentElement;
        }

        return $node;
    }

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
        $index = 0;
        $sibling = $this->mPreviousSibling;

        while ($sibling) {
            $index++;
            $sibling = $sibling->mPreviousSibling;
        }

        return $index;
    }

    /**
     * Returns the Node's length.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-node-length
     *
     * @return int
     */
    public function _getNodeLength()
    {
        return count($this->mChildNodes);
    }

    /**
     * Gets a node's root.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-tree-root
     *
     * @param Node $aNode The node whose root is to be returned.
     *
     * @return Node The node's root.
     */
    public static function getRootNode(Node $aNode)
    {
        $root = $aNode;

        while ($root->mParentNode) {
            $root = $root->mParentNode;
        }

        return $root;
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
            $ownerDocument = $this->mOwnerDocument ?: $this;
            Document::adopt($aNode, $ownerDocument);
        }

        $removedNodes = $this->mChildNodes;

        if (!$aNode) {
            $addedNodes = array();
        } elseif ($aNode instanceof DocumentFragment) {
            $addedNodes = $aNode->mChildNodes;
        } else {
            $addedNodes = array($aNode);
        }

        foreach ($removedNodes as $index => $node) {
            self::removeNode($node, $this, true);
        }

        if ($aNode) {
            self::insertNode($aNode, $this, null, true);
        }

        // TODO: Queue a mutation record for "childList"
    }

    protected function getBaseURI()
    {
        $ssl = isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == 'on';
        $port = in_array($_SERVER['SERVER_PORT'], array(80, 443)) ?
            '' : ':' . $_SERVER['SERVER_PORT'];
        $url = ($ssl ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] .
            $port . $_SERVER['REQUEST_URI'];

        return $url;
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
     * @return DocumentFragment|Node If $aNodes > 1, then a DocumentFragment is
     *     returned, otherwise a single Node is returned.
     */
    protected static function convertNodesToNode($aNodes)
    {
        $node = null;
        $nodes = $aNodes;

        // Turn all strings into Text nodes.
        array_walk($nodes, function (&$aArg) {
            if (is_string($aArg)) {
                $aArg = new Text($aArg);
            }
        });

        // If we were given mutiple nodes, throw them all into a
        // DocumentFragment
        if (count($nodes) > 1) {
            $node = new DocumentFragment();

            try {
                foreach ($nodes as $arg) {
                    $node->appendChild($arg);
                }
            } catch (Exception $e) {
                throw $e;
            }
        } else {
            $node = $nodes[0];
        }

        return $node;
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
     * @param Node $aParent The parent of the node being removed.
     *
     * @return Node The node that was removed.
     *
     * @throws NotFoundError If the parent of the node being removed does not
     *     match the given parent node.
     */
    protected static function preremoveNode(Node $aChild, Node $aParent)
    {
        if ($aChild->mParentNode !== $aParent) {
            throw new NotFoundError();
        }

        self::removeNode($aChild, $aParent);

        return $aChild;
    }

    /**
     * Invokes all callbacks associated with a given event and Node.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-event-listener-invoke
     *
     * @param Event $aEvent The event currently being dispatched.
     *
     * @param Node $aTarget The current target of the event being dispatched.
     */
    private function invokeEventListener($aEvent, $aTarget)
    {
        $listeners = $aTarget->mEvents;
        $aEvent->_setCurrentTarget($aTarget);

        for ($i = 0, $count = count($listeners); $i < $count; $i++) {
            if (
                $aEvent->_getFlags() & Event::EVENT_STOP_IMMEDIATE_PROPAGATION
            ) {
                break;
            }

            $phase = $aEvent->eventPhase;

            if (
                $aEvent->type !== $listeners[$i]['type'] ||
                ($phase === Event::CAPTURING_PHASE &&
                    !$listeners[$i]['capture']) ||
                ($phase === Event::BUBBLING_PHASE &&
                    $listeners[$i]['capture'])
            ) {
                continue;
            }

            call_user_func($listeners[$i]['callback'], $aEvent);
        }
    }
}
