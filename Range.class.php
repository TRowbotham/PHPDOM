<?php
namespace phpjs;

require_once 'Exceptions.class.php';

/**
 * Represents a sequence of content within a node tree.
 *
 * @link https://dom.spec.whatwg.org/#range
 * @link https://developer.mozilla.org/en-US/docs/Web/API/Range
 *
 * @property-read boolean   $collapsed          Returns true if the range's starting and ending points are at the same position,
 *                                              otherwise false.
 *
 * @property-read Node      $commonAncestor     Returns the deepest node in the node tree that contains both the start and end
 *                                              nodes.
 *
 * @property-read Node      $endContainer       Returns the node where the range ends.
 *
 * @property-read int       $endOffset          Returns the a number representing where in the endContainer the range ends.
 *
 * @property-read Node      $startContainer     Returns the node where the range begins.
 *
 * @property-read int       $startOffset        Returns a number representing where within the startContainer the range begins.
 */
class Range {
    const START_TO_START = 0;
    const START_TO_END = 1;
    const END_TO_END = 2;
    const END_TO_START = 3;

    private static $mCollection = array();

    private $mEndContainer;
    private $mEndOffset;
    private $mStartContainer;
    private $mStartOffset;

    public function __construct() {
        self::$mCollection[] = $this;
        $this->mEndContainer = Document::_getDefaultDocument();
        $this->mEndOffset = 0;
        $this->mStartContainer = Document::_getDefaultDocument();
        $this->mStartOffset = 0;
    }

    public function __get($aName) {
        switch ($aName) {
            case 'collapsed':
                return $this->mStartContainer === $this->mEndContainer &&
                        $this->mStartOffset == $this->mEndOffset;

            case 'commonAncestorContainer':
                return Node::_getCommonAncestor($this->mStartContainer, $this->mEndContainer);

            case 'endContainer':
                return $this->mEndContainer;

            case 'endOffset':
                return $this->mEndOffset;

            case 'startContainer':
                return $this->mStartContainer;

            case 'startOffset':
                return $this->mStartOffset;
        }
    }

    public function cloneContents() {
        $ownerDocument = $this->mStartContainer instanceof Document ? $this->mStartContainer : $this->mStartContainer->ownerDocument;
        $fragment = $ownerDocument->createDocumentFragment();

        if ($this->mStartContainer === $this->mEndContainer &&
            $this->mStartOffset == $this->mEndOffset) {
            return $fragment;
        }

        $originalStartNode = $this->mStartContainer;
        $originalStartOffset = $this->mStartOffset;
        $originalEndNode = $this->mEndNode;
        $originalEndOffset = $this->mEndOffset;

        if ($originalStartNode === $originalEndNode &&
            ($originalStartNode instanceof Text ||
            $originalStartNode instanceof ProcessingInstruction ||
            $originalStartNode instanceof Comment)) {
            $clone = $originalStartNode->cloneNode();
            $clone->data = $originalStartNode->substringData($originalStartOffset, $originalEndOffset - $originalStartOffset);
            $fragment->appendChild($clone);

            return $fragment;
        }

        $commonAncestor = Node::_getCommonAncestor($originalStartNode, $originalEndNode);
        $firstPartiallyContainedChild = null;

        if (!$originalStartNode->contains($originalEndNode)) {
            foreach ($commonAncestor->childNodes as $node) {
                if ($this->isPartiallyContainedNode($node)) {
                    $firstPartiallyContainedChild = $node;
                    break;
                }
            }
        }

        $lastPartiallyContainedChild = null;

        if (!$originalEndNode->contains($originalStartNode)) {
            foreach (array_reverse($commonAncestor->childNodes) as $node) {
                if ($this->isPartiallyContainedNode($node)) {
                    $lastPartiallyContainedChild = $node;
                    break;
                }
            }
        }

        $containedChildren = array();
        $node = $firstPartiallyContainedChild;

        while ($node) {
            $containedChildren[] = $node;

            if ($node instanceof DocumentType) {
                throw new HierarchyRequestError;
            }

            if ($node === $lastPartiallyContainedChild) {
                break;
            }

            $node = $node->nextSibling;
        }

        if ($firstPartiallyContainedChild instanceof Text ||
            $firstPartiallyContainedChild instanceof ProcessingInstruction ||
            $firstPartiallyContainedChild instanceof Comment) {
            $clone = $originalStartNode->cloneNode();
            $clone->data = $originalStartNode->substringData($originalStartOffset, $originalStartNode->length - $originalStartOffset);
            $fragment->appendChild($clone);
        } else {
            $clone = $firstPartiallyContainedChild->cloneNode();
            $fragment->appendChild($clone);
            $subrange = new Range();
            $subrange->setStart($originalStartNode, $originalStartOffset);
            $subrange->setEnd($firstPartiallyContainedChild, $firstPartiallyContainedChild->_getNodeLength());
            $subfragment = $subrange->cloneRange();
            $clone->appendChild($subfragment);
        }

        foreach ($containedChildren as $child) {
            $clone = $child->cloneNode(true);
            $fragment->appendChild($clone);
        }

        if ($lastPartiallyContainedChild instanceof Text ||
            $lastPartiallyContainedChild instanceof ProcessingInstruction ||
            $lastPartiallyContainedChild instanceof Comment) {
            $clone = $originalEndNode->cloneNode();
            $clone->data = $originalEndNode->substringData(0, $originalEndOffset);
            $fragment->appendChild($clone);
        } else if ($lastPartiallyContainedChild) {
            $clone = $lastPartiallyContainedChild->cloneNode();
            $fragment->appendChild($clone);
            $subrange = new Range();
            $subrange->setStart($lastPartiallyContainedChild, 0);
            $subrange->setEnd($originalEndNode, $originalEndOffset);
            $subfragment = $subrange->cloneRange();
            $clone->appendChild($subfragment);
        }

        return $fragment;
    }

