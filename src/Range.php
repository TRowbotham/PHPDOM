<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Exception\InvalidNodeTypeError;
use Rowbot\DOM\Exception\InvalidStateError;
use Rowbot\DOM\Exception\NotSupportedError;
use Rowbot\DOM\Exception\WrongDocumentError;
use Rowbot\DOM\Parser\ParserFactory;
use Rowbot\DOM\Range\BoundaryPoint;
use Rowbot\DOM\Range\BoundaryType;
use Rowbot\DOM\Range\Position;
use Rowbot\DOM\Support\Stringable;

use function assert;
use function mb_substr;
use function spl_object_id;

/**
 * Represents a sequence of content within a node tree.
 *
 * @see https://dom.spec.whatwg.org/#range
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Range
 *
 * @property-read \Rowbot\DOM\Node $commonAncestor Returns the deepest node in the node tree that contains both the
 *                                                 start and end nodes.
 */
final class Range extends AbstractRange implements Stringable
{
    public const START_TO_START = 0;
    public const START_TO_END   = 1;
    public const END_TO_END     = 2;
    public const END_TO_START   = 3;

    /**
     * @var array<int, \Rowbot\DOM\Range\RangeBoundary>
     */
    private static $collection = [];

    public function __construct(Document $document)
    {
        parent::__construct(new BoundaryPoint($document, 0), new BoundaryPoint($document, 0));
        self::$collection[spl_object_id($this->range)] = $this->range;
    }

    public function __get(string $name)
    {
        if ($name === 'commonAncestorContainer') {
            return Node::getCommonAncestor($this->range->start->node, $this->range->end->node);
        }

        return parent::__get($name);
    }

    public function __clone(): void
    {
        parent::__clone();

        self::$collection[spl_object_id($this->range)] = $this->range;
    }

    public function __destruct()
    {
        unset(self::$collection[spl_object_id($this->range)]);
    }

    /**
     * Sets the Range's start boundary relative to the given Node.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-setstart
     *
     * @param \Rowbot\DOM\Node $node   The Node where the Range will start.
     * @param int              $offset The offset within the given node where the Range starts.
     */
    public function setStart(Node $node, int $offset): void
    {
        $this->setStartOrEnd(BoundaryType::START, $node, Utils::unsignedLong($offset));
    }

    /**
     * Sets the Range's end boundary.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-setend
     *
     * @param \Rowbot\DOM\Node $node   The Node where the Range ends.
     * @param int              $offset The offset within the given node where the Range ends.
     */
    public function setEnd(Node $node, int $offset): void
    {
        $this->setStartOrEnd(BoundaryType::END, $node, Utils::unsignedLong($offset));
    }

    /**
     * Sets the Range's start boundary relative to the given Node.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-setstartbefore
     *
     * @throws \Rowbot\DOM\Exception\InvalidNodeTypeError
     */
    public function setStartBefore(Node $node): void
    {
        $parent = $node->parentNode;

        if (!$parent) {
            throw new InvalidNodeTypeError();
        }

        $this->setStartOrEnd(BoundaryType::START, $parent, $node->getTreeIndex());
    }

    /**
     * Sets the Range's start boundary relative to the given Node.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-setstartafter
     *
     * @throws \Rowbot\DOM\Exception\InvalidNodeTypeError
     */
    public function setStartAfter(Node $node): void
    {
        $parent = $node->parentNode;

        if (!$parent) {
            throw new InvalidNodeTypeError();
        }

        $this->setStartOrEnd(BoundaryType::START, $parent, $node->getTreeIndex() + 1);
    }

    /**
     * Sets the Range's end boundary relative to the given Node.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-setendbefore
     *
     * @throws \Rowbot\DOM\Exception\InvalidNodeTypeError
     */
    public function setEndBefore(Node $node): void
    {
        $parent = $node->parentNode;

        if (!$parent) {
            throw new InvalidNodeTypeError();
        }

        $this->setStartOrEnd(BoundaryType::END, $parent, $node->getTreeIndex());
    }

    /**
     * Sets the Range's end boundary relative to the given Node.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-setendafter
     *
     * @throws \Rowbot\DOM\Exception\InvalidNodeTypeError
     */
    public function setEndAfter(Node $node): void
    {
        $parent = $node->parentNode;

        if (!$parent) {
            throw new InvalidNodeTypeError();
        }

        $this->setStartOrEnd(BoundaryType::END, $parent, $node->getTreeIndex() + 1);
    }

