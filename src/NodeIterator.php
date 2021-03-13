<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#nodeiterator
 * @see https://developer.mozilla.org/en-US/docs/Web/API/NodeIterator
 *
 * @property-read \Rowbot\DOM\Node                     $root
 * @property-read \Rowbot\DOM\Node                     $referenceNode
 * @property-read bool                                 $pointerBeforeReferenceNode
 * @property-read int                                  $whatToShow
 * @property-read \Rowbot\DOM\NodeFilter|callable|null $filter
 */
final class NodeIterator
{
    use NodeFilterUtils;

    /**
     * @var \Rowbot\DOM\NodeIteratorContext
     */
    private $context;

    /**
     * @param \Rowbot\DOM\NodeFilter|callable|null $filter
     */
    public function __construct(Node $root, int $whatToShow = NodeFilter::SHOW_ALL, $filter = null)
    {
        $this->context = new NodeIteratorContext($root);
        $this->setFilter($filter);
        $this->whatToShow = $whatToShow;
        NodeIteratorContext::getIterators()->attach($this->context);
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'filter':
                return $this->filter;

            case 'pointerBeforeReferenceNode':
                return $this->context->pointerBeforeReferenceNode;

            case 'referenceNode':
                return $this->context->referenceNode;

            case 'root':
                return $this->context->root;

            case 'whatToShow':
                return $this->whatToShow;
        }
    }

    public function __destruct()
    {
        NodeIteratorContext::getIterators()->detach($this->context);
    }

    /**
     * Returns the next node in the iterator.
     *
     * @see https://dom.spec.whatwg.org/#dom-nodeiterator-nextnode
     */
    public function nextNode(): ?Node
    {
        return $this->traverse('next');
    }

    /**
     * Returns the previous node in the iterator.
     *
     * @see https://dom.spec.whatwg.org/#dom-nodeiterator-previousnode
     */
    public function previousNode(): ?Node
    {
        return $this->traverse('previous');
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-nodeiterator-traverse
     */
    private function traverse(string $direction): ?Node
    {
        $node = $this->context->referenceNode;
        $beforeNode = $this->context->pointerBeforeReferenceNode;

        while (true) {
            switch ($direction) {
                case 'next':
                    if (!$beforeNode) {
                        $firstChild = $node->firstChild;

                        if ($firstChild) {
                            $node = $firstChild;

                            break;
                        }

                        $sibling = null;
                        $temp = $node;

                        do {
                            if ($temp === $this->root) {
                                break;
                            }

                            $sibling = $temp->nextSibling;

                            if ($sibling) {
                                break;
                            }

                            $temp = $temp->parentNode;
                        } while ($temp);

                        if (!$sibling) {
                            return null;
                        }

                        $node = $sibling;
                    } else {
                        $beforeNode = false;
                    }

                    break;

                case 'previous':
                    if ($beforeNode) {
                        $sibling = $node->previousSibling;

                        if ($sibling) {
                            $node = $sibling;

                            while (($lastChild = $node->lastChild)) {
                                $node = $lastChild;
                            }
                        }

                        if ($this->context->referenceNode === $this->root || !($node = $node->parentNode)) {
                            return null;
                        }
                    } else {
                        $beforeNode = true;
                    }
            }

            $result = $this->filterNode($node);

            if ($result === NodeFilter::FILTER_ACCEPT) {
                break;
            }
        }

        $this->context->referenceNode = $node;
        $this->context->pointerBeforeReferenceNode = $beforeNode;

        return $node;
    }
}