    /**
     * Returns a new Range that has identical starting and ending nodes
     * as well as identical starting and ending offsets.
     *
     * @link https://dom.spec.whatwg.org/#dom-range-clonerange
     *
     * @return Range
     */
    public function cloneRange() {
        $range = new Range();
        $range->setStart($this->mStartContainer, $this->mStartOffset);
        $range->setEnd($this->mEndContainer, $this->mEndOffset);

        return $range;
    }

    /**
     * Collapses the Range to one of its boundary points.
     *
     * @link https://dom.spec.whatwg.org/#dom-range-collapse
     *
     * @param bool $aToStart Optional.  If true is passed, the Range will collapse on its starting boundary,
     *                       otherwise it will collapse on its ending boundary.
     */
    public function collapse($aToStart = false) {
        if ($aToStart) {
            $this->mEndContainer = $this->mStartContainer;
            $this->mEndOffset = $this->mStartOffset;
        } else {
            $this->mStartContainer = $this->mEndContainer;
            $this->mStartOffset = $this->mEndOffset;
        }
    }

    /**
     * Compares the boundary points of this Range with another Range.
     *
     * @link   https://dom.spec.whatwg.org/#dom-range-compareboundarypointshow-sourcerange
     *
     * @param  int    $aHow         A constant describing how the two Ranges should be compared.  Possible values:
     *                                  Range::END_TO_END       - Compares the end boundary points of both Ranges.
     *                                  Range::END_TO_START     - Compares the end boudary point of $aSourceRange to
     *                                                            the start boundary point of this Range.
     *                                  Range::START_TO_END     - Compares the start boundary point of $aSourceRange to
     *                                                            the end boundary of this Range.
     *                                  Range::START_TO_START   - Compares the start boundary point of $aSourceRange to
     *                                                            the start boundary of this Range.
     *
     * @param  Range  $aSourceRange A Range whose boundary points are to be compared.
     *
     * @return int                  Returns -1, 0, or 1 indicating wether the Range's boundary points are before, equal,
     *                              or after $aSourceRange's boundary points, respectively.
     *
     * @throws WrongDocumentError
     */
    public function compareBoundaryPoints($aHow, Range $aSourceRange) {
        if ($aHow < self::START_TO_START || $aHow > self::END_TO_START) {
            throw new NotSupportedError;
        }

        if (Node::_getRootElement($this->mStartContainer) !== Node::_getRootElement($aSourceRange->startContainer)) {
            throw new WrongDocumentError;
        }

        switch ($aHow) {
            case self::START_TO_START:
                $thisPoint = array($this->mStartContainer, $this->mStartOffset);
                $otherPoint = array($aSourceRange->startContainer, $aSourceRange->startOffset);

                break;

            case self::START_TO_END:
                $thisPoint = array($this->mEndContainer, $this->mEndOffset);
                $otherPoint = array($aSourceRange->startContainer, $aSourceRange->startOffset);

                break;

            case self::END_TO_END:
                $thisPoint = array($this->mEndContainer, $this->mEndOffset);
                $otherPoint = array($aSourceRange->endContainer, $aSourceRange->endOffset);

                break;

            case self::END_TO_START:
                $thisPoint = array($this->mStartContainer, $this->mStartOffset);
                $otherPoint = array($aSourceRange->endContainer, $aSourceRange->endOffset);

                break;
        }

        switch ($this->computePosition($thisPoint, $otherPoint)) {
            case 'before':
                return -1;

            case 'equal':
                return 0;

            case 'after':
                return 1;
        }
    }