    /**
     * Collapses the Range to one of its boundary points.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-collapse
     *
     * @param bool $toStart (optional) If true is passed, the Range will collapse on its starting boundary, otherwise it
     *                      will collapse on its ending boundary.
     */
    public function collapse(bool $toStart = false): void
    {
        if ($toStart) {
            $this->range->end = clone $this->range->start;
        } else {
            $this->range->start = clone $this->range->end;
        }
    }

    /**
     * Selects the given Node and its contents.
     *
     * @see https://dom.spec.whatwg.org/#concept-range-select
     *
     * @throws \Rowbot\DOM\Exception\InvalidNodeTypeError
     */
    public function selectNode(Node $node): void
    {
        $parent = $node->parentNode;

        if (!$parent) {
            throw new InvalidNodeTypeError();
        }

        $index = $node->getTreeIndex();

        $this->range->start = new BoundaryPoint($parent, $index);
        $this->range->end = new BoundaryPoint($parent, $index + 1);
    }

    /**
     * Selects the contents of the given Node.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-selectnodecontents
     *
     * @throws \Rowbot\DOM\Exception\InvalidNodeTypeError
     */
    public function selectNodeContents(Node $node): void
    {
        if ($node instanceof DocumentType) {
            throw new InvalidNodeTypeError();
        }

        $this->range->start = new BoundaryPoint($node, 0);
        $this->range->end = new BoundaryPoint($node, $node->getLength());
    }

    /**
     * Compares the boundary points of this Range with another Range.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-compareboundarypoints
     *
     * @param int               $how         A constant describing how the two Ranges should be compared. Possible
     *                                       values:
     *
     *                                       Range::END_TO_END     - Compares the end boundary points of both Ranges.
     *                                       Range::END_TO_START   - Compares the end boudary point of $sourceRange to
     *                                                               the start boundary point of this Range.
     *                                       Range::START_TO_END   - Compares the start boundary point of $sourceRange
     *                                                               to the end boundary of this Range.
     *                                       Range::START_TO_START - Compares the start boundary point of $sourceRange
     *                                                               to the start boundary of this Range.
     *
     * @param \Rowbot\DOM\Range $sourceRange A Range whose boundary points are to be compared.
     *
     * @return int Returns -1, 0, or 1 indicating wether the Range's boundary points are before, equal, or after
     *             $sourceRange's boundary points, respectively.
     *
     * @throws \Rowbot\DOM\Exception\NotSupportedError
     * @throws \Rowbot\DOM\Exception\WrongDocumentError
     */
    public function compareBoundaryPoints(int $how, self $sourceRange): int
    {
        if ($how < self::START_TO_START || $how > self::END_TO_START) {
            throw new NotSupportedError();
        }

        $sourceRangeRoot = $sourceRange->range->start->node->getRootNode();

        if ($this->range->start->node->getRootNode() !== $sourceRangeRoot) {
            throw new WrongDocumentError();
        }

        switch ($how) {
            case self::START_TO_START:
                $thisPoint = $this->range->start;
                $otherPoint = $sourceRange->range->start;

                break;

            case self::START_TO_END:
                $thisPoint = $this->range->end;
                $otherPoint = $sourceRange->range->start;

                break;

            case self::END_TO_END:
                $thisPoint = $this->range->end;
                $otherPoint = $sourceRange->range->end;

                break;

            case self::END_TO_START:
                $thisPoint = $this->range->start;
                $otherPoint = $sourceRange->range->end;

                break;
        }

        return BoundaryPoint::comparePosition($thisPoint, $otherPoint)->value;
    }

