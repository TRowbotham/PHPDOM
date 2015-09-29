<?php
// https://developer.mozilla.org/en-US/docs/Web/API/Node
// https://dom.spec.whatwg.org/#node

require_once 'NodeList.class.php';
require_once 'EventTarget.class.php';

abstract class Node implements EventTarget {
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

    protected function __construct() {
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

    public function __get( $aName ) {
        switch ($aName) {
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

            case 'textContent':
                switch (true) {
                    case $this instanceof DocumentFragment:
                    case $this instanceof Element:
                        $tw = $this->mOwnerDocument->createTreeWalker($this, NodeFilter::SHOW_TEXT);
                        $textContent = '';

                        while ($node = $tw->nextNode()) {
                            $textContent .= $node->data;
                        }

                        return $textContent;

                    case $this instanceof Text:
                    case $this instanceof ProcessingInstruction:
                    case $this instanceof Comment:
                        return $this->mData;

                    default:
                        return null;
                }
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'textContent':
                $value = $aValue === null ? '' : $aValue;

                switch (true) {
                    case $this instanceof DocumentFragment:
                    case $this instanceof Element:
                        $node = null;

                        if ($value !== '') {
                            $node = $this->mOwnerDocument->createTextNode($value);
                        }

                        $this->_replaceAll($node);

                        break;

                    case $this instanceof Text:
                    case $this instanceof ProcessingInstruction:
                    case $this instanceof Comment:
                        $this->replaceData(0, $this->length, $value);

                        break;
                }
        }
    }

    /**
     * Registers a callback for a specified event on the current node.
     *
     * @param string                    $aEventName     The name of the event to listen for.
     *
     * @param callable|EventListener    $aCallback      A callback that will be executed when the event occurs.  If an
     *                                                  object that inherits from the EventListener interface is given,
     *                                                  it will use the handleEvent method on the object as the
     *                                                  callback.
     *
     * @param boolean                   $aUseCapture    Optional. Specifies whether or not the event should be handled
     *                                                  during the capturing or bubbling phase.
     */
    public function addEventListener($aEventName, $aCallback, $aUseCapture = false) {
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
     * Appends a node to the parent node.  If the node being appended is already associated
     * with another parent node, it will be removed from that parent node before being appended
     * to the current parent node.
     * @param  Node   $aNode A node representing an element on the page.
     * @return Node          The node that was just appended to the parent node.
     */
    public function appendChild(Node $aNode) {
        return $this->_preinsertNodeBeforeChild($aNode, null);
    }

    /**
     * Returns a copy of the node upon which the method was called.
     * @link   https://dom.spec.whatwg.org/#dom-node-clonenodedeep
     * @param  boolean $aDeep If true, all child nodes and event listeners should be cloned as well.
     * @return Node           The copy of the node.
     */
    public function cloneNode($aDeep = false) {
        // TODO
    }

    /**
     * Compares the position of a node against another node.
     * @link   https://dom.spec.whatwg.org/#dom-node-comparedocumentpositionother
     * @param  Node   $aNode Node to compare position against.
     * @return integer       A bitmask representing the nodes position.  Possible values are as follows:
     *                         Node::DOCUMENT_POSITION_DISCONNECTED
     *                         Node::DOCUMENT_POSITION_PRECEDING
     *                         Node::DOCUMENT_POSITION_FOLLOWING
     *                         Node::DOCUMENT_POSITION_CONTAINS
     *                         Node::DOCUMENT_POSITION_CONTAINED_BY
     *                         Node::DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC
     */
    public function compareDocumentPosition(Node $aOtherNode) {
        $reference = $this;

        if ($reference === $aOtherNode) {
            return 0;
        }

        if ($reference->mOwnerDocument !== $aOtherNode->ownerDocument || !$reference->parentNode ||
            !$aOtherNode->parentNode) {
            return self::DOCUMENT_POSITION_DISCONNECTED + self::DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC +
                   self::DOCUMENT_POSITION_PRECEDING;
        }

        if ($aOtherNode->contains($reference)) {
            return self::DOCUMENT_POSITION_CONTAINS + self::DOCUMENT_POSITION_PRECEDING;
        }

        if ($reference->contains($aOtherNode)) {
            return self::DOCUMENT_POSITION_CONTAINED_BY + self::DOCUMENT_POSITION_FOLLOWING;
        }

        $commonParent = $reference->mParentNode;

        while ($commonParent) {
            if ($commonParent->contains($aOtherNode)) {
                break;
            }

            $commonParent = $commonParent->parentNode;
        }

        $referenceKey = -1;
        $otherKey = -1;

        foreach ($commonParent->childNodes as $child) {
            if ($referenceKey < 0 && $child->contains($reference)) {
                $referenceKey = key($commonParent->childNodes);
            }

            if ($otherKey < 0 && $child->contains($aOtherNode)) {
                $otherKey = key($commonParent->childNodes);
            }
        }

        if ($otherKey < $referenceKey) {
            return self::DOCUMENT_POSITION_PRECEDING;
        }

        return self::DOCUMENT_POSITION_FOLLOWING;
    }

    /**
     * Returns whether or not a node is a descendant of another node.
     * @param  Node     $aNode A node that you wanted to compare its position of.
     * @return boolean         Returns true if $aNode is a descendant of a node.
     */
    public function contains(Node $aNode) {
        $rv = false;

        foreach ($this->mChildNodes as $node) {
            if ($rv) {
                break;
            }

            $rv = $node === $aNode || $node->contains($aNode);
        }

        return $rv;
    }

    /**
     * Dispatches an event at the current EventTarget, which will then invoke any event listeners on the node and
     * its ancestors.
     *
     * @param  Event    $aEvent An object representing the specific event dispatched with information regarding
     *                          that event.
     *
     * @return boolean          Returns true if the event is not cancelable or if the preventDefault() method is not
     *                          invoked, otherwise it returns false.
     */
    public function dispatchEvent(Event $aEvent) {
        if ($aEvent->_getFlags() & Event::EVENT_DISPATCHED || !($aEvent->_getFlags() & Event::EVENT_INITIALIZED)) {
            throw new InvalidStateError;
        }

        $aEvent->_setIsTrusted(false);
        $aEvent->_setFlag(Event::EVENT_DISPATCHED);
        $aEvent->_setTarget($this);
        $eventPath = array();
        $node = $this->parentNode;

        while ($node) {
            $eventPath[] = $node;
            $node = $node->parentNode;
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

        return !$aEvent->cancelable || !($aEvent->_getFlags() & Event::EVENT_CANCELED);
    }

    /**
     * Returns a boolean indicating whether or not the current node contains any nodes.
     * @return boolean Returns true if at least one child node is present, otherwise false.
     */
    public function hasChildNodes() {
        return !empty($this->mChildNodes);
    }

    /**
     * Inserts a node before another node in a common parent node.
     * @param  Node   $aNewNode The node to be inserted into the document.
     * @param  Node   $aRefNode The node that the new node will be inserted before.
     * @return Node             The node that was inserted into the document.
     */
    public function insertBefore(Node $aNewNode, Node $aRefNode = null) {
        return $this->_preinsertNodeBeforeChild($aNewNode, $aRefNode);
    }

    /**
     * Compares two nodes to see if they are equal.
     * @link   https://dom.spec.whatwg.org/#concept-node-equals
     * @param  Node    $aNode The node you want to compare the current node to.
     * @return boolean        Returns true if the two nodes are the same, otherwise false.
     */
    public function isEqualNode(Node $aNode) {
        return $this === $aNode;
    }

    /**
     * "Normalizes" the node and its sub-tree so that there are no empty text nodes present and
     * there are no text nodes that appear consecutively.
     *
     * @link https://dom.spec.whatwg.org/#dom-node-normalize
     */
    public function normalize() {
        $ownerDocument = $this instanceof Document ? $this : $this->mOwnerDocument;
        $nextSibling = $this->mNextSibling;
        $tw = $ownerDocument->createTreeWalker($this, NodeFilter::SHOW_TEXT);

        while ($node = $tw->nextNode()) {
            $length = $node->length;

            if (!$length) {
                $this->_removeChild($node);
                continue;
            }

            $data = '';
            $contingiousTextNodes = array();
            $startNode = $node->previousSibling;

            while ($startNode) {
                if (!($startNode instanceof Text)) {
                    break;
                }

                $data .= $startNode->data;
                $contingiousTextNodes[] = $startNode;
                $startNode = $startNode->previousSibling;
            }

            $startNode = $node->nextSibling;

            while ($startNode) {
                if (!($startNode instanceof Text)) {
                    break;
                }

                $data .= $startNode->data;
                $contingiousTextNodes[] = $startNode;
                $startNode = $startNode->nextSibling;
            }

            $node->replaceData($length, 0, $data);
            $currentNode = $node->nextSibling;

            while ($currentNode instanceof Text) {
                foreach (Range::_getRangeCollection() as $index => $range) {
                    if ($range->startContainer === $currentNode) {
                        $range->setStart($node, $range->startOffset + $length);
                    }
                }

                foreach (Range::_getRangeCollection() as $index => $range) {
                    if ($range->endContainer === $currentNode) {
                        $range->setStart($node, $range->endOffset + $length);
                    }
                }

                foreach (Range::_getRangeCollection() as $index => $range) {
                    if ($range->startContainer === $currentNode->parentNode && $range->startOffset == $currentNode->_getTreeIndex()) {
                        $range->setStart($node, $length);
                    }
                }

                foreach (Range::_getRangeCollection() as $index => $range) {
                    if ($range->endContainer === $currentNode->parentNode && $range->endOffset == $currentNode->_getTreeIndex()) {
                        $range->setEnd($node, $length);
                    }
                }

                $length += $currentNode->length;
                $currentNode = $currentNode->nextSibling;
            }

            foreach ($contingiousTextNodes as $textNode) {
                $textNode->parentNode->removeChild($textNode);
            }

            unset($contingiousTextNodes);
        }
    }

    /**
     * Removes the specified node from the current node.
     * @param  Node   $aNode The node to be removed from the DOM.
     * @return Node          The node that was removed from the DOM.
     */
    public function removeChild(Node $aNode) {
        return $this->preremoveChild($aNode);
    }

    /**
     * Unregisters a callback for a specified event on the current node.
     *
     * @param string                    $aEventName     The name of the event to listen for.
     *
     * @param callable|EventListener    $aCallback      A callback that will be executed when the event occurs.  If an
     *                                                  object that inherits from the EventListener interface is given,
     *                                                  it will use the handleEvent method on the object as the
     *                                                  callback.
     *
     * @param boolean                   $aUseCapture    Optional. Specifies whether or not the event should be handled
     *                                                  during the capturing or bubbling phase.
     */
    public function removeEventListener($aEventName, $aCallback, $aUseCapture = false) {
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
     * Replaces a node with another node.
     * @link   https://dom.spec.whatwg.org/#concept-node-replace
     * @param  Node $aNewNode The node to be inserted into the DOM.
     * @param  Node $aOldNode The node that is being replaced by the new node.
     * @return Node           The node that was replaced in the DOM.
     * @throws HierarchyRequestError
     * @throws NotFoundError
     */
    public function replaceChild(Node $aNewNode, Node $aOldNode) {
        if (!($this instanceof Document) &&
            !($this instanceof DocumentFragment) &&
            !($this instanceof Element)) {
            throw new HierarchyRequestError;
        }

        if ($this === $aNewNode || $aNewNode->contains($this)) {
            throw new HierarchyRequestError;
        }

        if ($aOldNode->parentNode !== $this) {
            throw new NotFoundError;
        }

        if (!($aNewNode instanceof DocumentFragment) &&
            !($aNewNode instanceof DocumentType) && !($aNewNode instanceof Element) &&
            !($aNewNode instanceof Text) &&
            !($aNewNode instanceof ProcessingInstruction) &&
            !($aNewNode instanceof Comment)) {
            throw new HierarchyRequestError;
        }

        if (($aNewNode instanceof Text && $this instanceof Document) ||
            ($aNewNode instanceof DocumentType && !($this instanceof Document))) {
            throw new HierarchyRequestError;
        }

        if ($this instanceof Document) {
            if ($aNewNode instanceof DocumentFragment) {
                $hasTextNode = array_filter($this->mChildNodes, function($node) {
                    return $node instanceof Text;
                });

                if ($aNewNode->childElementCount > 1 || $hasTextNode) {
                    throw new HierarchyRequestError;
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

                    if ($this->childElementCount == 1 && ($this->children[0] !== $aOldNode || $docTypeFollowsChild)) {
                        throw new HierarchyRequestError;
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
                    throw new HierarchyRequestError;
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
                    throw new HierarchyRequestError;
                }
            }
        }

        $referenceChild = $aOldNode->nextSibling;

        if ($referenceChild === $aNewNode) {
            $referenceChild = $aNewNode->nextSibling;
        }

        // The DOM4 spec states that nodes should be implicitly adopted
        $ownerDocument = $this instanceof Document ? $this : $this->mOwnerDocument;
        $ownerDocument->adoptNode($aNewNode);

        $this->_removeChild($aOldNode, true);

        $nodes = $aNewNode instanceof DocumentFragment ? $aNewNode->childNodes : array($aNewNode);
        $this->_insertNodeBeforeChild($aNewNode, $referenceChild, true);

        return $aOldNode;
    }

    /**
     * @internal
     * @link   https://dom.spec.whatwg.org/#concept-node-ensure-pre-insertion-validity
     * @param  DocumentFragment|Node    $aNode  The nodes being inserted into the document tree.
     * @param  Node                     $aChild The reference node for where the new nodes should be inserted.
     * @throws HierarchyRequestError
     * @throws NotFoundError
     */
    public function _ensurePreinsertionValidity($aNode, $aChild) {
        if (!($this instanceof Document) &&
            !($this instanceof DocumentFragment) &&
            !($this instanceof Element)) {
            throw new HierarchyRequestError;
        }

        if ($this === $aNode || $aNode->contains($this)) {
            throw new HierarchyRequestError;
        }

        if ($aChild !== null && $this !== $aChild->parentNode) {
            throw new NotFoundError;
        }

        if (!($aNode instanceof DocumentFragment) &&
            !($aNode instanceof DocumentType) && !($aNode instanceof Element) &&
            !($aNode instanceof Text) &&
            !($aNode instanceof ProcessingInstruction) &&
            !($aNode instanceof Comment)) {
            throw new HierarchyRequestError;
        }

        if (($aNode instanceof Text && $this instanceof Document) ||
            ($aNode instanceof DocumentType && !($this instanceof Document))) {
            throw new HierarchyRequestError;
        }

        if ($this instanceof Document) {
            if ($aNode instanceof DocumentFragment) {
                $hasTextNode = false;

                foreach ($aNode->childNodes as $node) {
                    if ($node instanceof Text) {
                        $hasTextNode = true;

                        break;
                    }
                }

                if ($aNode->childElementCount > 1 || $hasTextNode) {
                    throw new HierarchyRequestError;
                } else {
                    if ($aNode->childElementCount == 1 && ($this->childElementCount || $aChild instanceof DocumentType)) {
                        throw new HierarchyRequestError;
                    }

                    if ($aChild !== null) {
                        $tw = $aChild->ownerDocument->createTreeWalker($aChild, NodeFilter::SHOW_DOCUMENT_TYPE);

                        if ($tw->nextNode() !== null) {
                            throw new HierarchyRequestError;
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
                    throw new HierarchyRequestError;
                }

                if ($aChild !== null) {
                    $tw = $aChild->ownerDocument->createTreeWalker($aChild, NodeFilter::SHOW_DOCUMENT_TYPE);

                    if ($tw->nextNode() !== null) {
                        throw new HierarchyRequestError;
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
                    $tw = $aChild->ownerDocument->createTreeWalker($aChild->ownerDocument, NodeFilter::SHOW_ELEMENT);
                    $tw->currentNode = $aChild;

                    if ($tw->previousNode() !== null) {
                        throw new HierarchyRequestError;
                    }
                }

                $parentHasElementChild = false;

                foreach ($this->mChildNodes as $node) {
                    if ($node instanceof Element) {
                        $parentHasElementChild = true;

                        break;
                    }
                }

                if ($parentHasDocTypeChild || ($aChild !== null && $parentHasElementChild)) {
                    throw new HierarchyRequestError;
                }
            }
        }
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
    public function _getTreeIndex() {
        $index = 0;
        $sibling = $this->previousSibling;

        while ($sibling) {
            $index++;
            $sibling = $sibling->previousSibling;
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
    public function _getNodeLength() {
        return count($this->mChildNodes);
    }

    /**
     * @internal
     * @link   https://dom.spec.whatwg.org/#concept-node-insert
     * @param  DocumentFragment|Node    $aNode              The nodes to be inserted into the document tree.
     * @param  Node                     $aChild             The reference node for where the new nodes should be inserted.
     * @param  bool                     $aSuppressObservers Optional.  If true, mutation events are ignored for this
     *                                                         operation.
     */
    public function _insertNodeBeforeChild($aNode, $aChild, $aSuppressObservers = null) {
        $isDocumentFragment = $aNode instanceof DocumentFragment;
        $parentElement = $this instanceof Element ? $this : null;
        $count = $isDocumentFragment ? count($aNode->childNodes) : 1;

        if ($aChild) {
            // TODO substeps for Step 2
        }

        $nodes = $isDocumentFragment ? $aNode->childNodes : array($aNode);
        $index = $aChild ? array_search($aChild, $this->mChildNodes) : count($this->mChildNodes);

        if ($isDocumentFragment) {
            foreach ($nodes as $node) {
                $aNode->_removeChild($node, true);
            }
        }

        if (!$aSuppressObservers) {

        }

        if ($index === 0) {
            $this->mFirstChild = $nodes[0];
        }

        foreach ($nodes as $newNode) {
            $newNode->mParentNode = $this;
            $newNode->mParentElement = $parentElement;

            if ($aChild) {
                $newNode->mPreviousSibling = $aChild->previousSibling;
                $newNode->mNextSibling = $aChild;

                if ($aChild->previousSibling) {
                    $aChild->previousSibling->mNextSibling = $newNode;
                }

                $aChild->mPreviousSibling = $newNode;
            } else {
                $newNode->mPreviousSibling = $this->mLastChild;

                if ($this->mLastChild) {
                    $this->mLastChild->mNextSibling = $newNode;
                }

                $this->mLastChild = $newNode;
            }

            array_splice($this->mChildNodes, $index++, 0, array($newNode));
        }
    }

    /**
     * @internal
     * @link   https://dom.spec.whatwg.org/#concept-node-pre-insert
     * @param  DocumentFragment|Node    $aNode  The nodes to be inserted into the document tree.
     * @param  Node                     $aChild The reference node for where the new nodes should be inserted.
     * @return DocumentFragment|Node            The nodes that were insterted into the document tree.
     */
    public function _preinsertNodeBeforeChild($aNode, $aChild) {
        $this->_ensurePreinsertionValidity($aNode, $aChild);
        $referenceChild = $aChild;

        if ($referenceChild === $aNode) {
            $referenceChild = $aNode->nextSibling;
        }

        // The DOM4 spec states that nodes should be implicitly adopted
        $ownerDocument = $this instanceof Document ? $this : $this->mOwnerDocument;
        $ownerDocument->adoptNode($aNode);

        $this->_insertNodeBeforeChild($aNode, $referenceChild);

        return $aNode;
    }

    /**
     * Replaces all nodes within a parent.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-node-replace-all
     *
     * @param Node $aNode The node that is to be inserted.
     */
    public function _replaceAll(Node $aNode) {
        if ($aNode) {
            $ownerDocument = $this instanceof Document ? $this : $this->mOwnerDocument;
            $ownerDocument->adoptNode($aNode);
        }

        $removedNodes = $this->mChildNodes;

        if (!$aNode) {
            $addedNodes = array();
        } else if ($aNode instanceof DocumentFragment) {
            $addedNodes = $aNode->childNodes;
        } else {
            $addedNodes = array($aNode);
        }

        foreach ($removedNodes as $index => $node) {
            $this->_removeChild($node, true);
        }

        if ($aNode) {
            $this->_insertNodeBeforeChild($aNode, null, true);
        }

        // TODO: Queue a mutation record for "childList"
    }

    /**
     * @internal
     * @link   https://dom.spec.whatwg.org/#mutation-method-macro
     * @param  array                   $aNodes An array of Nodes and strings.
     * @return DocumentFragment|Node           If $aNodes > 1, then a DocumentFragment is
     *                                             returned, otherwise a single Node is returned.
     */
    protected function mutationMethodMacro($aNodes) {
        $node = null;
        $nodes = $aNodes;

        // Turn all strings into Text nodes.
        array_walk($nodes, function(&$aArg) {
            if (is_string($aArg)) {
                $aArg = new Text($aArg);
            }
        });

        // If we were given mutiple nodes, throw them all into a DocumentFragment
        if (count($nodes) > 1) {
            $node = $this->mOwnerDocument->createDocumentFragment();

            foreach ($nodes as $arg) {
                $node->appendChild($arg);
            }
        } else {
            $node = $nodes[0];
        }

        return $node;
    }

    /**
     * @internal
     * @link  https://dom.spec.whatwg.org/#concept-node-remove
     * @param Node $aNode              The Node to be removed from the document tree.
     * @param bool $aSuppressObservers Optional. If true, mutation events are ignored for this
     *                                   operation.
     */
    protected function _removeChild($aNode, $aSuppressObservers = null) {
        $index = array_search($aNode, $this->mChildNodes);

        array_splice($this->mChildNodes, $index, 1);

        if ($this->mFirstChild === $aNode) {
            $this->mFirstChild = $aNode->nextSibling;
        }

        if ($this->mLastChild === $aNode) {
            $this->mLastChild = $aNode->previousSibling;
        }

        if ($aNode->previousSibling) {
            $aNode->previousSibling->mNextSibling = $aNode->nextSibling;
        }

        if ($aNode->nextSibling) {
            $aNode->nextSibling->mPreviousSibling = $aNode->previousSibling;
        }

        $aNode->mPreviousSibling = null;
        $aNode->mNextSibling = null;
        $aNode->mParentElement = null;
        $aNode->mParentNode = null;
    }

    /**
     * @internal
     * Invokes all callbacks associated with a given event and Node.
     *
     * @link https://dom.spec.whatwg.org/#concept-event-listener-invoke
     *
     * @param  Event $aEvent  The event currently being dispatched.
     *
     * @param  Node  $aTarget The current target of the event being dispatched.
     */
    private function invokeEventListener($aEvent, $aTarget) {
        $listeners = $aTarget->mEvents;
        $aEvent->_setCurrentTarget($aTarget);

        for ($i = 0; $i < count($listeners); $i++) {
            if ($aEvent->_getFlags() & Event::EVENT_STOP_IMMEDIATE_PROPAGATION) {
                break;
            }

            if (strcasecmp($aEvent->type, $listeners[$i]['type']) !== 0 ||
                ($aEvent->eventPhase === Event::CAPTURING_PHASE && !$listeners[$i]['capture']) ||
                ($aEvent->eventPhase === Event::BUBBLING_PHASE && $listeners[$i]['capture'])) {
                continue;
            }

            call_user_func($listeners[$i]['callback'], $aEvent);
        }
    }

    /**
     * @internal
     * @link   https://dom.spec.whatwg.org/#concept-node-pre-remove
     * @param  Node $aChild The Node to be removed from the document tree.
     * @return Node         The Node that was removed.
     */
    private function preremoveChild($aChild) {
        if ($aChild->parentNode !== $this) {
            throw new NotFoundError;
        }

        $this->_removeChild($aChild);

        return $aChild;
    }
}