    /**
     * Checks to see if a node comes before, after, or within the range.
     *
     * @link https://dom.spec.whatwg.org/#dom-range-comparepoint
     *
     * @param  Node   $aNode   The node to compare with.
     *
     * @param  int    $aOffset The offset position within the node.
     *
     * @return int             Returns -1, 0, or 1 to indicated whether the node lies before, after, or
     *                         within the range, respectively.
     *
     * @throws WrongDocumentError
     *
     * @throws InvalidNodeTypeError
     *
     * @throws IndexSizeError
     */
    public function comparePoint(Node $aNode, $aOffset) {
        if (Node::_getRootElement($aNode) !== Node::_getRootElement($this->mStartContainer)) {
            throw new WrongDocumentError;
        }

        if ($aNode instanceof DocumentType) {
            throw new InvalidNodeTypeError;
        }

        if ($aOffset > $aNode->_getNodeLength()) {
            throw new IndexSizeError;
        }

        $bp = array($aNode, $aOffset);

        if ($this->computePosition($bp, array($this->mStartContainer, $this->mStartOffset)) == 'before') {
            return -1;
        }

        if ($this->computePosition($bp, array($this->mEndContainer, $this->mEndOffset)) == 'after') {
            return 1;
        }

        return 0;
    }

    /**
     * Removes the contents of the Range from the Document.
     *
     * @link https://dom.spec.whatwg.org/#dom-range-deletecontents
     */
    public function deleteContents() {
        if ($this->mStartContainer === $this->mEndContainer &&
            $this->mStartOffset == $this->mEndOffset) {
            return;
        }

        $originalStartNode = $this->mStartContainer;
        $originalStartOffset = $this->mStartOffset;
        $originalEndNode = $this->mEndContainer;
        $originalEndOffset = $this->mEndOffset;

        if ($originalStartNode === $originalEndNode && ($originalStartNode instanceof Text ||
                $originalStartNode instanceof ProcessingInstruction ||
                $originalStartNode instanceof Comment)) {
            $originalStartNode->replaceData($originalStartOffset, $originalEndOffset - $originalStartOffset, '');
        }

        $nodesToRemove = array();
        $tw = new TreeWalker($originalStartNode, NodeFilter::SHOW_ALL, function($aNode) {
            return $this->isFullyContainedNode($aNode) && !$this->isFullyContainedNode($aNode->parentNode) ?
                    NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
        });

        while ($node = $tw->nextNode()) {
            $nodesToRemove[] = $node;
        }

        if ($originalStartNode->contains($originalEndNode)) {
            $newNode = $originalStartNode;
            $newOffset = $originalStartOffset;
        } else {
            $referenceNode = $originalStartNode;

            while ($referenceNode) {
                if ($referenceNode->contains($originalEndNode)) {
                    break;
                }

                $referenceNode = $referenceNode->parentNode;
            }

            $newNode = $referenceNode->parentNode;
            $newOffset = $referenceNode->_getTreeIndex() + 1;
        }

        if ($originalStartNode instanceof Text ||
            $originalStartNode instanceof ProcessingInstruction ||
            $originalStartNode instanceof Comment) {
            $originalStartNode->replaceData($originalStartOffset, $originalStartNode->length - $originalStartOffset, '');
        }

        foreach ($nodesToRemove as $node) {
            $node->parentNode->_removeChild($node);
        }

        if ($originalEndNode instanceof Text ||
            $originalEndNode instanceof ProcessingInstruction ||
            $originalEndNode instanceof Comment) {
            $originalEndNode->replaceData(0, $originalEndOffset, '');
        }

        $this->setStartOrEnd('start', $newNode, $newOffset);
        $this->setStartOrEnd('end', $newNode, $newOffset);
    }

