<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\SVG\SVGDescElement;
use Rowbot\DOM\Element\SVG\SVGForeignObjectElement;
use Rowbot\DOM\Element\SVG\SVGTitleElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Utils;
use SplObjectStorage;

use function assert;

trait IntegrationPointTrait
{
    /**
     * A node is an HTML integration point if it is one of the following:
     *
     *     - A MathML annotation-xml element whose start tag token had an
     *     attribute with the name "encoding" whose value was an ASCII
     *     case-insensitive match for the string "text/html".
     *
     *     - A MathML annotation-xml element whose start tag token had an
     *     attribute with the name "encoding" whose value was an ASCII
     *     case-insensitive match for the string "application/xhtml+xml".
     *
     *     - An SVG foreignObject element.
     *
     *     - An SVG desc element.
     *
     *     - An SVG title element.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#html-integration-point
     *
     * @param \SplObjectStorage<\Rowbot\DOM\Element\Element, \Rowbot\DOM\Parser\Token\TagToken> $elementTokenMap
     */
    private function isHTMLIntegrationPoint(Node $node, SplObjectStorage $elementTokenMap): bool
    {
        if (!$node instanceof Element) {
            return false;
        }

        if ($node->namespaceURI === Namespaces::MATHML) {
            if ($node->localName !== 'annotation-xml') {
                return false;
            }

            assert(isset($elementTokenMap[$node]));

            foreach ($elementTokenMap[$node]->attributes as $attr) {
                if ($attr->name === 'encoding') {
                    $value = Utils::toASCIILowercase($attr->value);

                    if ($value === 'text/html' || $value === 'application/xhtml+xml') {
                        return true;
                    }
                }
            }
        } elseif (
            $node instanceof SVGForeignObjectElement
            || $node instanceof SVGDescElement
            || $node instanceof SVGTitleElement
        ) {
            return true;
        }

        return false;
    }

    /**
     * A node is a MathML text integration point if it is one of the following
     * elements:
     *     - A MathML mi element.
     *     - A MathML mo element.
     *     - A MathML mn element.
     *     - A MathML ms element.
     *     - A MathML mtext element.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#mathml-text-integration-point
     */
    private function isMathMLTextIntegrationPoint(Node $node): bool
    {
        if ($node instanceof Element && $node->namespaceURI === Namespaces::MATHML) {
            $localName = $node->localName;

            return $localName === 'mi'
                || $localName === 'mo'
                || $localName === 'mn'
                || $localName === 'ms'
                || $localName === 'mtext';
        }

        return false;
    }
}