    /**
     * Removes the contents of the Range from the Document.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-deletecontents
     */
    public function deleteContents(): void
    {
        if ($this->range->isCollapsed()) {
            return;
        }

        $originalStartNode = $this->range->start->node;
        $originalStartOffset = $this->range->start->offset;
        $originalEndNode = $this->range->end->node;
        $originalEndOffset = $this->range->end->offset;

        if (
            $originalStartNode === $originalEndNode
            && ($originalStartNode instanceof Text
                || $originalStartNode instanceof ProcessingInstruction
                || $originalStartNode instanceof Comment)
        ) {
            $originalStartNode->doReplaceData(
                $originalStartOffset,
                $originalEndOffset - $originalStartOffset,
                ''
            );

            return;
        }

        $nodesToRemove = [];
        $commonAncestor = Node::getCommonAncestor($originalStartNode, $originalEndNode);

        if ($commonAncestor) {
            $node = $originalStartNode->nextNode($commonAncestor);

            while ($node) {
                if ($this->isFullyContainedNode($node)) {
                    $nodesToRemove[] = $node;

                    // $node is fully contained, so skip checking its descendants as they are all
                    // contained as well.
                    while ($node && !$node->nextSibling) {
                        if ($node === $commonAncestor) {
                            break 2;
                        }

                        $node = $node->parentNode;
                    }

                    if ($node === $commonAncestor) {
                        break;
                    }

                    if ($node) {
                        $node = $node->nextSibling;
                    }

                    continue;
                }

                $node = $node->nextNode($commonAncestor);
            }
        }

        if ($originalStartNode->contains($originalEndNode)) {
            $newNode = $originalStartNode;
            $newOffset = $originalStartOffset;
        } else {
            // 6.1. Let reference node equal original start node.
            $referenceNode = $originalStartNode;

            // 6.2. While reference node’s parent is not null and is not an inclusive ancestor of original end node,
            // set reference node to its parent.
            while (($parent = $referenceNode->parentNode) !== null && !$parent->contains($originalEndNode)) {
                $referenceNode = $parent;
            }

            // Note: If reference node’s parent were null, it would be the root of this, so would be an inclusive
            // ancestor of original end node, and we could not reach this point.
            assert($referenceNode->parentNode !== null);

            // 6.3. Set new node to the parent of reference node, and new offset to one plus the index of reference
            // node.
            $newNode = $referenceNode->parentNode;
            $newOffset = $referenceNode->getTreeIndex() + 1;
        }

        if (
            $originalStartNode instanceof Text
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
            $node->removeNode();
        }

        if (
            $originalEndNode instanceof Text
            || $originalEndNode instanceof ProcessingInstruction
            || $originalEndNode instanceof Comment
        ) {
            $originalEndNode->doReplaceData(0, $originalEndOffset, '');
        }

        $this->range->start = new BoundaryPoint($newNode, $newOffset);
        $this->range->end = new BoundaryPoint($newNode, $newOffset);
    }