    /**
     * Extracts the content of the Range from the node tree and places it in a DocumentFragment.
     *
     * @link https://dom.spec.whatwg.org/#dom-range-extractcontents
     *
     * @return DocumentFragment
     */
    public function extractContents() {
        $fragment = $this->mStartContainer->ownerDocument->createDocumentFragment();

        if ($this->mStartContainer === $this->mEndContainer && $this->mStartOffset == $this->mEndOffset) {
            return $fragment;
        }

        $startNode = $this->mStartContainer;
        $startOffset = $this->mStartOffset;
        $endNode = $this->mEndContainer;
        $endOffset = $this->mEndOffset;

        if ($startNode === $endNode &&
            ($startNode instanceof Text || $startNode instanceof ProcessingInstruction || $startNode instanceof Comment)) {
            $clone = $startNode->cloneNode();
            $clone->data = $startNode->substringData($startOffset, $endOffset - $startOffset);
            $fragment->appendChild($clone);
            $startNode->replaceData($startOffset, $endOffset - $startOffset, '');

            return $fragment;
        }

        // TODO: Finish
    }

    /**
     * Inserts a new Node into at the start of the Range.
     *
     * @link https://dom.spec.whatwg.org/#dom-range-insertnode
     *
     * @param  Node   $aNode The Node to be inserted.
     *
     * @throws HierarchyRequestError
     */
    public function insertNode(Node $aNode) {
        if (($this->mStartContainer instanceof ProcessingInstruction || $this->mStartContainer instanceof Comment) ||
            ($this->mStartContainer instanceof Text && $this->mStartContainer->parentNode === null)) {
            throw new HierarchyRequestError;
        }

        $referenceNode = null;

        if ($this->mStartContainer instanceof Text) {
            $referenceNode = $this->mStartContainer;
        } else {
            if (isset($this->mStartContainer->childNodes[$this->mStartOffset])) {
                $referenceNode = $this->mStartContainer->childNodes[$this->mStartOffset];
            } else {
                $referenceNode = null;
            }
        }

        $parent = !$referenceNode ? $this->mStartContainer : $referenceNode->parentNode;
        $parent->_ensurePreinsertionValidity($aNode, $referenceNode);

        if ($this->mStartContainer instanceof Text) {
            $this->mStartContainer->splitText($this->mStartOffset);
        }

        if ($aNode === $referenceNode) {
            $referenceNode = $referenceNode->nextSibling;
        }

        if (!$aNode->parentNode) {
            $aNode->parentNode->removeChild($aNode);
        }

        $newOffset = !$referenceNode ? $parent->_getNodeLength() : $referenceNode->_getTreeIndex();
        $newOffset += $aNode instanceof DocumentFragment ? $aNode->_getNodeLength() : 1;

        $parent->_preinsertNodeBeforeChild($aNode, $referenceNode);

        if ($this->mStartContainer === $this->mEndContainer &&
            $this->mStartOffset == $this->mEndOffset) {
            $this->mEndContainer = $parent;
            $this->mEndOffset = $newOffset;
        }
    }

    /**
     * Returns a boolean indicating whether or not the given Node intersects the Range.
     *
     * @link https://dom.spec.whatwg.org/#dom-range-intersectsnode
     *
     * @param  Node   $aNode The Node to be checked for intersection.
     *
     * @return bool
     */
    public function intersectsNode(Node $aNode) {
        if (Node::_getRootElement($aNode) !== Node::_getRootElement($this->mStartContainer)) {
            return false;
        }

        $parent = $aNode->parentNode;

        if (!$parent) {
            return true;
        }

        $offset = $aNode->_getTreeIndex();
        $bp = array($parent, $offset);

        if ($this->computePosition($bp, array($this->mEndContainer, $this->mEndOffset)) == 'before' &&
            $this->computePosition($bp, array($this->mStartContainer, $this->mStartOffset + 1)) == 'after') {
            return true;
        }

        return false;
    }

