<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Exception\InvalidNodeTypeError;
use Rowbot\DOM\Exception\NotSupportedError;
use Rowbot\DOM\Exception\WrongDocumentError;
use Rowbot\DOM\Parser\ParserFactory;

/**
 * Represents a sequence of content within a node tree.
 *
 * @see https://dom.spec.whatwg.org/#range
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Range
 *
 * @property-read boolean $collapsed Returns true if the range's starting and
 *     ending points are at the same position, otherwise false.
 *
 * @property-read Node $commonAncestor Returns the deepest node in the node tree
 *     that contains both the start and end nodes.
 *
 * @property-read Node $endContainer Returns the node where the range ends.
 *
 * @property-read int $endOffset Returns the a number representing where in the
 *     endContainer the range ends.
 *
 * @property-read Node $startContainer Returns the node where the range begins.
 *
 * @property-read int $startOffset Returns a number representing where within
 *     the startContainer the range begins.
 */
class Range
{
    const START_TO_START = 0;
    const START_TO_END = 1;
    const END_TO_END = 2;
    const END_TO_START = 3;

    private static $collection = [];

    private $endContainer;
    private $endOffset;
    private $startContainer;
    private $startOffset;

    public function __construct()
    {
        self::$collection[] = $this;
        $this->endContainer = Document::getDefaultDocument();
        $this->endOffset = 0;
        $this->startContainer = Document::getDefaultDocument();
        $this->startOffset = 0;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'collapsed':
                return $this->startContainer === $this->endContainer
                        && $this->startOffset == $this->endOffset;

            case 'commonAncestorContainer':
                return Node::getCommonAncestor(
                    $this->startContainer,
                    $this->endContainer
                );

            case 'endContainer':
                return $this->endContainer;

            case 'endOffset':
                return $this->endOffset;

            case 'startContainer':
                return $this->startContainer;

            case 'startOffset':
                return $this->startOffset;
        }
    }

    public function cloneContents()
    {
        $nodeDocument = $this->startContainer->getNodeDocument();
        $fragment = $nodeDocument->createDocumentFragment();

        if ($this->startContainer === $this->endContainer
            && $this->startOffset == $this->endOffset
        ) {
            return $fragment;
        }

        $originalStartNode = $this->startContainer;
        $originalStartOffset = $this->startOffset;
        $originalEndNode = $this->endContainer;
        $originalEndOffset = $this->endOffset;

        if ($originalStartNode === $originalEndNode
            && ($originalStartNode instanceof Text
                || $originalStartNode instanceof ProcessingInstruction
                || $originalStartNode instanceof Comment)
        ) {
            $clone = $originalStartNode->doCloneNode();
            $clone->data = $originalStartNode->substringData(
                $originalStartOffset,
                $originalEndOffset - $originalStartOffset
            );
            $fragment->appendChild($clone);

            return $fragment;
        }

        $commonAncestor = Node::getCommonAncestor(
            $originalStartNode,
            $originalEndNode
        );
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
            $childNodes = $commonAncestor
                ->childNodes
                ->getIterator()
                ->getArrayCopy();

            foreach (array_reverse($childNodes) as $node) {
                if ($this->isPartiallyContainedNode($node)) {
                    $lastPartiallyContainedChild = $node;
                    break;
                }
            }
        }

        $containedChildren = [];

        foreach ($commonAncestor->childNodes as $child) {
            if ($this->isFullyContainedNode($child)) {
                if ($child instanceof DocumentType) {
                    throw new HierarchyRequestError();
                }

                $containedChildren[] = $child;
            }
        }

        if ($firstPartiallyContainedChild instanceof Text
            || $firstPartiallyContainedChild instanceof ProcessingInstruction
            || $firstPartiallyContainedChild instanceof Comment
        ) {
            $clone = $originalStartNode->doCloneNode();
            $clone->data = $originalStartNode->substringData(
                $originalStartOffset,
                $originalStartNode->length - $originalStartOffset
            );
            $fragment->appendChild($clone);
        } elseif ($firstPartiallyContainedChild) {
            $clone = $firstPartiallyContainedChild->doCloneNode();
            $fragment->appendChild($clone);
            $subrange = new Range();
            $subrange->setStart($originalStartNode, $originalStartOffset);
            $subrange->setEnd(
                $firstPartiallyContainedChild,
                $firstPartiallyContainedChild->getLength()
            );
            $subfragment = $subrange->cloneRange();
            $clone->appendChild($subfragment);
        }

        foreach ($containedChildren as $child) {
            $clone = $child->doCloneNode(null, true);
            $fragment->appendChild($clone);
        }

        if ($lastPartiallyContainedChild instanceof Text
            || $lastPartiallyContainedChild instanceof ProcessingInstruction
            || $lastPartiallyContainedChild instanceof Comment
        ) {
            $clone = $originalEndNode->doCloneNode();
            $clone->data = $originalEndNode->substringData(
                0,
                $originalEndOffset
            );
            $fragment->appendChild($clone);
        } elseif ($lastPartiallyContainedChild) {
            $clone = $lastPartiallyContainedChild->doCloneNode();
            $fragment->appendChild($clone);
            $subrange = new Range();
            $subrange->setStart($lastPartiallyContainedChild, 0);
            $subrange->setEnd($originalEndNode, $originalEndOffset);
            $subfragment = $subrange->cloneContents();
            $clone->appendChild($subfragment);
        }

        return $fragment;
    }

    /**
     * Returns a new Range that has identical starting and ending nodes
     * as well as identical starting and ending offsets.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-clonerange
     *
     * @return Range
     */
    public function cloneRange()
    {
        $range = new Range();
        $range->setStart($this->startContainer, $this->startOffset);
        $range->setEnd($this->endContainer, $this->endOffset);

        return $range;
    }

    /**
     * Collapses the Range to one of its boundary points.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-collapse
     *
     * @param bool $toStart Optional.  If true is passed, the Range will
     *     collapse on its starting boundary, otherwise it will collapse on its
     *     ending boundary.
     */
    public function collapse($toStart = false)
    {
        if ($toStart) {
            $this->endContainer = $this->startContainer;
            $this->endOffset = $this->startOffset;
        } else {
            $this->startContainer = $this->endContainer;
            $this->startOffset = $this->endOffset;
        }
    }

    /**
     * Compares the boundary points of this Range with another Range.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-compareboundarypoints
     *
     * @param int $how A constant describing how the two Ranges should be
     *     compared.  Possible values:
     *
     *     Range::END_TO_END     - Compares the end boundary points of both
     *         Ranges.
     *     Range::END_TO_START   - Compares the end boudary point of
     *         $sourceRange to the start boundary point of this Range.
     *     Range::START_TO_END   - Compares the start boundary point of
     *         $sourceRange to the end boundary of this Range.
     *     Range::START_TO_START - Compares the start boundary point of
     *         $sourceRange to the start boundary of this Range.
     *
     * @param Range $sourceRange A Range whose boundary points are to be
     *     compared.
     *
     * @return int Returns -1, 0, or 1 indicating wether the Range's boundary
     *     points are before, equal, or after $sourceRange's boundary points,
     *     respectively.
     *
     * @throws WrongDocumentError
     */
    public function compareBoundaryPoints($how, Range $sourceRange)
    {
        if ($how < self::START_TO_START || $how > self::END_TO_START) {
            throw new NotSupportedError();
        }

        $sourceRangeRoot = $sourceRange->startContainer->getRootNode();

        if ($this->startContainer->getRootNode() !== $sourceRangeRoot) {
            throw new WrongDocumentError();
        }

        switch ($how) {
            case self::START_TO_START:
                $thisPoint = [$this->startContainer, $this->startOffset];
                $otherPoint = [
                    $sourceRange->startContainer,
                    $sourceRange->startOffset
                ];

                break;

            case self::START_TO_END:
                $thisPoint = [$this->endContainer, $this->endOffset];
                $otherPoint = [
                    $sourceRange->startContainer,
                    $sourceRange->startOffset
                ];

                break;

            case self::END_TO_END:
                $thisPoint = [$this->endContainer, $this->endOffset];
                $otherPoint = [
                    $sourceRange->endContainer,
                    $sourceRange->endOffset
                ];

                break;

            case self::END_TO_START:
                $thisPoint = [$this->startContainer, $this->startOffset];
                $otherPoint = [
                    $sourceRange->endContainer,
                    $sourceRange->endOffset
                ];

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
     * @see https://dom.spec.whatwg.org/#dom-range-comparepoint
     *
     * @param Node $node The node to compare with.
     *
     * @param int $offset The offset position within the node.
     *
     * @return int Returns -1, 0, or 1 to indicated whether the node lies
     *     before, after, or within the range, respectively.
     *
     * @throws WrongDocumentError
     *
     * @throws InvalidNodeTypeError
     *
     * @throws IndexSizeError
     */
    public function comparePoint(Node $node, $offset)
    {
        $root = $this->startContainer->getRootNode();

        if ($node->getRootNode() !== $root) {
            throw new WrongDocumentError();
        }

        if ($node instanceof DocumentType) {
            throw new InvalidNodeTypeError();
        }

        if ($offset > $node->getLength()) {
            throw new IndexSizeError();
        }

        $bp = [$node, $offset];

        if ($this->computePosition($bp, [
            $this->startContainer,
            $this->startOffset
        ]) === 'before') {
            return -1;
        }

        if ($this->computePosition($bp, [
            $this->endContainer,
            $this->endOffset
        ]) === 'after') {
            return 1;
        }

        return 0;
    }

    /**
     * Removes the contents of the Range from the Document.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-deletecontents
     */
    public function deleteContents()
    {
        if ($this->startContainer === $this->endContainer &&
            $this->startOffset == $this->endOffset) {
            return;
        }

        $originalStartNode = $this->startContainer;
        $originalStartOffset = $this->startOffset;
        $originalEndNode = $this->endContainer;
        $originalEndOffset = $this->endOffset;

        if ($originalStartNode === $originalEndNode
            && ($originalStartNode instanceof Text
                || $originalStartNode instanceof ProcessingInstruction
                || $originalStartNode instanceof Comment)
        ) {
            $originalStartNode->doReplaceData(
                $originalStartOffset,
                $originalEndOffset - $originalStartOffset,
                ''
            );
        }

        $nodesToRemove = array();
        $tw = new TreeWalker(
            $originalStartNode,
            NodeFilter::SHOW_ALL,
            function ($node) {
                if ($this->isFullyContainedNode($node)
                    && !$this->isFullyContainedNode($node->parentNode)
                ) {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_SKIP;
            }
        );

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
            $newOffset = $referenceNode->getTreeIndex() + 1;
        }

        if ($originalStartNode instanceof Text
            || $originalStartNode instanceof ProcessingInstruction
            || $originalStartNode instanceof Comment
        ) {
            $originalStartNode->doReplaceData(
                $originalStartOffset,
                $originalStartNode->length - $originalStartOffset,
                ''
            );
        }

        foreach ($nodesToRemove as $node) {
            $node->parentNode->removeNode($node);
        }

        if ($originalEndNode instanceof Text
            || $originalEndNode instanceof ProcessingInstruction
            || $originalEndNode instanceof Comment
        ) {
            $originalEndNode->doReplaceData(0, $originalEndOffset, '');
        }

        $this->setStartOrEnd('start', $newNode, $newOffset);
        $this->setStartOrEnd('end', $newNode, $newOffset);
    }

    /**
     * Extracts the content of the Range from the node tree and places it in a
     * DocumentFragment.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-extractcontents
     *
     * @return DocumentFragment
     */
    public function extractContents()
    {
        $fragment = $this->startContainer->getNodeDocument()
            ->createDocumentFragment();

        if ($this->startContainer === $this->endContainer
            && $this->startOffset == $this->endOffset
        ) {
            return $fragment;
        }

        $originalStartNode = $this->startContainer;
        $originalStartOffset = $this->startOffset;
        $originalEndNode = $this->endContainer;
        $originalEndOffset = $this->endOffset;

        if ($originalStartNode === $originalEndNode
            && ($originalStartNode instanceof Text
                || $originalStartNode instanceof ProcessingInstruction
                || $originalStartNode instanceof Comment)
        ) {
            $clone = $originalStartNode->doCloneNode();
            $clone->data = $originalStartNode->substringData(
                $originalStartOffset,
                $originalEndOffset - $originalStartOffset
            );
            $fragment->appendChild($clone);
            $originalStartNode->doReplaceData(
                $originalStartOffset,
                $originalEndOffset - $originalStartOffset,
                ''
            );

            return $fragment;
        }

        $commonAncestor = Node::getCommonAncestor(
            $originalStartNode,
            $originalEndNode
        );
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

        $tw = new TreeWalker(
            $commonAncestor,
            NodeFilter::SHOW_ALL,
            function ($aNode) {
                if ($this->isFullyContainedNode($aNode)) {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_SKIP;
            }
        );
        $containsDocType = false;
        $containedChildren = [];

        while (($node = $tw->nextNode())) {
            if (!$containsDocType && $node instanceof DocumentType) {
                $containsDocType = true;
            }

            $containedChildren[] = $node;
        }

        if ($containsDocType) {
            throw new HierarchyRequestError();
        }

        if ($originalStartNode->contains($originalEndNode)) {
            $newNode = $originalStartNode;
            $newOffset = $originalStartOffset;
        } else {
            $referenceNode = $originalStartNode;

            while (true) {
                $parent = $referenceNode->parentNode;

                if (!$parent || $parent->contains($originalEndNode)) {
                    break;
                }

                $referenceNode = $parent;

                // If reference node’s parent is null, it would be the root of
                // range, so would be an inclusive ancestor of original end
                // node, and we could not reach this point.
                $newNode = $referenceNode->parentNode;
                $newOffset = $referenceNode->getTreeIndex();
            }
        }

        if ($firstPartiallyContainedChild instanceof Text
            || $firstPartiallyContainedChild instanceof ProcessingInstruction
            || $firstPartiallyContainedChild instanceof Comment
        ) {
            $clone = $originalStartNode->doCloneNode();
            $clone->data = $originalStartNode->substringData(
                $originalStartOffset,
                $originalStartNode->length - $originalStartOffset
            );
            $fragment->appendChild($clone);
            $originalStartNode->doReplaceData(
                $originalStartOffset,
                $originalStartNode->length - $originalStartOffset,
                ''
            );
        } elseif ($firstPartiallyContainedChild) {
            $clone = $firstPartiallyContainedChild->doCloneNode();
            $fragment->appendChild($clone);
            $subrange = new Range();
            $subrange->startContainer = $originalStartNode;
            $subrange->startOffset = $originalStartOffset;
            $subrange->endContainer = $firstPartiallyContainedChild;
            $subrange->endOffset = $firstPartiallyContainedChild->getLength();
            $subfragment = $subrange->extractContents();
            $clone->appendChild($subfragment);
        }

        foreach ($containedChildren as $child) {
            $fragment->appendChild($child);
        }

        if ($lastPartiallyContainedChild instanceof Text
            || $lastPartiallyContainedChild instanceof ProcessingInstruction
            || $lastPartiallyContainedChild instanceof Comment
        ) {
            // In this case, last partially contained child is original end node
            $clone = $originalEndNode->doCloneNode();
            $clone->data = $originalEndNode->substringData(
                0,
                $originalEndOffset
            );
            $fragment->appendChild($clone);
            $originalEndNode->doReplaceData(
                0,
                $originalEndOffset,
                ''
            );
        } elseif ($lastPartiallyContainedChild) {
            $clone = $lastPartiallyContainedChild->doCloneNode();
            $fragment->appendChild($clone);
            $subrange = new Range();
            $subrange->startContainer = $lastPartiallyContainedChild;
            $subrange->startOffset = 0;
            $subrange->endContainer = $originalEndNode;
            $subrange->endOffset = $originalEndOffset;
            $subfragment = $subrange->extractContents();
            $clone->appendChild($subfragment);
        }

        $this->startContainer = $newNode;
        $this->startOffset = $newOffset;
        $this->endContainer = $newNode;
        $this->endOffset = $newOffset;

        return $fragment;
    }

    /**
     * Inserts a new Node into at the start of the Range.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-insertnode
     *
     * @param Node $node The Node to be inserted.
     *
     * @throws HierarchyRequestError
     */
    public function insertNode(Node $node)
    {
        if (($this->startContainer instanceof ProcessingInstruction
                || $this->startContainer instanceof Comment)
            || ($this->startContainer instanceof Text
                && $this->startContainer->parentNode === null)
        ) {
            throw new HierarchyRequestError();
        }

        $referenceNode = null;

        if ($this->startContainer instanceof Text) {
            $referenceNode = $this->startContainer;
        } else {
            if (isset($this->startContainer->childNodes[$this->startOffset])) {
                $referenceNode = $this
                    ->startContainer
                    ->childNodes[$this->startOffset];
            } else {
                $referenceNode = null;
            }
        }

        $parent = !$referenceNode
            ? $this->startContainer
            : $referenceNode->parentNode;
        $parent->ensurePreinsertionValidity($node, $referenceNode);

        if ($this->startContainer instanceof Text) {
            $this->startContainer->splitText($this->startOffset);
        }

        if ($node === $referenceNode) {
            $referenceNode = $referenceNode->nextSibling;
        }

        if (!$node->parentNode) {
            $node->parentNode->removeNode($node);
        }

        $newOffset = !$referenceNode
            ? $parent->getLength()
            : $referenceNode->getTreeIndex();
        $newOffset += $node instanceof DocumentFragment
            ? $node->getLength()
            : 1;

        $parent->preinsertNode($node, $referenceNode);

        if ($this->startContainer === $this->endContainer
            && $this->startOffset == $this->endOffset
        ) {
            $this->endContainer = $parent;
            $this->endOffset = $newOffset;
        }
    }

    /**
     * Returns a boolean indicating whether or not the given Node intersects the
     * Range.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-intersectsnode
     *
     * @param Node $node The Node to be checked for intersection.
     *
     * @return bool
     */
    public function intersectsNode(Node $node)
    {
        $root = $this->startContainer->getRootNode();

        if ($node->getRootNode() !== $root) {
            return false;
        }

        $parent = $node->parentNode;

        if (!$parent) {
            return true;
        }

        $offset = $node->getTreeIndex();
        $bp = [$parent, $offset];

        if ($this->computePosition($bp, [
                $this->endContainer, $this->endOffset
            ]) === 'before' && $this->computePosition($bp, [
                $this->startContainer, $this->startOffset + 1
            ]) === 'after'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Returns a boolean indicating whether the given point is within the Range.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-ispointinrange
     *
     * @param Node $node The Node whose position is to be checked.
     *
     * @param int $offset The offset within the given node.
     *
     * @return bool
     *
     * @throws InvalidNodeTypeError
     *
     * @throws IndexSizeError
     */
    public function isPointInRange(Node $node, $offset)
    {
        $root = $this->startContainer->getRootNode();

        if ($node->getRootNode() !== $root) {
            return false;
        }

        if ($node instanceof DocumentType) {
            throw new InvalidNodeTypeError();
        }

        if ($offset > $node->getLength()) {
            throw new IndexSizeError();
        }

        $bp = array($node, $offset);

        if ($this->computePosition($bp, [
                $this->startContainer, $this->startOffset
            ]) === 'before' || $this->computePosition($bp, [
                $this->endContainer, $this->endOffset
            ]) === 'after'
        ) {
            return false;
        }

        return true;
    }

    /**
     * Selects the given Node and its contents.
     *
     * @see https://dom.spec.whatwg.org/#concept-range-select
     *
     * @param Node $node The node and its contents to be selected.
     *
     * @throws InvalidNodeTypeError
     */
    public function selectNode(Node $node)
    {
        $parent = $node->parentNode;

        if (!$parent) {
            throw new InvalidNodeTypeError();
        }

        $index = $node->getTreeIndex();

        $this->startContainer = $parent;
        $this->startOffset = $index;
        $this->endContainer = $parent;
        $this->endOffset = $index + 1;
    }

    /**
     * Selects the contents of the given Node.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-selectnodecontents
     *
     * @param Node $node The Node whose content is to be selected.
     *
     * @throws InvalidNodeTypeError
     */
    public function selectNodeContents(Node $node)
    {
        if ($node instanceof DocumentType) {
            throw new InvalidNodeTypeError();
        }

        $this->startContainer = $node;
        $this->startOffset = 0;
        $this->endContainer = $node;
        $this->endOffset = $node->getLength();
    }

    /**
     * Sets the Range's end boundary.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-setend
     *
     * @param Node $node The Node where the Range ends.
     *
     * @param int $offset The offset within the given node where the Range
     *     ends.
     */
    public function setEnd(Node $node, $offset)
    {
        $this->setStartOrEnd('end', $node, $offset);
    }

    /**
     * Sets the Range's end boundary relative to the given Node.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-setendafter
     *
     * @param Node $node The Node where the Range will end.
     *
     * @throws InvalidNodeTypeError
     */
    public function setEndAfter(Node $node)
    {
        $parent = $node->parentNode;

        if (!$parent) {
            throw new InvalidNodeTypeError();
        }

        $this->setStartOrEnd('end', $parent, $node->getTreeIndex() + 1);
    }

    /**
     * Sets the Range's end boundary relative to the given Node.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-setendbefore
     *
     * @param Node $node The Node where the Range will end.
     *
     * @throws InvalidNodeTypeError
     */
    public function setEndBefore(Node $node)
    {
        $parent = $node->parentNode;

        if (!$parent) {
            throw new InvalidNodeTypeError();
        }

        $this->setStartOrEnd('end', $parent, $node->getTreeIndex());
    }

    /**
     * Sets the Range's start boundary relative to the given Node.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-setstart
     *
     * @param Node $node The Node where the Range will start.
     *
     * @param int  $aOffset The offset within the given node where the Range
     *     starts.
     */
    public function setStart(Node $node, $aOffset)
    {
        $this->setStartOrEnd('start', $node, $aOffset);
    }

    /**
     * Sets the Range's start boundary relative to the given Node.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-setstartafter
     *
     * @param Node $node The Node where the Range will start.
     *
     * @throws InvalidNodeTypeError
     */
    public function setStartAfter(Node $node)
    {
        $parent = $node->parentNode;

        if (!$parent) {
            throw new InvalidNodeTypeError();
        }

        $this->setStartOrEnd('start', $parent, $node->getTreeIndex() + 1);
    }

    /**
     * Sets the Range's start boundary relative to the given Node.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-setstartbefore
     *
     * @param Node $node The Node where the Range will start.
     *
     * @throws InvalidNodeTypeError
     */
    public function setStartBefore(Node $node)
    {
        $parent = $node->parentNode;

        if (!$parent) {
            throw new InvalidNodeTypeError();
        }

        $this->setStartOrEnd('start', $parent, $node->getTreeIndex());
    }

    /**
     * Wraps the content of Range in a new Node and inserts it in to the Document.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-surroundcontents
     *
     * @param Node $newParent The node that will surround the Range's content.
     */
    public function surroundCountents(Node $newParent)
    {
        if ((!($this->startContainer instanceof Text)
                && $this->isPartiallyContainedNode($this->startContainer))
            || (!($this->endContainer instanceof Text)
                && $this->isPartiallyContainedNode($this->endContainer))
        ) {
            throw new InvalidStateError();
        }

        if ($newParent instanceof Document
            || $newParent instanceof DocumentType
            || $newParent instanceof DocumentFragment
        ) {
            throw new InvalidNodeTypeError();
        }

        $fragment = $this->extractContents();

        if ($newParent->hasChildNodes()) {
            $newParent->replaceAllNodes(null);
        }

        $this->insertNode($newParent);
        $newParent->appendChild($fragment);
        $this->selectNode($newParent);
    }

    /**
     * Concatenates the contents of the Range in to a string.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-stringifier
     *
     * @return string
     */
    public function toString()
    {
        $s = '';
        $owner = $this->startContainer->getNodeDocument();
        $encoding = $owner->characterSet;

        if ($this->startContainer === $this->endContainer &&
            $this->startContainer instanceof Text
        ) {
            return mb_substr(
                $this->startContainer->data,
                $this->startOffset,
                $this->endOffset - $this->startOffset,
                $encoding
            );
        }

        if ($this->startContainer instanceof Text) {
            $s .= mb_substr(
                $this->startContainer->data,
                $this->startOffset,
                null,
                $encoding
            );
        }

        $tw = new TreeWalker(
            $this->startContainer->getRootNode(),
            NodeFilter::SHOW_TEXT,
            function ($aNode) {
                if ($this->isFullyContainedNode($aNode)) {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_REJECT;
            }
        );
        $tw->currentNode = $this->startContainer;

        while ($text = $tw->nextNode()) {
            $s .= $text->data;
        }

        if ($this->endContainer instanceof Text) {
            $s .= mb_substr(
                $this->endContainer->data,
                0,
                $this->endOffset,
                $encoding
            );
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
    public static function getRangeCollection()
    {
        return self::$collection;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#idl-def-range-createcontextualfragment(domstring)
     *
     * @param string $fragment
     *
     * @return DocumentFragment
     */
    public function createContextualFragment($fragment)
    {
        $node = $this->startContainer;

        switch ($node->nodeType) {
            case Node::DOCUMENT_NODE:
            case Node::DOCUMENT_FRAGMENT_NODE:
                $element = null;

                break;

            case Node::ELEMENT_NODE:
                $element = $node;

                break;

            case Node::TEXT_NODE:
            case Node::COMMENT_NODE:
                $element = $node->parentNode;

                break;

            case Node::DOCUMENT_TYPE_NODE:
            case Node::PROCESSING_INSTRUCTION_NODE:
                // DOM4 prevents this case.
                return;
        }

        // If either element is null or element's node document is an HTML
        // document and element's local name is "html" and element's namespace
        // is the HTML namespace, then let element be a new Element with "body"
        // as its local name, the HTML namespace as its namespace, and the
        // context object's node document as its node document.
        if ($element === null
            || ($element->getNodeDocument() instanceof HTMLDocument
                && $element->localName === 'html'
                && $element->namespaceURI === Namespaces::HTML)
        ) {
            $element = ElementFactory::create(
                $this->startContainer->getNodeDocument(),
                'body',
                Namespaces::HTML
            );

            // Let fragment node be the result of invoking the fragment parsing
            // algorithm with fragment as markup, and element as the context
            // element.
            $fragmentNode = ParserFactory::parseFragment($fragment, $element);

            // TODO: Unmark all scripts in fragment node as "already started".

            return $fragmentNode;
        }
    }

    /**
     * Compares the position of two boundary points.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-range-bp-position
     *
     * @param mixed[] $boundaryPointA An array containing a Node and an offset within that
     *     Node representing a boundary.
     *
     * @param mixed[] $boundaryPointB An array containing a Node and an offset within that
     *     Node representing a boundary.
     *
     * @return int Returns before, equal, or after based on the position of the
     *     first boundary relative to the second boundary.
     */
    private function computePosition($boundaryPointA, $boundaryPointB)
    {
        if ($boundaryPointA[0] === $boundaryPointB[0]) {
            if ($boundaryPointA[1] == $boundaryPointB[1]) {
                return 'equal';
            } elseif ($boundaryPointA[1] < $boundaryPointB[1]) {
                return 'before';
            } else {
                return 'after';
            }
        }

        $tw = new TreeWalker(
            $boundaryPointB[0]->getRootNode(),
            NodeFilter::SHOW_ALL,
            function ($aNode) use ($boundaryPointA) {
                if ($aNode === $boundaryPointA[0]) {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_SKIP;
            }
        );
        $tw->currentNode = $boundaryPointB[0];

        $AFollowsB = $tw->nextNode();

        if ($AFollowsB) {
            switch ($this->computePosition($boundaryPointB, $boundaryPointA)) {
                case 'after':
                    return 'before';
                case 'before':
                    return 'after';
            }
        }

        $ancestor = $boundaryPointB[0]->parentNode;

        while ($ancestor) {
            if ($ancestor === $boundaryPointA[0]) {
                break;
            }

            $ancestor = $ancestor->parentNode;
        }

        if ($ancestor) {
            $child = $boundaryPointB[0];
            $childNodes = $boundaryPointA[0]
                ->childNodes
                ->getIterator()
                ->getArrayCopy();

            while ($child) {
                if (in_array($child, $childNodes, true)) {
                    break;
                }

                $child = $child->parentNode;
            }

            if ($child->getTreeIndex() < $boundaryPointA[1]) {
                return 'after';
            }
        }

        return 'before';
    }

    /**
     * Returns true if the entire Node is within the Range, otherwise false.
     *
     * @see https://dom.spec.whatwg.org/#contained
     *
     * @param Node $node The Node to check against.
     *
     * @return bool
     */
    private function isFullyContainedNode(Node $node)
    {
        $startBP = array($this->startContainer, $this->startOffset);
        $endBP = array($this->endContainer, $this->endOffset);
        $root = $this->startContainer->getRootNode();

        return $node->getRootNode() === $root
            && $this->computePosition([$node, 0], $startBP) === 'after'
            && $this->computePosition(
                [$node, $node->getLength()],
                $endBP
            ) === 'before';
    }

    /**
     * Returns true if only a portion of the Node is contained within the Range.
     *
     * @see https://dom.spec.whatwg.org/#partially-contained
     *
     * @param Node $node The Node to check against.
     *
     * @return bool
     */
    private function isPartiallyContainedNode(Node $node)
    {
        $isAncestorOfStart = $node->contains($this->startContainer);
        $isAncestorOfEnd = $node->contains($this->endContainer);

        return ($isAncestorOfStart && !$isAncestorOfEnd)
            || (!$isAncestorOfStart && $isAncestorOfEnd);
    }

    /**
     * Sets the start or end boundary point for the Range.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-range-bp-set
     *
     * @param string $type Which boundary point should be set.  Valid values
     *     are start or end.
     *
     * @param Node $node The Node that will become the boundary.
     *
     * @param int $offset The offset within the given Node that will be the
     *     boundary.
     *
     * @throws InvalidNodeTypeError
     *
     * @throws IndexSizeError
     */
    private function setStartOrEnd($type, $node, $offset)
    {
        if ($node instanceof DocumentType) {
            throw new InvalidNodeTypeError();
        }

        if ($offset > $node->getLength()) {
            throw new IndexSizeError();
        }

        $bp = [$node, $offset];

        switch ($type) {
            case 'start':
                if ($this->computePosition($bp, [
                        $this->endContainer, $this->endOffset
                    ]) === 'after'
                    || $this->startContainer->getRootNode() !==
                    $node->getRootNode()
                ) {
                    $this->endContainer = $node;
                    $this->endOffset = $offset;
                }

                $this->startContainer = $node;
                $this->startOffset = $offset;

                break;

            case 'end':
                if ($this->computePosition($bp, [
                        $this->startContainer, $this->startOffset
                    ]) === 'before'
                    || $this->startContainer->getRootNode() !==
                    $node->getRootNode()
                ) {
                    $this->startContainer = $node;
                    $this->startOffset = $offset;
                }

                $this->endContainer = $node;
                $this->endOffset = $offset;
        }
    }
}