    /**
     * Extracts the content of the Range from the node tree and places it in a
     * DocumentFragment.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-extractcontents
     */
    public function extractContents(): DocumentFragment
    {
        $fragment = $this->range->start->node->getNodeDocument()->createDocumentFragment();

        if ($this->range->isCollapsed()) {
            return $fragment;
        }

        $originalStartNode = $this->range->start->node;
        $originalStartOffset = $this->range->start->offset;
        $originalEndNode = $this->range->end->node;
        $originalEndOffset = $this->range->end->offset;

        if (
            $originalStartNode === $originalEndNode
            && ($originalStartNode instanceof Text
                || $originalStartNode instanceof ProcessingInstruction
                || $originalStartNode instanceof Comment)
        ) {
            $clone = $originalStartNode->cloneNodeInternal();
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

        $commonAncestor = Node::getCommonAncestor($originalStartNode, $originalEndNode);
        // It should be impossible for common ancestor to be null here since both nodes should be
        // in the same tree.
        assert($commonAncestor !== null);
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
            $node = $commonAncestor->lastChild;

            while ($node) {
                if ($this->isPartiallyContainedNode($node)) {
                    $lastPartiallyContainedChild = $node;

                    break;
                }

                $node = $node->previousSibling;
            }
        }

        $containedChildren = [];

        foreach ($commonAncestor->childNodes as $childNode) {
            if ($this->isFullyContainedNode($childNode)) {
                if ($childNode instanceof DocumentType) {
                    throw new HierarchyRequestError();
                }

                $containedChildren[] = $childNode;
            }
        }

        if ($originalStartNode->contains($originalEndNode)) {
            $newNode = $originalStartNode;
            $newOffset = $originalStartOffset;
        } else {
            $referenceNode = $originalStartNode;
            $parent = $referenceNode->parentNode;

            while ($parent && !$parent->contains($originalEndNode)) {
                $referenceNode = $parent;
                $parent = $referenceNode->parentNode;
            }

            // Note: If reference node’s parent is null, it would be the root of range, so would be an inclusive
            // ancestor of original end node, and we could not reach this point.
            assert($parent !== null);
            $newNode = $parent;
            $newOffset = $referenceNode->getTreeIndex() + 1;
        }

        if (
            $firstPartiallyContainedChild instanceof Text
            || $firstPartiallyContainedChild instanceof ProcessingInstruction
            || $firstPartiallyContainedChild instanceof Comment
        ) {
            // Note: In this case, first partially contained child is original start node.
            assert($originalStartNode instanceof CharacterData);
            $clone = $originalStartNode->cloneNodeInternal();
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
            $clone = $firstPartiallyContainedChild->cloneNodeInternal();
            $fragment->appendChild($clone);
            $subrange = clone $this;
            $subrange->range->start = new BoundaryPoint($originalStartNode, $originalStartOffset);
            $subrange->range->end = new BoundaryPoint(
                $firstPartiallyContainedChild,
                $firstPartiallyContainedChild->getLength()
            );
            $subfragment = $subrange->extractContents();
            $clone->appendChild($subfragment);
        }

        foreach ($containedChildren as $child) {
            $fragment->appendChild($child);
        }

        if (
            $lastPartiallyContainedChild instanceof Text
            || $lastPartiallyContainedChild instanceof ProcessingInstruction
            || $lastPartiallyContainedChild instanceof Comment
        ) {
            // Note: In this case, last partially contained child is original end node.
            assert($originalEndNode instanceof CharacterData);
            $clone = $originalEndNode->cloneNodeInternal();
            $clone->data = $originalEndNode->substringData(0, $originalEndOffset);
            $fragment->appendChild($clone);
            $originalEndNode->doReplaceData(0, $originalEndOffset, '');
        } elseif ($lastPartiallyContainedChild) {
            $clone = $lastPartiallyContainedChild->cloneNodeInternal();
            $fragment->appendChild($clone);
            $subrange = clone $this;
            $subrange->range->start = new BoundaryPoint($lastPartiallyContainedChild, 0);
            $subrange->range->end = new BoundaryPoint($originalEndNode, $originalEndOffset);
            $subfragment = $subrange->extractContents();
            $clone->appendChild($subfragment);
        }

        $this->range->start = new BoundaryPoint($newNode, $newOffset);
        $this->range->end = new BoundaryPoint($newNode, $newOffset);

        return $fragment;
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-range-clonecontents
     *
     * @throws \Rowbot\DOM\Exception\HierarchyRequestError
     */
    public function cloneContents(): DocumentFragment
    {
        $nodeDocument = $this->range->start->node->getNodeDocument();
        $fragment = $nodeDocument->createDocumentFragment();

        if ($this->range->isCollapsed()) {
            return $fragment;
        }

        $originalStartNode = $this->range->start->node;
        $originalStartOffset = $this->range->start->offset;
        $originalEndNode = $this->range->end->node;
        $originalEndOffset = $this->range->end->offset;

        if (
            $originalStartNode === $originalEndNode
            && ($originalStartNode instanceof Text
                || $originalStartNode instanceof ProcessingInstruction
                || $originalStartNode instanceof Comment)
        ) {
            $clone = $originalStartNode->cloneNodeInternal();
            $clone->data = $originalStartNode->substringData(
                $originalStartOffset,
                $originalEndOffset - $originalStartOffset
            );
            $fragment->appendChild($clone);

            return $fragment;
        }

        $commonAncestor = Node::getCommonAncestor($originalStartNode, $originalEndNode);
        // It should be impossible for common ancestor to be null here since both nodes should be
        // in the same tree.
        assert($commonAncestor !== null);
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
            $node = $commonAncestor->lastChild;

            while ($node) {
                if ($this->isPartiallyContainedNode($node)) {
                    $lastPartiallyContainedChild = $node;

                    break;
                }

                $node = $node->previousSibling;
            }
        }

        $containedChildrenStart = null;
        $containedChildrenEnd = null;
        $child = $firstPartiallyContainedChild ?: $commonAncestor->firstChild;

        for (; $child; $child = $child->nextSibling) {
            if ($this->isFullyContainedNode($child)) {
                $containedChildrenStart = $child;

                break;
            }
        }

        $child = $lastPartiallyContainedChild ?: $commonAncestor->lastChild;

        for (; $child !== $containedChildrenStart; $child = $child->previousSibling) {
            if ($this->isFullyContainedNode($child)) {
                $containedChildrenEnd = $child;

                break;
            }
        }

        if (!$containedChildrenEnd) {
            $containedChildrenEnd = $containedChildrenStart;
        }

        // $containedChildrenStart and $containedChildrenEnd may be null here, but this loop still works correctly
        for ($child = $containedChildrenStart; $child !== $containedChildrenEnd; $child = $child->nextSibling) {
            if ($child instanceof DocumentType) {
                throw new HierarchyRequestError();
            }
        }

        if (
            $firstPartiallyContainedChild instanceof Text
            || $firstPartiallyContainedChild instanceof ProcessingInstruction
            || $firstPartiallyContainedChild instanceof Comment
        ) {
            $clone = $originalStartNode->cloneNodeInternal();
            $clone->data = $originalStartNode->substringData(
                $originalStartOffset,
                $originalStartNode->length - $originalStartOffset
            );
            $fragment->appendChild($clone);
        } elseif ($firstPartiallyContainedChild) {
            $clone = $firstPartiallyContainedChild->cloneNodeInternal();
            $fragment->appendChild($clone);
            $subrange = clone $this;
            $subrange->range->start = new BoundaryPoint($originalStartNode, $originalStartOffset);
            $subrange->range->end = new BoundaryPoint(
                $firstPartiallyContainedChild,
                $firstPartiallyContainedChild->getLength()
            );
            $subfragment = $subrange->cloneContents();
            $clone->appendChild($subfragment);
        }

        // $containedChildrenStart and $containedChildrenEnd may be null here, but this loop still works correctly
        for ($child = $containedChildrenStart; $child !== $containedChildrenEnd; $child = $child->nextSibling) {
            $clone = $child->cloneNodeInternal(null, true);
            $fragment->appendChild($clone);
        }

        // If not null, this node wasn't processed by the loop
        if ($containedChildrenEnd) {
            $clone = $child->cloneNodeInternal(null, true);
            $fragment->appendChild($clone);
        }

        if (
            $lastPartiallyContainedChild instanceof Text
            || $lastPartiallyContainedChild instanceof ProcessingInstruction
            || $lastPartiallyContainedChild instanceof Comment
        ) {
            $clone = $originalEndNode->cloneNodeInternal();
            $clone->data = $originalEndNode->substringData(
                0,
                $originalEndOffset
            );
            $fragment->appendChild($clone);
        } elseif ($lastPartiallyContainedChild) {
            $clone = $lastPartiallyContainedChild->cloneNodeInternal();
            $fragment->appendChild($clone);
            $subrange = clone $this;
            $subrange->range->start = new BoundaryPoint($lastPartiallyContainedChild, 0);
            $subrange->range->end = new BoundaryPoint($originalEndNode, $originalEndOffset);
            $subfragment = $subrange->cloneContents();
            $clone->appendChild($subfragment);
        }

        return $fragment;
    }

