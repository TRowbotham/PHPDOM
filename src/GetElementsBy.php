<?php
namespace Rowbot\DOM;

trait GetElementsBy
{
    /**
     * Returns a list of all the Element's that have all the given class names.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-getelementsbyclassname
     *
     * @param string $className A space delimited string containing the
     *     classNames to search for.
     *
     * @return Element[]
     */
    public function getElementsByClassName($className)
    {
        $classes = Utils::parseOrderedSet(
            Utils::DOMString($className)
        );
        $collection = [];

        if ($classes === '') {
            return $collection;
        }

        $nodeFilter = function ($node) use ($classes) {
            $hasClasses = false;

            foreach ($classes as $className) {
                if (!($hasClasses = $node->classList->contains($className))) {
                    break;
                }
            }

            return $hasClasses ?
                NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
        };
        $tw = new TreeWalker($this, NodeFilter::SHOW_ELEMENT, $nodeFilter);

        while ($node = $tw->nextNode()) {
            $collection[] = $node;
        }

        return $collection;
    }

    /**
     * Returns an array of Elements with the specified local name.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-getelementsbytagname
     *
     * @param string $localName The element's local name to search for. If
     *     given '*', all element decendants will be returned.
     *
     * @return Element[] A list of Elements with the specified local name.
     */
    public function getElementsByTagName($localName)
    {
        $collection = array();
        $localName = Utils::DOMString($localName);

        if (strcmp($localName, '*') === 0) {
            $nodeFilter = null;
        } elseif ($this instanceof Document) {
            $nodeFilter = function ($node) use ($localName) {
                $shouldAccept = ($node->namespaceURI === Namespaces::HTML &&
                    $node->localName === Utils::toASCIILowercase($localName)
                    ) ||
                    ($node->namespaceURI === Namespaces::HTML &&
                    $node->localName === $localName);

                if ($shouldAccept) {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_SKIP;
            };
        } else {
            $nodeFilter = function ($node) use ($localName) {
                return strcmp($node->localName, $localName) === 0 ?
                    NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
            };
        }

        $tw = new TreeWalker($this, NodeFilter::SHOW_ELEMENT, $nodeFilter);

        while ($node = $tw->nextNode()) {
            $collection[] = $node;
        }

        return $collection;
    }

    /**
     * Returns a collection of Elements that match the given namespace and local
     * name.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-getelementsbytagnamens
     *
     * @param string $namespace The namespaceURI to search for.  If both
     *     namespace and local name are given '*', all element decendants will
     *     be returned.  If only namespace is given '*' all element decendants
     *     matching only local name will be returned.
     *
     * @param string $localName The Element's local name to search for.  If
     *     both namespace and local name are given '*', all element decendants
     *     will be returned.  If only local name is given '*' all element
     *     decendants matching only namespace will be returned.
     *
     * @return Element[]
     */
    public function getElementsByTagNameNS($namespace, $localName)
    {
        $namespace = Utils::DOMString($namespace, false, true);
        $namespace = $namespace === '' ? null : $namespace;
        $localName = Utils::DOMString($localName);
        $collection = array();

        if (strcmp($namespace, '*') === 0 && strcmp($localName, '*') === 0) {
            $nodeFilter = null;
        } elseif (strcmp($namespace, '*') === 0) {
            $nodeFilter = function ($node) use ($localName) {
                return strcmp($node->localName, $localName) === 0 ?
                    NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
            };
        } elseif (strcmp($localName, '*') === 0) {
            $nodeFilter =  function ($node) use ($namespace) {
                return strcmp($node->namespaceURI, $namespace) === 0 ?
                    NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
            };
        }

        $tw = new TreeWalker($this, NodeFilter::SHOW_ELEMENT, $nodeFilter);

        while ($node = $tw->nextNode()) {
            $collection[] = $node;
        }

        return $collection;
    }
}
