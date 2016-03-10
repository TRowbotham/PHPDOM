<?php
namespace phpjs;

/**
 * @see https://dom.spec.whatwg.org/#treewalker
 * @see https://developer.mozilla.org/en-US/docs/Web/API/TreeWalker
 */
final class TreeWalker
{
    private $mCurrentNode;
    private $mFilter;
    private $mRoot;
    private $mWhatToShow;

    public function __construct(
        Node $aRoot,
        $aWhatToShow = NodeFilter::SHOW_ALL,
        callable $aFilter = null
    ) {
        $this->mCurrentNode = $aRoot;
        $this->mFilter = $aFilter;
        $this->mRoot = $aRoot;
        $this->mWhatToShow = $aWhatToShow;
    }

    public function __destruct()
    {
        $this->mCurrentNode = null;
        $this->mFilter = null;
        $this->mRoot = null;
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'currentNode':
                return $this->mCurrentNode;

            case 'filter':
                return $this->mFilter;

            case 'root':
                return $this->mRoot;

            case 'whatToShow':
                return $this->mWhatToShow;
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'currentNode':
                if ($aValue instanceof Node) {
                    $this->mCurrentNode = $aValue;
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
        $node = $this->mCurrentNode;
        $result = NodeFilter::FILTER_ACCEPT;

        while (true) {
            while (
                $result != NodeFilter::FILTER_REJECT &&
                $node->hasChildNodes()
            ) {
                $node = $node->firstChild;
                $result = NodeFilter::_filter($node, $this);

                if ($result == NodeFilter::FILTER_ACCEPT) {
                    $this->mCurrentNode = $node;

                    return $node;
                }
            }

            $sibling = null;
            $temp = $node;

            do {
                if ($temp === $this->mRoot) {
                    break;
                }

                $sibling = $temp->nextSibling;

                if ($sibling) {
                    break;
                }

                $temp = $temp->parentNode;
            } while($temp);

            if (!$sibling) {
                break;
            }

            $node = $sibling;
            $result = NodeFilter::_filter($node, $this);

            if ($result == NodeFilter::FILTER_ACCEPT) {
                $this->mCurrentNode = $node;

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
        $node = $this->mCurrentNode;

        while ($node && $node !== $this->mRoot) {
            $node = $node->parentNode;

            if (
                $node &&
                NodeFilter::_filter($node, $this) == NodeFilter::FILTER_ACCEPT
            ) {
                $this->mCurrentNode = $node;

                return $node;
            }
        }

        return null;
    }

    public function previousNode()
    {
        $node = $this->mCurrentNode;

        while ($node !== $this->mRoot) {
            $sibling = $node->previousSibling;

            while ($sibling) {
                $node = $sibling;
                $result = NodeFilter::_filter($node, $this);

                while (
                    $result != NodeFilter::FILTER_REJECT &&
                    ($lastChild = $node->lastChild)
                ) {
                    $node = $lastChild;
                    $result = NodeFilter::_filter($node, $this);
                }

                if ($result == NodeFilter::FILTER_ACCEPT) {
                    $this->mCurrentNode = $node;

                    return $node;
                }

                $sibling = $node->previousSibling;
            }

            if ($node === $this->mRoot || !($node = $node->parentNode)) {
                return null;
            }

            $nodeFilter = NodeFilter::_filter($node, $this);

            if ($nodeFilter == NodeFilter::FILTER_ACCEPT) {
                $this->mCurrentNode = $node;

                return $node;
            }
        }

        return null;
    }

    public function previousSibling()
    {
        return $this->traverseSiblings('previous');
    }

    private function traverseChildren($aType)
    {
        $node = $this->mCurrentNode;
        $node = $aType == 'first' ? $node->firstChild : $node->lastChild;

        if (!$node) {
            return null;
        }

        while (true) {
            $result = NodeFilter::_filter($node, $this);

            switch ($result) {
                case NodeFilter::FILTER_ACCEPT:
                    $this->mCurrentNode = $node;

                    return $node;

                case NodeFilter::FILTER_SKIP:
                    $child = $aType == 'first' ?
                        $node->firstChild : $node->lastChild;

                    if ($child) {
                        $node = $child;
                        continue 2;
                    }
            }

            while (true) {
                $sibling = $aType == 'first' ?
                    $node->nextSibling : $node->previousSibling;

                if ($sibling) {
                    $node = $sibling;
                    continue 2;
                }

                $parent = $node->parentNode;

                if (
                    !$parent ||
                    $parent === $this->mRoot ||
                    $parent === $this->mCurrentNode
                ) {
                    return null;
                }

                $node = $parent;
            }
        }
    }

    private function traverseSiblings($aType)
    {
        $node = $this->mCurrentNode;

        if ($node === $this->mRoot) {
            return null;
        }

        while (true) {
            $sibling = $aType == 'next' ?
                $node->nextSibling : $node->previousSibling;

            while ($sibling) {
                $node = $sibling;
                $result = NodeFilter::_filter($node, $this);

                if ($result == NodeFilter::FILTER_ACCEPT) {
                    $this->mCurrentNode = $node;

                    return $node;
                }

                $sibling = $aType == 'next' ?
                    $node->firstChild : $node->lastChild;

                if ($result == NodeFilter::FILTER_REJECT || !$sibling) {
                    $sibling = $aType == 'next' ?
                        $node->nextSibling : $node->previousSibling;
                }
            }

            $node = $node->parentNode;

            if (!$node || $node === $this->mRoot) {
                return null;
            }

            $nodeFilter = NodeFilter::_filter($node, $this);

            if ($nodeFilter == NodeFilter::FILTER_ACCEPT) {
                return null;
            }
        }
    }
}
