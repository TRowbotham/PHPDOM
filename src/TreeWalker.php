<?php
declare(strict_types=1);

namespace Rowbot\DOM;

use function is_callable;

/**
 * @see https://dom.spec.whatwg.org/#treewalker
 * @see https://developer.mozilla.org/en-US/docs/Web/API/TreeWalker
 *
 * @property-read \Rowbot\DOM\Node                     $root
 * @property-read int                                  $whatToShow
 * @property-read \Rowbot\DOM\NodeFilter|callable|null $filter
 * @property      \Rowbot\DOM\Node                     $currentNode
 */
final class TreeWalker
{
    use NodeFilterUtils;

    /**
     * @var \Rowbot\DOM\Node
     */
    private $currentNode;

    /**
     * @var \Rowbot\DOM\NodeFilter|callable|null
     */
    private $filter;

    /**
     * @var \Rowbot\DOM\Node
     */
    private $root;

    /**
     * @var int
     */
    private $whatToShow;

    /**
     * Construct.
     *
     * @param \Rowbot\DOM\Node                     $root
     * @param int                                  $whatToShow
     * @param \Rowbot\DOM\NodeFilter|callable|null $filter
     *
     * @return void
     */
    public function __construct(
        Node $root,
        int $whatToShow = NodeFilter::SHOW_ALL,
        $filter = null
    ) {
        $this->currentNode = $root;

        if ($filter instanceof NodeFilter || is_callable($filter)) {
            $this->filter = $filter;
        }

        $this->root = $root;
        $this->whatToShow = $whatToShow;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'currentNode':
                return $this->currentNode;

            case 'filter':
                return $this->filter;

            case 'root':
                return $this->root;

            case 'whatToShow':
                return $this->whatToShow;
        }
    }

    /**
     * @param string           $name
     * @param \Rowbot\DOM\Node $value
     */
    public function __set(string $name, Node $value)
    {
        switch ($name) {
            case 'currentNode':
                if ($value instanceof Node) {
                    $this->currentNode = $value;
                }
        }
    }

    /**
     * Gets the first child node.
     *
     * @see https://dom.spec.whatwg.org/#dom-treewalker-firstchild
     *
     * @return \Rowbot\DOM\Node|null
     */
    public function firstChild(): ?Node
    {
        return $this->traverseChildren('first');
    }

    /**
     * Gets the last child node.
     *
     * @see https://dom.spec.whatwg.org/#dom-treewalker-lastchild
     *
     * @return \Rowbot\DOM\Node|null
     */
    public function lastChild(): ?Node
    {
        return $this->traverseChildren('last');
    }

    /**
     * Gets the next node.
     *
     * @see https://dom.spec.whatwg.org/#dom-treewalker-nextnode
     *
     * @return \Rowbot\DOM\Node|null
     */
    public function nextNode(): ?Node
    {
        $node = $this->currentNode;
        $result = NodeFilter::FILTER_ACCEPT;

        while (true) {
            while ($result != NodeFilter::FILTER_REJECT
                && $node->hasChildNodes()
            ) {
                $node = $node->firstChild;
                $result = $this->filterNode($node);

                if ($result == NodeFilter::FILTER_ACCEPT) {
                    $this->currentNode = $node;

                    return $node;
                }
            }

            $sibling = null;
            $temp = $node;

            while ($temp !== null) {
                if ($temp === $this->root) {
                    return null;
                }

                $sibling = $temp->nextSibling;

                if ($sibling !== null) {
                    $node = $sibling;

                    break;
                }

                $temp = $temp->parentNode;
            }

            $result = $this->filterNode($node);

            if ($result == NodeFilter::FILTER_ACCEPT) {
                $this->currentNode = $node;

                return $node;
            }
        }
    }

    /**
     * Gets the next sibling node.
     *
     * @see https://dom.spec.whatwg.org/#dom-treewalker-nextsibling
     *
     * @return \Rowbot\DOM\Node|null
     */
    public function nextSibling(): ?Node
    {
        return $this->traverseSiblings('next');
    }

    /**
     * Gets the parent node.
     *
     * @see https://dom.spec.whatwg.org/#dom-treewalker-parentnode
     *
     * @return \Rowbot\DOM\Node|null
     */
    public function parentNode(): ?Node
    {
        $node = $this->currentNode;

        while ($node && $node !== $this->root) {
            $node = $node->parentNode;

            if ($node &&
                $this->filterNode($node) == NodeFilter::FILTER_ACCEPT
            ) {
                $this->currentNode = $node;

                return $node;
            }
        }

        return null;
    }

    /**
     * Gets the previous node.
     *
     * @see https://dom.spec.whatwg.org/#dom-treewalker-previousnode
     *
     * @return \Rowbot\DOM\Node|null
     */
    public function previousNode(): ?Node
    {
        $node = $this->currentNode;

        while ($node !== $this->root) {
            $sibling = $node->previousSibling;

            while ($sibling) {
                $node = $sibling;
                $result = $this->filterNode($node);

                while ($result != NodeFilter::FILTER_REJECT
                    && ($lastChild = $node->lastChild)
                ) {
                    $node = $lastChild;
                    $result = $this->filterNode($node);
                }

                if ($result == NodeFilter::FILTER_ACCEPT) {
                    $this->currentNode = $node;

                    return $node;
                }

                $sibling = $node->previousSibling;
            }

            if ($node === $this->root || !($node = $node->parentNode)) {
                return null;
            }

            $nodeFilter = $this->filterNode($node);

            if ($nodeFilter == NodeFilter::FILTER_ACCEPT) {
                $this->currentNode = $node;

                return $node;
            }
        }

        return null;
    }

    /**
     * Gets the previous sibling node.
     *
     * @see https://dom.spec.whatwg.org/#dom-treewalker-previoussibling
     *
     * @return \Rowbot\DOM\Node|null
     */
    public function previousSibling(): ?Node
    {
        return $this->traverseSiblings('previous');
    }

    /**
     * Traverses a tree of nodes.
     *
     * @see https://dom.spec.whatwg.org/#concept-traverse-children
     *
     * @param string $type
     *
     * @return \Rowbot\DOM\Node|null
     */
    private function traverseChildren(string $type): ?Node
    {
        $node = $this->currentNode;
        $node = $type == 'first' ? $node->firstChild : $node->lastChild;

        while ($node !== null) {
            $result = $this->filterNode($node);

            switch ($result) {
                case NodeFilter::FILTER_ACCEPT:
                    $this->currentNode = $node;

                    return $node;

                case NodeFilter::FILTER_SKIP:
                    $child = $type == 'first'
                        ? $node->firstChild
                        : $node->lastChild;

                    if ($child !== null) {
                        $node = $child;
                        continue 2;
                    }
            }

            while ($node !== null) {
                $sibling = $type == 'first'
                    ? $node->nextSibling
                    : $node->previousSibling;

                if ($sibling) {
                    $node = $sibling;
                    break;
                }

                $parent = $node->parentNode;

                if ($parent === null ||
                    $parent === $this->root ||
                    $parent === $this->currentNode
                ) {
                    return null;
                }

                $node = $parent;
            }
        }

        return null;
    }

    /**
     * Traverses a tree of nodes.
     *
     * @see https://dom.spec.whatwg.org/#concept-traverse-siblings
     *
     * @param string $type
     *
     * @return \Rowbot\DOM\Node|null
     */
    private function traverseSiblings(string $type): ?Node
    {
        $node = $this->currentNode;

        if ($node === $this->root) {
            return null;
        }

        while (true) {
            $sibling = $type == 'next'
                ? $node->nextSibling
                : $node->previousSibling;

            while ($sibling) {
                $node = $sibling;
                $result = $this->filterNode($node);

                if ($result == NodeFilter::FILTER_ACCEPT) {
                    $this->currentNode = $node;

                    return $node;
                }

                $sibling = $type == 'next'
                    ? $node->firstChild
                    : $node->lastChild;

                if ($result == NodeFilter::FILTER_REJECT || !$sibling) {
                    $sibling = $type == 'next'
                        ? $node->nextSibling
                        : $node->previousSibling;
                }
            }

            $node = $node->parentNode;

            if (!$node || $node === $this->root) {
                return null;
            }

            $nodeFilter = $this->filterNode($node);

            if ($nodeFilter == NodeFilter::FILTER_ACCEPT) {
                return null;
            }
        }
    }
}