    /**
     * Returns a boolean indicating whether the given point is within the Range.
     *
     * @link https://dom.spec.whatwg.org/#dom-range-ispointinrange
     *
     * @param  Node    $aNode   The Node whose position is to be checked.
     *
     * @param  int     $aOffset The offset within the given node.
     *
     * @return bool
     *
     * @throws InvalidNodeTypeError
     *
     * @throws IndexSizeError
     */
    public function isPointInRange(Node $aNode, $aOffset) {
        if (Node::_getRootElement($aNode) !== Node::_getRootElement($this->mStartContainer)) {
            return false;
        }

        if ($aNode instanceof DocumentType) {
            throw new InvalidNodeTypeError;
        }

        if ($aOffset > $aNode->_getNodeLength()) {
            throw new IndexSizeError;
        }

        $bp = array($aNode, $aOffset);

        if ($this->computePosition($bp, array($this->mStartContainer, $this->mStartOffset)) == 'before' ||
            $this->computePosition($bp, array($this->mEndContainer, $this->mEndOffset)) == 'after') {
            return false;
        }

        return true;
    }

    /**
     * Selects the given Node and its contents.
     *
     * @link   https://dom.spec.whatwg.org/#concept-range-select
     *
     * @param  Node   $aNode The node and its contents to be selected.
     *
     * @throws InvalidNodeTypeError
     */
    public function selectNode(Node $aNode) {
        $parent = $aNode->parentNode;

        if (!$parent) {
            throw new InvalidNodeTypeError;
        }

        $index = $aNode->_getTreeIndex();

        $this->mStartContainer = $parent;
        $this->mStartOffset = $index;
        $this->mEndContainer = $parent;
        $this->mEndOffset = $index + 1;
    }

    /**
     * Selects the contents of the given Node.
     *
     * @link https://dom.spec.whatwg.org/#dom-range-selectnodecontents
     *
     * @param  Node   $aNode The Node whose content is to be selected.
     *
     * @throws InvalidNodeTypeError
     */
    public function selectNodeContents(Node $aNode) {
        if ($aNode instanceof DocumentType) {
            throw new InvalidNodeTypeError;
        }

        $this->mStartContainer = $aNode;
        $this->mStartOffset = 0;
        $this->mEndContainer = $aNode;
        $this->mEndOffset = $aNode->_getNodeLength();
    }

    /**
     * Sets the Range's end boundary.
     *
     * @link https://dom.spec.whatwg.org/#dom-range-setend
     *
     * @param Node   $aNode   The Node where the Range ends.
     *
     * @param int    $aOffset The offset within the given node where the Range ends.
     */
    public function setEnd(Node $aNode, $aOffset) {
        $this->setStartOrEnd('end', $aNode, $aOffset);
    }

    /**
     * Sets the Range's end boundary relative to the given Node.
     *
     * @link https://dom.spec.whatwg.org/#dom-range-setendafter
     *
     * @param Node $aNode The Node where the Range will end.
     *
     * @throws InvalidNodeTypeError
     */
    public function setEndAfter(Node $aNode) {
        $parent = $aNode->parentNode;

        if (!$parent) {
            throw new InvalidNodeTypeError;
        }

        $this->setStartOrEnd('end', $parent, $aNode->_getTreeIndex() + 1);
    }

    /**
     * Sets the Range's end boundary relative to the given Node.
     *
     * @link https://dom.spec.whatwg.org/#dom-range-setendbefore
     *
     * @param Node $aNode The Node where the Range will end.
     *
     * @throws InvalidNodeTypeError
     */
    public function setEndBefore(Node $aNode) {
        $parent = $aNode->parentNode;

        if (!$parent) {
            throw new InvalidNodeTypeError;
        }

        $this->setStartOrEnd('end', $parent, $aNode->_getTreeIndex());
    }