    /**
     * Inserts a new Node into at the start of the Range.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-insertnode
     *
     * @throws \Rowbot\DOM\Exception\HierarchyRequestError
     */
    public function insertNode(Node $node): void
    {
        if (
            $this->range->start->node instanceof ProcessingInstruction || $this->range->start->node instanceof Comment
            || ($this->range->start->node instanceof Text && $this->range->start->node->parentNode === null)
            || $this->range->start->node === $node
        ) {
            throw new HierarchyRequestError();
        }

        $referenceNode = null;

        if ($this->range->start->node instanceof Text) {
            $referenceNode = $this->range->start->node;
        } else {
            $referenceNode = $this->range->start->node->childNodes[$this->range->start->offset] ?? null;
        }

        $parent = !$referenceNode
            ? $this->range->start->node
            : $referenceNode->parentNode;
        assert($parent !== null);
        $parent->ensurePreinsertionValidity($node, $referenceNode);

        if ($this->range->start->node instanceof Text) {
            $referenceNode = $this->range->start->node->splitText($this->range->start->offset);
        }

        if ($node === $referenceNode) {
            $referenceNode = $referenceNode->nextSibling;
        }

        if ($node->parentNode) {
            $node->removeNode();
        }

        $newOffset = !$referenceNode
            ? $parent->getLength()
            : $referenceNode->getTreeIndex();
        $newOffset += $node instanceof DocumentFragment
            ? $node->getLength()
            : 1;

        $parent->preinsertNode($node, $referenceNode);

        if ($this->range->isCollapsed()) {
            $this->range->end = new BoundaryPoint($parent, $newOffset);
        }
    }

