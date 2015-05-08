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
    public $textContent; // String

    private $mEvents;

    protected function __construct() {
        $this->mChildNodes = array();
        $this->mFirstChild = null;
        $this->mLastChild = null;
        $this->mNextSibling = null;
        $this->mNodeName = '';
        $this->mNodeType = '';
        $this->mNodeValue = null;
        $this->mOwnerDocument = null;
        $this->mParentElement = null;
        $this->mParentNode = null;
        $this->mPreviousSibling = null;
        $this->mEvents = array();
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
        }
    }

    /**
     * Registers a callback for a specified event on the current node.
     * @param string                    $aEventName     The name of the event to listen for.
     * @param callable|EventListener    $aCallback      A callback that will be executed when the event occurs.  If an
     *                                                  object that inherits from the EventListener interface is given,
     *                                                  it will use the handleEvent method on the object as the
     *                                                  callback.
     * @param boolean                   $aUseCapture    Optional. Specifies whether or not the event should be handled
     *                                                  during the capturing or bubbling phase.
     */
    public function addEventListener($aEventName, $aCallback, $aUseCapture = false) {
        if (!array_key_exists($aEventName, $this->mEvents)) {
            $this->mEvents[$aEventName] = array();
            $this->mEvents[$aEventName][Event::CAPTURING_PHASE] = array();
            $this->mEvents[$aEventName][Event::BUBBLING_PHASE] = array();
        }

        if ($aCallback instanceof EventListener) {
            $callback = array($aCallback, 'handleEvent');
        } else {
            $callback = $aCallback;
        }

        $useCapture = $aUseCapture ? Event::CAPTURING_PHASE : Event::BUBBLING_PHASE;

        if (!in_array($callback, $this->mEvents[$aEventName][$useCapture])) {
            array_unshift($this->mEvents[$aEventName][$useCapture], $callback);
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
        return $this->preinsertNodeBeforeChild($aNode, null);
    }

    /**
     * Returns a copy of the node upon which the method was called.
     * @param  boolean $aDeep If true, all child nodes and event listeners should be cloned as well.
     * @return Node           The copy of the node.
     */
    public function cloneNode($aDeep = false) {
        // TODO
    }

    /**
     * Compares the position of a node against another node.
     * @param  Node   $aNode Node to compare position against.
     * @return integer       A bitmask representing the nodes position.  Possible values are as follows:
     *                         Node::DOCUMENT_POSITION_DISCONNECTED
     *                         Node::DOCUMENT_POSITION_PRECEDING
     *                         Node::DOCUMENT_POSITION_FOLLOWING
     *                         Node::DOCUMENT_POSITION_CONTAINS
     *                         Node::DOCUMENT_POSITION_CONTAINED_BY
     *                         Node::DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC
     */
    public function compareDocumentPosition(Node $aNode) {
        // TODO
    }

    /**
     * Returns whether or not a node is a descendant of another node.
     * @param  Node     $aNode A node that you wanted to compare its position of.
     * @return boolean         Returns true if $aNode is a descendant of a node.
     */
    public function contains(Node $aNode) {
        $rv = false;

        if (!$this->hasChildNodes()) {
            return $rv;
        }

        foreach ($this->mChildNodes as $node) {
            $rv = $node == $aNode;

            if (!$rv) {
                foreach ($node->mChildNodes as $childNode) {
                    $rv = $childNode->contains($aNode);

                    if ($rv) {
                        break 2;
                    }
                }
            }
        }

        return $rv;
    }

    /**
     * Dispatches an event at the current EventTarget, which will then invoke any event listeners on the node.
     * @param  Event  $aEvent An object representing the specific event dispatched with information regarding
     *                        that event.
     * @return boolean        Returns false if at least one event handler calles Event.preventDefault(), otherwise it
     *                        returns true.
     */
    public function dispatchEvent(Event $aEvent) {
        $node = $this;

        while ($node->parentNode) {
            $node = $node->parentNode;
        }

        $aEvent->_setDispatched();
        $aEvent->_setTarget($this);
        $aEvent->_setTimeStamp(microtime());
        $aEvent->_updateEventPhase(($node != $this ? Event::CAPTURING_PHASE : Event::AT_TARGET));
        $node->_dispatchEvent($aEvent);
    }

    /**
     * Handles executing the callbacks registered to a specific event on this Node.  It also takes care of handling
     * event propagation and the event phases.
     * @param  Event   &$aEvent         The event object associated with the currently executing event.
     * @param  boolean $aMoveToBubbling Phase When the event phase is AT_TARGET, it first executes any event handlers
     *                                  on the target Node that are associated with the CAPTURING_PHASE.  Another event
     *                                  will then be dispatched on the same Node in the AT_TARGET phase again which
     *                                  executes and event handlers on the target that are associated with the
     *                                  BUBBLING_PHASE.  When set to true, this means that the dispatched event has
     *                                  already been through the AT_TARGET phase once.
     */
    private function _dispatchEvent(Event &$aEvent, $aMoveToBubblingPhase = false) {
        if ($aEvent->_isPropagationStopped()) {
            $aEvent->_updateEventPhase(Event::NONE);

            return;
        }

        $moveToBubblingPhase = $aMoveToBubblingPhase;

        if (array_key_exists($aEvent->type, $this->mEvents)) {
            $aEvent->_setCurrentTarget($this);
            $eventPhase = $moveToBubblingPhase ? Event::BUBBLING_PHASE : Event::CAPTURING_PHASE;

            foreach ($this->mEvents[$aEvent->type][$eventPhase] as $callback) {
                if ($aEvent->_isImmediatePropagationStopped()) {
                    break;
                }

                call_user_func($callback, $aEvent);
            }
        }

        switch ($aEvent->eventPhase) {
            case Event::CAPTURING_PHASE:
                $node = $aEvent->target;

                while ($node) {
                    if ($node->parentNode == $this) {
                        break;
                    }

                    $node = $node->parentNode;
                }

                if ($this == $aEvent->target->parentNode) {
                    $aEvent->_updateEventPhase(Event::AT_TARGET);
                }

                break;

            case Event::AT_TARGET:
                if ($moveToBubblingPhase) {
                    $node = $this->parentNode;
                    $aEvent->_updateEventPhase(Event::BUBBLING_PHASE);
                } else {
                    $node = $this;
                    $moveToBubblingPhase = true;
                }

                break;

            case Event::BUBBLING_PHASE:
                $node = $this->parentNode;
        }

        if ($node) {
            $node->_dispatchEvent($aEvent, $moveToBubblingPhase);
        } else {
            // The event listener has run its course.
            $aEvent->_updateEventPhase(Event::NONE);
        }
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
        return $this->preinsertNodeBeforeChild($aNewNode, $aRefNode);
    }

    /**
     * Compares two nodes to see if they are equal.
     * @param  Node    $aNode The node you want to compare the current node to.
     * @return boolean        Returns true if the two nodes are the same, otherwise false.
     */
    public function isEqualNode(Node $aNode) {
        return $this === $aNode;
    }

    /**
     * "Normalizes" the node and its sub-tree so that there are no empty text nodes present and
     * there are no text nodes that appear consecutively.
     */
    public function normalize() {
        if ($this->hasChildNodes()) {
            $nodesToRemove = [];
            $lastValidTextNode;

            foreach($this->mChildNodes as $node) {
                switch($node->mNodeType) {
                    case Node::TEXT_NODE:
                        if (empty($node->textContent)) {
                            $nodesToRemove[] = $node;
                            break;
                        }

                        if (!is_null($node->mPreviousSibling) &&
                            $node->mPreviousSibling->mNodeType != Node::TEXT_NODE) {
                            $lastValidTextNode = $node;
                        }

                        if ($node != $lastValidTextNode && !is_null($node->mNextSibling) &&
                            $node->mNextSibling->mNodeType == Node::TEXT_NODE) {
                            $lastValidTextNode->textContent .= $node->mNextSibling->textContent;
                            $nodesToRemove[] = $node->mNextSibling;
                        }

                        break;

                    case Node::ELEMENT_NODE:
                        $node->normalize();

                        break;
                }
            }

            if (!empty($nodesToRemove)) {
                foreach($nodesToRemove as $node) {
                    $this->removeChild($node);
                }
            }
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
     * @param string                    $aEventName     The name of the event to listen for.
     * @param callable|EventListener    $aCallback      A callback that will be executed when the event occurs.  If an
     *                                                  object that inherits from the EventListener interface is given,
     *                                                  it will use the handleEvent method on the object as the
     *                                                  callback.
     * @param boolean                   $aUseCapture    Optional. Specifies whether or not the event should be handled
     *                                                  during the capturing or bubbling phase.
     */
    public function removeEventListener($aEventName, $aCallback, $aUseCapture = false) {
        if (array_key_exists($aEventName, $this->mEvents)) {
            $useCapture = $aUseCapture ? Event::CAPTURING_PHASE : Event::BUBBLING_PHASE;

            if ($aCallback instanceof EventListener) {
                $callback = array($aCallback, 'handleEvent');
            } else {
                $callback = $aCallback;
            }

            $index = array_search($callback, $this->mEvents[$aEventName][$useCapture]);

            if ($index !== false) {
                array_splice($this->mEvents[$aEventName][$useCapture], $index, 1);
            }
        }
    }

    /**
     * Replaces a node with another node.
     * @param  Node $aNewNode The node to be inserted into the DOM.
     * @param  Node $aOldNode The node that is being replaced by the new node.
     * @return Node           The node that was replaced in the DOM.
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
        $this->insertNodeBeforeChild($aNewNode, $referenceChild, true);

        return $aOldNode;
    }

    private function insertNodeBeforeChild($aNode, $aChild, $aSuppressObservers = null) {
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

        if ($index === 0) {
            $this->mFirstChild = $nodes[0];
        }

        foreach ($nodes as $newNode) {
            $newNode->mParentNode = $this;
            $newNode->mParentElement = $parentElement;

            if ($aChild) {
                $newNode->mPreviousSibling = $aChild->previousSibling;
                $newNode->mNextSibling = $aChild;
                $aChild->mPreviousSibling = $newNode;
            } else {
                $newNode->mPreviousSibling = $this->mLastChild;
                $this->mLastChild = $newNode;
            }

            array_splice($this->mChildNodes, $index++, 0, array($newNode));
        }
    }

    private function _removeChild($aNode, $aSuppressObservers = null) {
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

    private function preinsertNodeBeforeChild($aNode, $aChild) {
        $this->preinsertionValidity($aNode, $aChild);
        $referenceChild = $aChild;

        if ($referenceChild === $aNode) {
            $referenceChild = $aNode->nextSibling;
        }

        // The DOM4 spec states that nodes should be implicitly adopted
        $ownerDocument = $this instanceof Document ? $this : $this->mOwnerDocument;
        $ownerDocument->adoptNode($aNode);

        $this->insertNodeBeforeChild($aNode, $referenceChild);

        return $aNode;
    }

    private function preremoveChild($aChild) {
        if ($aChild->parentNode !== $this) {
            throw new NotFoundError;
        }

        $this->_removeChild($aChild);

        return $aChild;
    }

    private function preinsertionValidity($aNode, $aChild) {
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
                    $node = $aChild ? $aChild->nextSibling : null;
                    $docTypeFollowsChild = false;

                    while ($node) {
                        if ($node instanceof DocumentType) {
                            $docTypeFollowsChild = true;

                            break;
                        }

                        $node = $node->nextSibling;
                    }

                    if ($aNode->childElementCount == 1 && ($this->childElementCount ||
                        $aChild instanceof DocumentType || ($aChild !== null && $docTypeFollowsChild))) {
                        throw new HierarchyRequestError;
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

                $node = $aChild ? $aChild->nextSibling : null;
                $docTypeFollowsChild = false;

                while ($node) {
                    if ($node instanceof DocumentType) {
                        $docTypeFollowsChild = true;

                        break;
                    }

                    $node = $node->nextSibling;
                }

                if ($parentHasElementChild || $aChild instanceof DocumentType ||
                    ($aChild !== null && $docTypeFollowsChild)) {
                    throw new HierarchyRequestError;
                }
            } elseif ($aNode instanceof DocumentType) {
                $parentHasDocTypeChild = false;

                foreach ($this->mChildNodes as $node) {
                    if ($node instanceof DocumentType) {
                        $parentHasDocTypeChild = true;

                        break;
                    }
                }

                $node = $aChild ? $aChild->previousSibling : null;
                $elementPrecedesChild = false;

                while ($node) {
                    if ($node instanceof Element) {
                        $elementPrecedesChild = true;
                        break;
                    }

                    $node = $node->previousSibling;
                }

                $parentHasElementChild = false;

                foreach ($this->mChildNodes as $node) {
                    if ($node instanceof Element) {
                        $parentHasElementChild = true;

                        break;
                    }
                }

                if ($parentHasDocTypeChild || $elementPrecedesChild || ($aChild !== null && $parentHasElementChild)) {
                    throw new HierarchyRequestError;
                }
            }
        }
    }
}
