<?php
namespace phpjs;

trait GetElementsBy
{
    /**
     * Returns a list of all the Element's that have all the given class names.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-getelementsbyclassname
     *
     * @param string $aClassName A space delimited string containing the
     *     classNames to search for.
     *
     * @return Element[]
     */
    public function getElementsByClassName($aClassName)
    {
        $classes = Utils::parseOrderedSet(
            Utils::DOMString($aClassName)
        };

        if (empty($classes)) {
            return $classes;
        }

        $nodeFilter = function ($aNode) use ($classes) {
            $hasClasses = false;

            foreach ($classes as $className) {
                if (!($hasClasses = $aNode->classList->contains($className))) {
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
     * @param string $aLocalName The element's local name to search for. If
     *     given '*', all element decendants will be returned.
     *
     * @return Element[] A list of Elements with the specified local name.
     */
    public function getElementsByTagName($aLocalName)
    {
        $collection = array();
        $localName = Utils::DOMString($aLocalName);

        if (strcmp($localName, '*') === 0) {
            $nodeFilter = null;
        } elseif ($this instanceof Document) {
            $nodeFilter = function ($aNode) use ($localName) {
                if (
                    ($aNode->namespaceURI === Namespaces::HTML &&
                    strcmp($aNode->localName, strtolower($localName)) === 0) ||
                    ($aNode->namespaceURI === Namespaces::HTML &&
                    strcmp($aNode->localName, $localName) === 0)
                ) {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_SKIP;
            };
        } else {
            $nodeFilter = function ($aNode) use ($localName) {
                return strcmp($aNode->localName, $localName) === 0 ?
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
     * @param string $aNamespace The namespaceURI to search for.  If both
     *     namespace and local name are given '*', all element decendants will
     *     be returned.  If only namespace is given '*' all element decendants
     *     matching only local name will be returned.
     *
     * @param string $aLocalName The Element's local name to search for.  If
     *     both namespace and local name are given '*', all element decendants
     *     will be returned.  If only local name is given '*' all element
     *     decendants matching only namespace will be returned.
     *
     * @return Element[]
     */
    public function getElementsByTagNameNS($aNamespace, $aLocalName)
    {
        $namespace = Utils::DOMString($aNamespace, false, true);
        $namespace = $namespace === '' ? null : $namespace;
        $localName = Utils::DOMString($aLocalName);
        $collection = array();

        if (strcmp($namespace, '*') === 0 && strcmp($localName, '*') === 0) {
            $nodeFilter = null;
        } elseif (strcmp($namespace, '*') === 0) {
            $nodeFilter = function ($aNode) use ($localName) {
                return strcmp($aNode->localName, $localName) === 0 ?
                    NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
            };
        } elseif (strcmp($localName, '*') === 0) {
            $nodeFilter =  function ($aNode) use ($namespace) {
                return strcmp($aNode->namespaceURI, $namespace) === 0 ?
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