    /**
     * Sets the Range's start boundary relative to the given Node.
     *
     * @link https://dom.spec.whatwg.org/#dom-range-setstart
     *
     * @param Node $aNode The Node where the Range will start.
     *
     * @param int  $aOffset The offset within the given node where the Range starts.
     */
    public function setStart(Node $aNode, $aOffset) {
        $this->setStartOrEnd('start', $aNode, $aOffset);
    }

    /**
     * Sets the Range's start boundary relative to the given Node.
     *
     * @link https://dom.spec.whatwg.org/#dom-range-setstartafter
     *
     * @param Node $aNode The Node where the Range will start.
     *
     * @throws InvalidNodeTypeError
     */
    public function setStartAfter(Node $aNode) {
        $parent = $aNode->parentNode;

        if (!$parent) {
            throw new InvalidNodeTypeError;
        }

        $this->setStartOrEnd('start', $parent, $aNode->_getTreeIndex() + 1);
    }

    /**
     * Sets the Range's start boundary relative to the given Node.
     *
     * @link https://dom.spec.whatwg.org/#dom-range-setstartbefore
     *
     * @param Node $aNode The Node where the Range will start.
     *
     * @throws InvalidNodeTypeError
     */
    public function setStartBefore(Node $aNode) {
        $parent = $aNode->parentNode;

        if (!$parent) {
            throw new InvalidNodeTypeError;
        }

        $this->setStartOrEnd('start', $parent, $aNode->_getTreeIndex());
    }

    /**
     * Wraps the content of Range in a new Node and inserts it in to the Document.
     *
     * @link https://dom.spec.whatwg.org/#dom-range-surroundcontents
     *
     * @param  Node   $aNewParent The node that will surround the Range's content.
     */
    public function surroundCountents(Node $aNewParent) {
        if ((!($this->mStartContainer instanceof Text) && $this->isPartiallyContainedNode($this->mStartContainer)) ||
            (!($this->mEndContainer instanceof Text) && $this->isPartiallyContainedNode($this->mEndContainer))) {
            throw new InvalidStateError;
        }

        if ($aNewParent instanceof Document || $aNewParent instanceof DocumentType ||
            $aNewParent instanceof DocumentFragment) {
            throw new InvalidNodeTypeError;
        }

        $fragment = $this->extractContents();

        if ($aNewParent->hasChildNodes()) {
            $aNewParent->_replaceAll(null);
        }

        $this->insertNode($aNewParent);
        $aNewParent->appendChild($fragment);
        $this->selectNode($aNewParent);
    }

    /**
     * Concatenates the contents of the Range in to a string.
     *
     * @link https://dom.spec.whatwg.org/#dom-Range-toString
     *
     * @return string
     */
    public function toString() {
        $s = '';

        if ($this->mStartContainer === $this->mEndContainer && $this->mStartContainer instanceof Text) {
            return substr($this->mStartContainer->data, $this->mStartOffset, $this->mEndOffset);
        }

        if ($this->mStartContainer instanceof Text) {
            $s .= substr($this->mStartContainer->data, $this->mStartOffset);
        }

        $tw = new TreeWalker(Node::_getRootElement($this->mStartContainer), NodeFilter::SHOW_TEXT, function($aNode) {
            return $this->isFullyContainedNode($aNode) ? NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_REJECT;
        });
        $tw->currentNode = $this->mStartContainer;

        while ($text = $tw->nextNode()) {
            $s .= $text->data;
        }

        if ($this->mEndContainer instanceof Text) {
            $s .= substr($this->mEndContainer->data, 0, $this->mEndOffset);
        }

        return $s;
    }

    /**
     * Returns a collection of all Ranges.
     *
     * @internal
     *
     * @return Range[]
     */
    public static function _getRangeCollection() {
        return self::$mCollection;
    }