    /**
     * Wraps the content of Range in a new Node and inserts it in to the Document.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-surroundcontents
     *
     * @throws \Rowbot\DOM\Exception\InvalidNodeTypeError
     * @throws \Rowbot\DOM\Exception\InvalidStateError
     */
    public function surroundContents(Node $newParent): void
    {
        $commonAncestor = Node::getCommonAncestor($this->range->start->node, $this->range->end->node);

        if ($commonAncestor) {
            $node = $commonAncestor->nextNode($commonAncestor);

            while ($node) {
                if (!$node instanceof Text && $this->isPartiallyContainedNode($node)) {
                    throw new InvalidStateError();
                }

                $node = $node->nextNode($commonAncestor);
            }
        }

        if (
            $newParent instanceof Document
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
     * Returns a new Range that has identical starting and ending nodes
     * as well as identical starting and ending offsets.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-clonerange
     */
    public function cloneRange(): self
    {
        return clone $this;
    }

    /**
     * The detach() method, when invoked, must do nothing.
     *
     * NOTE: Its functionality (disabling a Range object) was removed, but the method itself is preserved for
     * compatibility.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-detach
     */
    public function detach(): void
    {
        // Do nothing.
    }

    /**
     * Returns a boolean indicating whether the given point is within the Range.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-ispointinrange
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError
     * @throws \Rowbot\DOM\Exception\InvalidNodeTypeError
     */
    public function isPointInRange(Node $node, int $offset): bool
    {
        $offset = Utils::unsignedLong($offset);
        $root = $this->range->start->node->getRootNode();

        if ($node->getRootNode() !== $root) {
            return false;
        }

        if ($node instanceof DocumentType) {
            throw new InvalidNodeTypeError();
        }

        if ($offset > $node->getLength()) {
            throw new IndexSizeError();
        }

        $bp = new BoundaryPoint($node, $offset);

        if (
            BoundaryPoint::comparePosition($bp, $this->range->start) === Position::BEFORE
            || BoundaryPoint::comparePosition($bp, $this->range->end) === Position::AFTER
        ) {
            return false;
        }

        return true;
    }

    /**
     * Checks to see if a node comes before, after, or within the range.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-comparepoint
     *
     * @return int Returns -1, 0, or 1 to indicated whether the node lies before, after, or within the range,
     *             respectively.
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError
     * @throws \Rowbot\DOM\Exception\InvalidNodeTypeError
     * @throws \Rowbot\DOM\Exception\WrongDocumentError
     */
    public function comparePoint(Node $node, int $offset): int
    {
        $offset = Utils::unsignedLong($offset);
        $root = $this->range->start->node->getRootNode();

        if ($node->getRootNode() !== $root) {
            throw new WrongDocumentError();
        }

        if ($node instanceof DocumentType) {
            throw new InvalidNodeTypeError();
        }

        if ($offset > $node->getLength()) {
            throw new IndexSizeError();
        }

        $bp = new BoundaryPoint($node, $offset);

        if (BoundaryPoint::comparePosition($bp, $this->range->start) === Position::BEFORE) {
            return -1;
        }

        if (BoundaryPoint::comparePosition($bp, $this->range->end) === Position::AFTER) {
            return 1;
        }

        return 0;
    }

    /**
     * Returns a boolean indicating whether or not the given Node intersects the
     * Range.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-intersectsnode
     */
    public function intersectsNode(Node $node): bool
    {
        $root = $this->range->start->node->getRootNode();

        if ($node->getRootNode() !== $root) {
            return false;
        }

        $parent = $node->parentNode;

        if (!$parent) {
            return true;
        }

        $offset = $node->getTreeIndex();
        $position1 = BoundaryPoint::comparePosition(new BoundaryPoint($parent, $offset), $this->range->end);
        $position2 = BoundaryPoint::comparePosition(new BoundaryPoint($parent, $offset + 1), $this->range->start);

        return $position1 === Position::BEFORE && $position2 === Position::AFTER;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Concatenates the contents of the Range in to a string.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-stringifier
     */
    public function toString(): string
    {
        $s = '';

        if ($this->range->start->node === $this->range->end->node && $this->range->start->node instanceof Text) {
            return mb_substr(
                $this->range->start->node->data,
                $this->range->start->offset,
                $this->range->end->offset - $this->range->start->offset,
                'utf-8'
            );
        }

        if ($this->range->start->node instanceof Text) {
            $s .= mb_substr(
                $this->range->start->node->data,
                $this->range->start->offset,
                null,
                'utf-8'
            );
        }

        $root = $this->range->start->node->getRootNode();
        $node = $this->range->start->node->nextNode($root);

        while ($node) {
            if ($node instanceof Text && $this->isFullyContainedNode($node)) {
                $s .= $node->data;
            }

            $node = $node->nextNode($root);
        }

        if ($this->range->end->node instanceof Text) {
            $s .= mb_substr(
                $this->range->end->node->data,
                0,
                $this->range->end->offset,
                'utf-8'
            );
        }

        return $s;
    }

    /**
     * Returns a collection of all live ranges.
     *
     * @internal
     *
     * @return array<int, \Rowbot\DOM\Range\RangeBoundary>
     */
    public static function getRangeCollection(): array
    {
        return self::$collection;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dom-range-createcontextualfragment
     */
    public function createContextualFragment(string $fragment): DocumentFragment
    {
        $node = $this->range->start->node;
        $element = null;

        if ($node instanceof Document || $node instanceof DocumentFragment) {
            $element = null;
        } elseif ($node instanceof Element) {
            $element = $node;
        } elseif ($node instanceof Text || $node instanceof Comment) {
            $element = $node->parentElement;
        } elseif ($node instanceof DocumentType || $node instanceof ProcessingInstruction) {
            // DOM4 prevents this case.
        }

        // If either element is null or element's node document is an HTML
        // document and element's local name is "html" and element's namespace
        // is the HTML namespace, then let element be a new Element with "body"
        // as its local name, the HTML namespace as its namespace, and the
        // context object's node document as its node document.
        if (
            $element === null
            || ($element->getNodeDocument()->isHTMLDocument()
                && $element->localName === 'html'
                && $element->namespaceURI === Namespaces::HTML)
        ) {
            $element = ElementFactory::create(
                $this->range->start->node->getNodeDocument(),
                'body',
                Namespaces::HTML
            );
        }

        // Let fragment node be the result of invoking the fragment parsing
        // algorithm with fragment as markup, and element as the context
        // element.
        $fragmentNode = ParserFactory::parseFragment($fragment, $element);

        // TODO: Unmark all scripts in fragment node as "already started" and as "parser-inserted".

        return $fragmentNode;
    }

    /**
     * Returns true if the entire Node is within the Range, otherwise false.
     *
     * @see https://dom.spec.whatwg.org/#contained
     */
    private function isFullyContainedNode(Node $node): bool
    {
        return $node->getRootNode() === $this->range->start->node->getRootNode()
            && BoundaryPoint::comparePosition(new BoundaryPoint($node, 0), $this->range->start) === Position::AFTER
            && BoundaryPoint::comparePosition(
                new BoundaryPoint($node, $node->getLength()),
                $this->range->end
            ) === Position::BEFORE;
    }

    /**
     * Returns true if only a portion of the Node is contained within the Range.
     *
     * @see https://dom.spec.whatwg.org/#partially-contained
     */
    private function isPartiallyContainedNode(Node $node): bool
    {
        return $node->contains($this->range->start->node) xor $node->contains($this->range->end->node);
    }

    /**
     * Sets the start or end boundary point for the Range.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-range-bp-set
     *
     * @param \Rowbot\DOM\Node $node   The Node that will become the boundary.
     * @param int              $offset The offset within the given Node that will be the boundary.
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError
     * @throws \Rowbot\DOM\Exception\InvalidNodeTypeError
     */
    private function setStartOrEnd(BoundaryType $type, Node $node, int $offset): void
    {
        $offset = Utils::unsignedLong($offset);

        if ($node instanceof DocumentType) {
            throw new InvalidNodeTypeError();
        }

        if ($offset > $node->getLength()) {
            throw new IndexSizeError();
        }

        $bp = new BoundaryPoint($node, $offset);

        switch ($type) {
            case BoundaryType::START:
                if (
                    $this->range->start->node->getRootNode() !== $node->getRootNode()
                    || BoundaryPoint::comparePosition($bp, $this->range->end) === Position::AFTER
                ) {
                    $this->range->end = clone $bp;
                }

                $this->range->start = clone $bp;

                break;

            case BoundaryType::END:
                if (
                    $this->range->start->node->getRootNode() !== $node->getRootNode()
                    || BoundaryPoint::comparePosition($bp, $this->range->start) === Position::BEFORE
                ) {
                    $this->range->start = clone $bp;
                }

                $this->range->end = clone $bp;
        }
    }
}
