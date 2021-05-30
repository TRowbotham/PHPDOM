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
use Rowbot\DOM\Support\Stringable;
use SplObjectStorage;

use function assert;
use function mb_substr;

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
     * @var \SplObjectStorage<\Rowbot\DOM\RangeBoundary, null>|null
     */
    private static $collection;

    public function __construct(Document $document)
    {
        $range = new RangeBoundary($document, 0, $document, 0);
        parent::__construct($range);
        self::getRangeCollection()->attach($this->range);
    }

    public function __get(string $name)
    {
        if ($name === 'commonAncestorContainer') {
            return Node::getCommonAncestor($this->range->startNode, $this->range->endNode);
        }

        return parent::__get($name);
    }

    public function __clone()
    {
        $this->range = clone $this->range;
        self::getRangeCollection()->attach($this->range);
    }

    public function __destruct()
    {
        self::getRangeCollection()->detach($this->range);
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
        $this->setStartOrEnd('start', $node, Utils::unsignedLong($offset));
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
        $this->setStartOrEnd('end', $node, Utils::unsignedLong($offset));
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

        $this->setStartOrEnd('start', $parent, $node->getTreeIndex());
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

        $this->setStartOrEnd('start', $parent, $node->getTreeIndex() + 1);
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

        $this->setStartOrEnd('end', $parent, $node->getTreeIndex());
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

        $this->setStartOrEnd('end', $parent, $node->getTreeIndex() + 1);
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
            $this->range->endNode = $this->range->startNode;
            $this->range->endOffset = $this->range->startOffset;
        } else {
            $this->range->startNode = $this->range->endNode;
            $this->range->startOffset = $this->range->endOffset;
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

        $this->range->startNode = $parent;
        $this->range->startOffset = $index;
        $this->range->endNode = $parent;
        $this->range->endOffset = $index + 1;
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

        $this->range->startNode = $node;
        $this->range->startOffset = 0;
        $this->range->endNode = $node;
        $this->range->endOffset = $node->getLength();
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

        $sourceRangeRoot = $sourceRange->range->startNode->getRootNode();

        if ($this->range->startNode->getRootNode() !== $sourceRangeRoot) {
            throw new WrongDocumentError();
        }

        switch ($how) {
            case self::START_TO_START:
                $thisPoint = [$this->range->startNode, $this->range->startOffset];
                $otherPoint = [$sourceRange->range->startNode, $sourceRange->range->startOffset];

                break;

            case self::START_TO_END:
                $thisPoint = [$this->range->endNode, $this->range->endOffset];
                $otherPoint = [$sourceRange->range->startNode, $sourceRange->range->startOffset];

                break;

            case self::END_TO_END:
                $thisPoint = [$this->range->endNode, $this->range->endOffset];
                $otherPoint = [$sourceRange->range->endNode, $sourceRange->range->endOffset];

                break;

            case self::END_TO_START:
                $thisPoint = [$this->range->startNode, $this->range->startOffset];
                $otherPoint = [$sourceRange->range->endNode, $sourceRange->range->endOffset];

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
     * Removes the contents of the Range from the Document.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-deletecontents
     */
    public function deleteContents(): void
    {
        if (
            $this->range->startNode === $this->range->endNode
            && $this->range->startOffset === $this->range->endOffset
        ) {
            return;
        }

        $originalStartNode = $this->range->startNode;
        $originalStartOffset = $this->range->startOffset;
        $originalEndNode = $this->range->endNode;
        $originalEndOffset = $this->range->endOffset;

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

        $this->range->startNode = $newNode;
        $this->range->startOffset = $newOffset;
        $this->range->endNode = $newNode;
        $this->range->endOffset = $newOffset;
    }

    /**
     * Extracts the content of the Range from the node tree and places it in a
     * DocumentFragment.
     *
     * @see https://dom.spec.whatwg.org/#dom-range-extractcontents
     */
    public function extractContents(): DocumentFragment
    {
        $fragment = $this->range->startNode->getNodeDocument()->createDocumentFragment();

        if (
            $this->range->startNode === $this->range->endNode
            && $this->range->startOffset === $this->range->endOffset
        ) {
            return $fragment;
        }

        $originalStartNode = $this->range->startNode;
        $originalStartOffset = $this->range->startOffset;
        $originalEndNode = $this->range->endNode;
        $originalEndOffset = $this->range->endOffset;

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
            $subrange->range->startNode = $originalStartNode;
            $subrange->range->startOffset = $originalStartOffset;
            $subrange->range->endNode = $firstPartiallyContainedChild;
            $subrange->range->endOffset = $firstPartiallyContainedChild->getLength();
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
            $subrange->range->startNode = $lastPartiallyContainedChild;
            $subrange->range->startOffset = 0;
            $subrange->range->endNode = $originalEndNode;
            $subrange->range->endOffset = $originalEndOffset;
            $subfragment = $subrange->extractContents();
            $clone->appendChild($subfragment);
        }

        $this->range->startNode = $newNode;
        $this->range->startOffset = $newOffset;
        $this->range->endNode = $newNode;
        $this->range->endOffset = $newOffset;

        return $fragment;
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-range-clonecontents
     *
     * @throws \Rowbot\DOM\Exception\HierarchyRequestError
     */
    public function cloneContents(): DocumentFragment
    {
        $nodeDocument = $this->range->startNode->getNodeDocument();
        $fragment = $nodeDocument->createDocumentFragment();

        if (
            $this->range->startNode === $this->range->endNode
            && $this->range->startOffset === $this->range->endOffset
        ) {
            return $fragment;
        }

        $originalStartNode = $this->range->startNode;
        $originalStartOffset = $this->range->startOffset;
        $originalEndNode = $this->range->endNode;
        $originalEndOffset = $this->range->endOffset;

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
            $subrange->range->startNode = $originalStartNode;
            $subrange->range->startOffset = $originalStartOffset;
            $subrange->range->endNode = $firstPartiallyContainedChild;
            $subrange->range->endOffset = $firstPartiallyContainedChild->getLength();
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
            $subrange->range->startNode = $lastPartiallyContainedChild;
            $subrange->range->startOffset = 0;
            $subrange->range->endNode = $originalEndNode;
            $subrange->range->endOffset = $originalEndOffset;
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
            $this->range->startNode instanceof ProcessingInstruction || $this->range->startNode instanceof Comment
            || ($this->range->startNode instanceof Text && $this->range->startNode->parentNode === null)
            || $this->range->startNode === $node
        ) {
            throw new HierarchyRequestError();
        }

        $referenceNode = null;

        if ($this->range->startNode instanceof Text) {
            $referenceNode = $this->range->startNode;
        } else {
            $referenceNode = $this->range->startNode->childNodes[$this->range->startOffset] ?? null;
        }

        $parent = !$referenceNode
            ? $this->range->startNode
            : $referenceNode->parentNode;
        assert($parent !== null);
        $parent->ensurePreinsertionValidity($node, $referenceNode);

        if ($this->range->startNode instanceof Text) {
            $referenceNode = $this->range->startNode->splitText($this->range->startOffset);
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

        if (
            $this->range->startNode === $this->range->endNode
            && $this->range->startOffset === $this->range->endOffset
        ) {
            $this->range->endNode = $parent;
            $this->range->endOffset = $newOffset;
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
        $commonAncestor = Node::getCommonAncestor($this->range->startNode, $this->range->endNode);

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
        $root = $this->range->startNode->getRootNode();

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

        if (
            $this->computePosition($bp, [$this->range->startNode, $this->range->startOffset]) === 'before'
            || $this->computePosition($bp, [$this->range->endNode, $this->range->endOffset]) === 'after'
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
        $root = $this->range->startNode->getRootNode();

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

        if ($this->computePosition($bp, [$this->range->startNode, $this->range->startOffset]) === 'before') {
            return -1;
        }

        if ($this->computePosition($bp, [$this->range->endNode, $this->range->endOffset]) === 'after') {
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
        $root = $this->range->startNode->getRootNode();

        if ($node->getRootNode() !== $root) {
            return false;
        }

        $parent = $node->parentNode;

        if (!$parent) {
            return true;
        }

        $offset = $node->getTreeIndex();
        $position1 = $this->computePosition([$parent, $offset], [$this->range->endNode, $this->range->endOffset]);
        $position2 = $this->computePosition(
            [$parent, $offset + 1],
            [$this->range->startNode, $this->range->startOffset]
        );

        return $position1 === 'before' && $position2 === 'after';
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

        if ($this->range->startNode === $this->range->endNode && $this->range->startNode instanceof Text) {
            return mb_substr(
                $this->range->startNode->data,
                $this->range->startOffset,
                $this->range->endOffset - $this->range->startOffset,
                'utf-8'
            );
        }

        if ($this->range->startNode instanceof Text) {
            $s .= mb_substr(
                $this->range->startNode->data,
                $this->range->startOffset,
                null,
                'utf-8'
            );
        }

        $root = $this->range->startNode->getRootNode();
        $node = $this->range->startNode->nextNode($root);

        while ($node) {
            if ($node instanceof Text && $this->isFullyContainedNode($node)) {
                $s .= $node->data;
            }

            $node = $node->nextNode($root);
        }

        if ($this->range->endNode instanceof Text) {
            $s .= mb_substr(
                $this->range->endNode->data,
                0,
                $this->range->endOffset,
                'utf-8'
            );
        }

        return $s;
    }

    /**
     * Returns a collection of all Ranges.
     *
     * @internal
     *
     * @return \SplObjectStorage<\Rowbot\DOM\RangeBoundary, null>
     */
    public static function getRangeCollection()
    {
        if (!self::$collection) {
            self::$collection = new SplObjectStorage();
        }

        return self::$collection;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dom-range-createcontextualfragment
     */
    public function createContextualFragment(string $fragment): DocumentFragment
    {
        $node = $this->range->startNode;
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
                $this->range->startNode->getNodeDocument(),
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
     * Compares the position of two boundary points.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-range-bp-position
     *
     * @param array{0: \Rowbot\DOM\Node, 1: int} $boundaryPointA An array containing a Node and an offset within that
     *                                                           Node representing a boundary.
     * @param array{0: \Rowbot\DOM\Node, 1: int} $boundaryPointB An array containing a Node and an offset within that
     *                                                           Node representing a boundary.
     *
     * @return string Returns before, equal, or after based on the position of the first boundary relative to the second
     *                boundary.
     */
    private function computePosition(array $boundaryPointA, array $boundaryPointB): string
    {
        if ($boundaryPointA[0] === $boundaryPointB[0]) {
            if ($boundaryPointA[1] === $boundaryPointB[1]) {
                return 'equal';
            } elseif ($boundaryPointA[1] < $boundaryPointB[1]) {
                return 'before';
            } else {
                return 'after';
            }
        }

        $commonAncestor = Node::getCommonAncestor($boundaryPointB[0], $boundaryPointA[0]);

        if ($commonAncestor === $boundaryPointA[0]) {
            $AFollowsB = false;
        } elseif ($commonAncestor === $boundaryPointB[0]) {
            $AFollowsB = true;
        } else {
            // A was not found inside B. Traverse both A & B up to the nodes
            // before their common ancestor, then see if A is in the nextSibling
            // chain of B.
            $b = $boundaryPointB[0];

            while ($b->parentNode !== $commonAncestor) {
                /** @var \Rowbot\DOM\Node $b */
                $b = $b->parentNode;
            }

            $a = $boundaryPointA[0];

            while ($a->parentNode !== $commonAncestor) {
                /** @var \Rowbot\DOM\Node $a */
                $a = $a->parentNode;
            }

            $AFollowsB = false;

            while ($b) {
                if ($a === $b) {
                    $AFollowsB = true;

                    break;
                }

                $b = $b->nextSibling;
            }
        }

        if ($AFollowsB) {
            // Swap variables
            [$boundaryPointB, $boundaryPointA] = [$boundaryPointA, $boundaryPointB];
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

            while ($child) {
                if ($child->parentNode === $boundaryPointA[0]) {
                    break;
                }

                $child = $child->parentNode;
            }

            if ($child->getTreeIndex() < $boundaryPointA[1]) {
                return $AFollowsB ? 'before' : 'after';
            }
        }

        return $AFollowsB ? 'after' : 'before';
    }

    /**
     * Returns true if the entire Node is within the Range, otherwise false.
     *
     * @see https://dom.spec.whatwg.org/#contained
     */
    private function isFullyContainedNode(Node $node): bool
    {
        $startBP = array($this->range->startNode, $this->range->startOffset);
        $endBP = array($this->range->endNode, $this->range->endOffset);
        $root = $this->range->startNode->getRootNode();

        return $node->getRootNode() === $root
            && $this->computePosition([$node, 0], $startBP) === 'after'
            && $this->computePosition([$node, $node->getLength()], $endBP) === 'before';
    }

    /**
     * Returns true if only a portion of the Node is contained within the Range.
     *
     * @see https://dom.spec.whatwg.org/#partially-contained
     */
    private function isPartiallyContainedNode(Node $node): bool
    {
        $isAncestorOfStart = $node->contains($this->range->startNode);
        $isAncestorOfEnd = $node->contains($this->range->endNode);

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
     * @param string           $type   Which boundary point should be set. Valid values are start or end.
     * @param \Rowbot\DOM\Node $node   The Node that will become the boundary.
     * @param int              $offset The offset within the given Node that will be the boundary.
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError
     * @throws \Rowbot\DOM\Exception\InvalidNodeTypeError
     */
    private function setStartOrEnd(string $type, Node $node, int $offset): void
    {
        $offset = Utils::unsignedLong($offset);

        if ($node instanceof DocumentType) {
            throw new InvalidNodeTypeError();
        }

        if ($offset > $node->getLength()) {
            throw new IndexSizeError();
        }

        $bp = [$node, $offset];

        switch ($type) {
            case 'start':
                if (
                    $this->range->startNode->getRootNode() !== $node->getRootNode()
                    || $this->computePosition($bp, [$this->range->endNode, $this->range->endOffset]) === 'after'
                ) {
                    $this->range->endNode = $node;
                    $this->range->endOffset = $offset;
                }

                $this->range->startNode = $node;
                $this->range->startOffset = $offset;

                break;

            case 'end':
                if (
                    $this->range->startNode->getRootNode() !== $node->getRootNode()
                    || $this->computePosition($bp, [$this->range->startNode, $this->range->startOffset]) === 'before'
                ) {
                    $this->range->startNode = $node;
                    $this->range->startOffset = $offset;
                }

                $this->range->endNode = $node;
                $this->range->endOffset = $offset;
        }
    }
}