    /**
     * Compares the position of two boundary points.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-range-bp-position
     *
     * @param  mixed[] $aA An array containing a Node and an offset within that Node representing a boundary.
     *
     * @param  mixed[] $aB An array containing a Node and an offset within that Node representing a boundary.
     *
     * @return int         Returns before, equal, or after based on the position of the first boundary relative
     *                     to the second boundary.
     */
    private function computePosition($aA, $aB) {
        if ($aA[0] === $aB[0]) {
            if ($aA[1] == $aB[1]) {
                return 'equal';
            } elseif ($aA[1] < $aB[1]) {
                return 'before';
            } else {
                return 'after';
            }
        }

        $tw = new TreeWalker(Node::_getRootElement($aB[0]), NodeFilter::SHOW_ALL, function($aNode) use ($aA) {
            return $aNode === $aA[0] ? NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
        });
        $tw->currentNode = $aB[0];

        $AFollowsB = $tw->nextNode();

        if ($AFollowsB) {
            switch ($this->computePosition($aB, $aA)) {
                case 'after':
                    return 'before';
                case 'before':
                    return 'after';
            }
        }

        $ancestor = $aB[0]->parentNode;

        while ($ancestor) {
            if ($ancestor === $aA[0]) {
                break;
            }

            $ancestor = $ancestor->parentNode;
        }

        if ($ancestor) {
            $child = $aB[0];

            while ($child) {
                if (in_array($child, $aA[0]->childNodes, true)) {
                    break;
                }

                $child = $child->parentNode;
            }

            if ($child->_getTreeIndex() < $aA[1]) {
                return 'after';
            }
        }

        return 'before';
    }

    /**
     * Returns true if the entire Node is within the Range, otherwise false.
     *
     * @link https://dom.spec.whatwg.org/#contained
     *
     * @param  Node    $aNode The Node to check against.
     *
     * @return bool
     */
    private function isFullyContainedNode(Node $aNode) {
        $startBP = array($this->mStartContainer, $this->mStartOffset);
        $endBP = array($this->mEndContainer, $this->mEndOffset);

        return Node::_getRootElement($aNode) === Node::_getRootElement($this->mStartContainer) &&
                $this->computePosition(array($aNode, 0), $startBP) == 'after' &&
                $this->computePosition(array($aNode, $aNode->_getNodeLength()), $endBP) == 'before';
    }

    /**
     * Returns true if only a portion of the Node is contained within the Range.
     *
     * @link https://dom.spec.whatwg.org/#partially-contained
     *
     * @param  Node    $aNode The Node to check against.
     *
     * @return bool
     */
    private function isPartiallyContainedNode(Node $aNode) {
        $isAncestorOfStart = $aNode->contains($this->mStartContainer);
        $isAncestorOfEnd = $aNode->contains($this->mEndContainer);

        return ($isAncestorOfStart && !$isAncestorOfEnd) || (!$isAncestorOfStart && $isAncestorOfEnd);
    }

    /**
     * Sets the start or end boundary point for the Range.
     *
     * @internal
     *
     * @link  https://dom.spec.whatwg.org/#concept-range-bp-set
     *
     * @param string    $aType   Which boundary point should be set.  Valid values are start or end.
     *
     * @param Node      $aNode   The Node that will become the boundary.
     *
     * @param int       $aOffset The offset within the given Node that will be the boundary.
     *
     * @throws InvalidNodeTypeError
     *
     * @throws IndexSizeError
     */
    private function setStartOrEnd($aType, $aNode, $aOffset) {
        if ($aNode instanceof DocumentType) {
            throw new InvalidNodeTypeError;
        }

        if ($aOffset > $aNode->_getNodeLength()) {
            throw new IndexSizeError;
        }

        $bp = array($aNode, $aOffset);

        switch ($aType) {
            case 'start':
                if ($this->computePosition($bp, array($this->mEndContainer, $this->mEndOffset)) == 'after' ||
                    Node::_getRootElement($this->mStartContainer) !== Node::_getRootElement($aNode)) {
                    $this->mEndContainer = $aNode;
                    $this->mEndOffset = $aOffset;
                }

                $this->mStartContainer = $aNode;
                $this->mStartOffset = $aOffset;

                break;

            case 'end':
                if ($this->computePosition($bp, array($this->mStartContainer, $this->mStartOffset)) == 'before' ||
                    Node::_getRootElement($this->mStartContainer) !== Node::_getRootElement($aNode)) {
                    $this->mStartContainer = $aNode;
                    $this->mStartOffset = $aOffset;
                }

                $this->mEndContainer = $aNode;
                $this->mEndOffset = $aOffset;
        }
    }
}
