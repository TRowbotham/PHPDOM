<?php
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
        $this->mNodeValue = '';
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
     * @param string            $aEventName The name of the event to listen for.
     * @param callable|object   $aCallback  A callback that will be executed when the event occurs.  If an object is
     *                                      given, it will use the handleEvent method on the object as the callback if
     *                                      it exists.
     * @param boolean           $aCapture   Optional. Specifies whether or not the event should be handled during
     *                                      the capturing or bubbling phase.
     */
    public function addEventListener($aEventName, $aCallback, $aCapture = false) {
        echo 'addEventListener Called<br>';
        if (!array_key_exists($aEventName, $this->mEvents)) {
            $this->mEvents[$aEventName] = array();
            $this->mEvents[$aEventName][Event::CAPTURING_PHASE] = array();
            $this->mEvents[$aEventName][Event::BUBBLING_PHASE] = array();
        }

        if (is_object($aCallback)) {
            $callback = array($aCallback, 'handleEvent');
        } else {
            $callback = $aCallback;
        }

        $useCapture = $aCapture ? Event::CAPTURING_PHASE : Event::BUBBLING_PHASE;

        if (!in_array($callback, $this->mEvents[$aEventName][$useCapture])) {
            echo 'EVENT ADDED<br>';
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
        $numChildren = count($this->mChildNodes);

        // If the node already exists in the document tree somewhere
        // else, remove it from there first.
        if (!is_null($aNode->mParentNode)) {
            $aNode->mParentNode->removeChild($aNode);
        }

        $this->mChildNodes[] = $aNode;

        if ($numChildren == 0) {
            $this->mFirstChild = $aNode;
        } else {
            $this->mChildNodes[$numChildren - 1]->mNextSibling = $aNode;
            $aNode->mPreviousSibling = $this->mChildNodes[$numChildren - 1];
        }

        $aNode->mParentElement = $this->mNodeType == Node::ELEMENT_NODE ? $this : null;
        $aNode->mParentNode = $this;
        $this->mLastChild = $aNode;

        return $aNode;
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
        if ($aEvent->stopPropagation()) {
            $aEvent->_updateEventPhase(Event::CAPTURING_PHASE);
            return;
        }

        if (empty($aEvent->target)) {
            $aEvent->_setTarget($this);
        }

        if (array_key_exists($aEvent->type, $this->mEvents)) {
            foreach ($this->mEvents[$aEvent->type][$aEvent->eventPhase] as $callback) {
                if ($aEvent->_isImmediatePropagationStopped()) {
                    break;
                }

                call_user_func($callback, $aEvent);
            }
        }

        if ($aEvent->_getNodeStack()->valid()) {
            $currentTarget = $aEvent->_getNodeStack()->current();

            switch ($aEvent->eventPhase) {
                case Event::CAPTURING_PHASE:
                    $aEvent->_getNodeStack()->next();

                    $currentTarget->dispatchEvent($aEvent);

                    if ($currentTarget == $aEvent->target) {
                        $aEvent->_updateEventPhase(Event::BUBBLING_PHASE);
                        $aEvent->_getNodeStack()->prev();
                        $currentTarget->dispatchEvent($aEvent);
                    }

                    break;

                case Event::BUBBLING_PHASE:
                    $aEvent->_getNodeStack()->prev();
                    $currentTarget->dispatchEvent($aEvent);
            }
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
    public function insertBefore(Node $aNewNode, Node $aRefNode) {
        if (!isset($aRefNode) || is_null($aRefNode)) {
            return $this->appendChild($aNewNode);
        }

        $index = array_search($aRefNode, $this->mChildNodes);

        if ($index === false) {
            throw new DOMException("NotFoundError: Node was not found");
        }

        if (!is_null($aNewNode->mParentNode)) {
            $aNewNode->mParentNode->removeChild($aNewNode);
        }

        array_splice($this->mChildNodes, $index, 0, array($aNewNode));

        if ($index == 0) {
            $this->mFirstChild = $aNewNode;
        }

        $aNewNode->mPreviousSibling = $aRefNode->mPreviousSibling;
        $aNewNode->mNextSibling = $aRefNode;
        $aNewNode->mParentNode = $this;
        $aNewNode->mParentElement = $this->mNodeType == Node::ELEMENT_NODE ? $this : null;
        $aRefNode->mPreviousSibling = $aNewNode;

        return $aNewNode;
    }

    /**
     * Compares two nodes to see if they are equal.
     * @param  Node    $aNode The node you want to compare the current node to.
     * @return boolean        Returns true if the two nodes are the same, otherwise false.
     */
    public function isEqualNode(Node $aNode) {
        return $this == $aNode;
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
        $index = array_search($aNode, $this->mChildNodes);

        if ($index === false) {
            throw new DOMException("NotFoundError: Node was not found");
        }

        array_splice($this->mChildNodes, $index, 1);

        if ($this->mFirstChild == $aNode) {
            $this->mFirstChild = $aNode->mNextSibling;
        }

        if ($this->mLastChild == $aNode) {
            $this->mLastChild = $aNode->mPreviousSibling;
        }

        if (!is_null($aNode->mPreviousSibling)) {
            $aNode->mPreviousSibling->mNextSibling = $aNode->mNextSibling;
        }

        if (!is_null($aNode->mNextSibling)) {
            $aNode->mNextSibling->mPreviousSibling = $aNode->mPreviousSibling;
        }

        $aNode->mPreviousSibling = null;
        $aNode->mNextSibling = null;
        $aNode->mParentElement = null;
        $aNode->mParentNode = null;

        return $aNode;
    }

    /**
     * Unregisters a callback for a specified event on the current node.
     * @param string            $aEventName The name of the event to listen for.
     * @param callable|object   $aCallback  A callback that will be executed when the event occurs.  If an object is
     *                                      given, it will use the handleEvent method on the object as the callback if
     *                                      it exists.
     * @param boolean           $aCapture   Optional. Specifies whether or not the event should be handled during
     *                                      the capturing or bubbling phase.
     */
    public function removeEventListener($aEventName, $aCallback, $aCapture = false) {
        if (array_key_exists($aEventName, $this->mEvents)) {
            $useCapture = $aCapture ? Event::CAPTURING_PHASE : Event::BUBBLING_PHASE;

            if (is_object($aCallback)) {
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
    public function replaceChild($aNewNode, $aOldNode) {
        if (!is_null($aNewNode->mParentNode)) {
            $aNewNode->mParentNode->removeChild($aNewNode);
        }

        $index = array_search($aOldNode, $this->mChildNodes);

        if ($index === false) {
            throw new DOMException("NotFoundError: Node was not found");
        }

        array_splice($this->mChildNodes, $index, 1, array($aNewNode));

        return $aOldNode;
    }
}