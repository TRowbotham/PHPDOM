<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Generator;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Support\Collection\StringSet;

use function array_map;
use function in_array;

trait GetElementsBy
{
    /**
     * Returns a list of all the Element's that have all the given class names.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-getelementsbyclassname
     *
     * @param string $classNames A space delimited string containing the classNames to search for.
     *
     * @return \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\Element>
     */
    public function getElementsByClassName(string $classNames): HTMLCollection
    {
        // 1. Let classes be the result of running the ordered set parser on classNames.
        $classes = StringSet::createFromString($classNames);

        // NOTE: When invoked with the same argument, the same HTMLCollection object may be returned
        // as returned by an earlier call.
        return new HTMLCollection($this, static function (self $root) use ($classes): Generator {
            // 2. If classes is the empty set, return an empty HTMLCollection.
            if ($classes->isEmpty()) {
                return;
            }

            $isQuirksMode = $root->nodeDocument->getMode() === DocumentMode::QUIRKS;
            $mapToASCIILowercase = static function (string $className): string {
                return Utils::toASCIILowercase($className);
            };

            if ($isQuirksMode) {
                $classes = array_map($mapToASCIILowercase, $classes->all());
            }

            // 3. Return a HTMLCollection rooted at root, whose filter matches descendant elements
            // that have all their classes in classes.
            $walker = new TreeWalker(
                $root,
                NodeFilter::SHOW_ELEMENT,
                static function (Element $node) use (
                    $isQuirksMode,
                    $mapToASCIILowercase,
                    $classes
                ): int {
                    // NOTE: The comparisons for the classes must be done in an ASCII
                    // case-insensitive manner if root’s node document’s mode is "quirks", and in an
                    // identical to manner otherwise.
                    if ($isQuirksMode) {
                        $nodeClasses = array_map(
                            $mapToASCIILowercase,
                            StringSet::createFromString($node->className)->all()
                        );

                        foreach ($classes as $className) {
                            if (!in_array($className, $nodeClasses, true)) {
                                return NodeFilter::FILTER_SKIP;
                            }
                        }
                    } else {
                        foreach ($classes as $className) {
                            if (!$node->classList->contains($className)) {
                                return NodeFilter::FILTER_SKIP;
                            }
                        }
                    }

                    return NodeFilter::FILTER_ACCEPT;
                }
            );

            while (($element = $walker->nextNode()) !== null) {
                yield $element;
            }
        });
    }

    /**
     * Returns an array of Elements with the specified local name.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-getelementsbytagname
     *
     * @param string $qualifiedName The element's local name to search for. If given '*', all element decendants will be
     *                              returned.
     *
     * @return \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\Element> A list of Elements with the specified local name.
     */
    public function getElementsByTagName(string $qualifiedName): HTMLCollection
    {
        // 3. Otherwise, return a HTMLCollection rooted at root, whose filter matches descendant
        // elements whose qualified name is qualifiedName.
        $filter = static function (Element $element) use ($qualifiedName): int {
            return $qualifiedName === $element->getQualifiedName()
                ? NodeFilter::FILTER_ACCEPT
                : NodeFilter::FILTER_SKIP;
        };

        // 1. If qualifiedName is "*" (U+002A), return a HTMLCollection rooted at root, whose filter
        // matches only descendant elements.
        if ($qualifiedName === '*') {
            $filter = null;

        //Otherwise, if root’s node document is an HTML document, return a HTMLCollection rooted
        // at root, whose filter matches the following descendant elements:
        } elseif ($this->nodeDocument instanceof HTMLDocument) {
            $filter = static function (Element $element) use ($qualifiedName): int {
                $isHTMLNamespace = $element->namespaceURI === Namespaces::HTML;
                $qName = $element->getQualifiedName();

                // - Whose namespace is the HTML namespace and whose qualified name is
                //   qualifiedName, in ASCII lowercase.
                // - Whose namespace is not the HTML namespace and whose qualified name is
                //   qualifiedName.
                if (
                    ($isHTMLNamespace && $qName === Utils::toASCIILowercase($qualifiedName))
                    || (!$isHTMLNamespace && $qName === $qualifiedName)
                ) {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_SKIP;
            };
        }

        // NOTE: When invoked with the same argument, and as long as root’s node document’s type has
        // not changed, the same HTMLCollection object may be returned as returned by an earlier
        // call.
        return new HTMLCollection($this, static function (self $root) use ($filter): Generator {
            $walker = new TreeWalker($root, NodeFilter::SHOW_ELEMENT, $filter);

            while (($element = $walker->nextNode()) !== null) {
                yield $element;
            }
        });
    }

    /**
     * Returns a collection of Elements that match the given namespace and local
     * name.
     *
     * @see https://dom.spec.whatwg.org/#dom-document-getelementsbytagnamens
     *
     * @param string $namespace The namespaceURI to search for. If both namespace and local name are given '*', all
     *                          element decendants will be returned. If only namespace is given '*' all element
     *                          decendants matching only local name will be returned.
     * @param string $localName The Element's local name to search for. If both namespace and local name are given '*',
     *                          all element decendants will be returned.  If only local name is given '*' all element
     *                          decendants matching only namespace will be returned.
     *
     * @return \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\Element>
     */
    public function getElementsByTagNameNS(?string $namespace, string $localName): HTMLCollection
    {
        // 1. If namespace is the empty string, set it to null.
        if ($namespace === '') {
            $namespace = null;
        }

        // 5. Otherwise, return a HTMLCollection rooted at root, whose filter matches descendant
        // elements whose namespace is namespace and local name is localName.
        $filter = static function (Element $element) use ($namespace, $localName): int {
            return $element->namespaceURI === $namespace && $element->localName === $localName
                ? NodeFilter::FILTER_ACCEPT
                : NodeFilter::FILTER_SKIP;
        };

        // 2. If both namespace and localName are "*" (U+002A), return a HTMLCollection rooted at
        // root, whose filter matches descendant elements.
        if ($namespace === '*' && $localName === '*') {
            $filter = null;

        // 3. Otherwise, if namespace is "*" (U+002A), return a HTMLCollection rooted at root, whose
        // filter matches descendant elements whose local name is localName.
        } elseif ($namespace === '*') {
            $filter = static function (Element $element) use ($localName): int {
                return $element->localName === $localName
                    ? NodeFilter::FILTER_ACCEPT
                    : NodeFilter::FILTER_SKIP;
            };

        // 4. Otherwise, if localName is "*" (U+002A), return a HTMLCollection rooted at root, whose
        // filter matches descendant elements whose namespace is namespace.
        } elseif ($localName === '*') {
            $filter = static function (Element $element) use ($namespace): int {
                return $element->namespaceURI === $namespace
                    ? NodeFilter::FILTER_ACCEPT
                    : NodeFilter::FILTER_SKIP;
            };
        }

        // NOTE: When invoked with the same arguments, the same HTMLCollection object may be
        // returned as returned by an earlier call.
        return new HTMLCollection($this, static function (self $root) use ($filter): Generator {
            $walker = new TreeWalker($root, NodeFilter::SHOW_ELEMENT, $filter);

            while (($element = $walker->nextNode()) !== null) {
                yield $element;
            }
        });
    }
}
