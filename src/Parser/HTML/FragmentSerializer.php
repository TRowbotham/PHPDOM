<?php
namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\Attr;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Node;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\FragmentSerializerInterface;

class FragmentSerializer implements FragmentSerializerInterface
{
    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#serialising-html-fragments
     *
     * @param Element|Document|DocumentFragment $node
     * @param bool                              $requireWellFormed
     *
     * @return string
     */
    public function serializeFragment(
        Node $node,
        bool $requireWellFormed = false
    ): string {
        $s = '';

        // If the node is a template element, then let the node instead be the
        // template element's template contents (a DocumentFragment node).
        if ($node instanceof HTMLTemplateElement) {
            $node = $node->content;
        }

        foreach ($node->childNodes as $currentNode) {
            switch ($currentNode->nodeType) {
                case Node::ELEMENT_NODE:
                    switch ($currentNode->namespaceURI) {
                        case Namespaces::HTML:
                        case Namespaces::MATHML:
                        case Namespaces::SVG:
                            $tagname = $currentNode->localName;

                            break;

                        default:
                            $tagname = $currentNode->tagName;
                    }

                    $s .= '<' . $tagname;

                    foreach ($currentNode->getAttributeList() as $attr) {
                        $s .= ' ' . $this->serializeContentAttributeName($attr);
                        $s .= '="' . $this->escapeHTMLString(
                            $attr->value,
                            true
                        ) . '"';
                    }

                    $s .= '>';
                    $localName = $currentNode->localName;

                    // If the current node's local name is a known void element,
                    // then move on to current node's next sibling, if any.
                    if (\preg_match(self::VOID_TAGS, $localName)) {
                        continue 2;
                    }

                    $s .= $this->serializeFragment($currentNode);
                    $s .= '</' . $tagname . '>';

                    break;

                case Node::TEXT_NODE:
                    $localName = $currentNode->parentNode->localName;

                    if ($localName === 'style' ||
                        $localName === 'script' ||
                        $localName === 'xmp' ||
                        $localName === 'iframe' ||
                        $localName === 'noembed' ||
                        $localName === 'noframes' ||
                        $localName === 'plaintext' ||
                        $localName === 'noscript'
                    ) {
                        $s .= $currentNode->data;
                    } else {
                        $s .= $this->escapeHTMLString($currentNode->data);
                    }

                    break;

                case Node::COMMENT_NODE:
                    $s .= '<!--' . $currentNode->data . '-->';

                    break;

                case Node::PROCESSING_INSTRUCTION_NODE:
                    $s .= '<?' . $currentNode->target . ' ' .
                        $currentNode->data . '>';

                    break;

                case Node::DOCUMENT_TYPE_NODE:
                    $s .= '<!DOCTYPE ' . $currentNode->name . '>';

                    break;
            }
        }

        return $s;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#escapingString
     *
     * @param string $string The input string to be escaped.
     *
     * @return string The escaped input string.
     */
    private function escapeHTMLString($string, $inAttributeMode = false): string
    {
        if ($string === '') {
            return '';
        }

        $search = ['&', "\xC2\xA0"];
        $replace = ['&amp;', '&nbsp;'];

        if ($inAttributeMode) {
            $search[] = '"';
            $replace[] = '&quot;';
        } else {
            $search += ['<', '>'];
            $replace += ['&lt;', '&gt;'];
        }

        return \str_replace($search, $replace, $string);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#attribute's-serialised-name
     *
     * @param Attr $attr The attribute whose name is to be serialized.
     *
     * @return string The attribute's serialized name.
     */
    private function serializeContentAttributeName(Attr $attr): string
    {
        $namespace = $attr->namespaceURI;

        if ($namespace === null) {
            return $attr->localName;
        }

        if ($namespace === Namespaces::XML) {
            return 'xml:' . $attr->localName;
        }

        if ($namespace === Namespaces::XMLNS) {
            $localName = $attr->localName;

            if ($localName === 'xmlns') {
                return 'xmlns';
            }

            return 'xmlns:' . $localName;
        }

        if ($namespace === Namespaces::XLINK) {
            return 'xlink:' . $attr->localName;
        }

        return $attr->name;
    }
}
