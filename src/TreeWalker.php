<?php
namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#treewalker
 * @see https://developer.mozilla.org/en-US/docs/Web/API/TreeWalker
 */
final class TreeWalker
{
    use NodeFilterUtils;

    private $currentNode;
    private $filter;
    private $root;
    private $whatToShow;

    public function __construct(
        Node $root,
        $whatToShow = NodeFilter::SHOW_ALL,
        $filter = null
    ) {
        $this->currentNode = $root;
        $this->filter = null;

        if ($filter instanceof NodeFilter || is_callable($filter)) {
            $this->filter = $filter;
        }

        $this->filter = $filter;
        $this->root = $root;
        $this->whatToShow = $whatToShow;
    }

    public function __get($name)
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

    public function __set($name, $value)
    {
        switch ($name) {
            case 'currentNode':
                if ($value instanceof Node) {
                    $this->currentNode = $value;
                }
        }
    }

    public function firstChild()
    {
        return $this->traverseChildren('first');
    }

    public function lastChild()
    {
        return $this->traverseChildren('last');
    }

    public function nextNode()
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
                break;
            }

            $node = $sibling;
            $result = $this->filterNode($node);

            if ($result == NodeFilter::FILTER_ACCEPT) {
                $this->currentNode = $node;

                return $node;
            }
        }

        return null;
    }

    public function nextSibling()
    {
        return $this->traverseSiblings('next');
    }

    public function parentNode()
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

    public function previousNode()
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

    public function previousSibling()
    {
        return $this->traverseSiblings('previous');
    }

    private function traverseChildren($type)
    {
        $node = $this->currentNode;
        $node = $type == 'first' ? $node->firstChild : $node->lastChild;

        if (!$node) {
            return null;
        }

        while (true) {
            $result = $this->filterNode($node);

            switch ($result) {
                case NodeFilter::FILTER_ACCEPT:
                    $this->currentNode = $node;

                    return $node;

                case NodeFilter::FILTER_SKIP:
                    $child = $type == 'first'
                        ? $node->firstChild
                        : $node->lastChild;

                    if ($child) {
                        $node = $child;
                        continue 2;
                    }
            }

            while (true) {
                $sibling = $type == 'first'
                    ? $node->nextSibling
                    : $node->previousSibling;

                if ($sibling) {
                    $node = $sibling;
                    continue 2;
                }

                $parent = $node->parentNode;

                if (
                    !$parent ||
                    $parent === $this->root ||
                    $parent === $this->currentNode
                ) {
                    return null;
                }

                $node = $parent;
            }
        }
    }

    private function traverseSiblings($type)
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

                $sibling = $type == 'next' ?
                    $node->firstChild : $node->lastChild;

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
