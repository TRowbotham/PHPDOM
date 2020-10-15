<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\Attr;
use Rowbot\DOM\Comment;
use Rowbot\DOM\DocumentType;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Parser\FragmentSerializerInterface;
use Rowbot\DOM\ProcessingInstruction;
use Rowbot\DOM\Text;

use function preg_match;
use function str_replace;

class FragmentSerializer implements FragmentSerializerInterface
{
    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#serialising-html-fragments
     *
     * @param \Rowbot\DOM\Element\Element|\Rowbot\DOM\Document|\Rowbot\DOM\DocumentFragment $node
     */
    public function serializeFragment(Node $node, bool $requireWellFormed = false): string
    {
        $s = '';

        // If the node is a template element, then let the node instead be the
        // template element's template contents (a DocumentFragment node).
        if ($node instanceof HTMLTemplateElement) {
            $node = $node->content;
        }

        foreach ($node->childNodes as $currentNode) {
            if ($currentNode instanceof Element) {
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
                    $s .= '="' . $this->escapeHTMLString($attr->getValue(), true) . '"';
                }

                $s .= '>';
                $localName = $currentNode->localName;

                // If the current node's local name is a known void element,
                // then move on to current node's next sibling, if any.
                if (preg_match(self::VOID_TAGS, $localName)) {
                    continue;
                }

                $s .= $this->serializeFragment($currentNode);
                $s .= '</' . $tagname . '>';
            } elseif ($currentNode instanceof Text) {
                $localName = $currentNode->parentNode->localName;

                if (
                    $localName === 'style'
                    || $localName === 'script'
                    || $localName === 'xmp'
                    || $localName === 'iframe'
                    || $localName === 'noembed'
                    || $localName === 'noframes'
                    || $localName === 'plaintext'
                    || $localName === 'noscript'
                ) {
                    $s .= $currentNode->data;
                } else {
                    $s .= $this->escapeHTMLString($currentNode->data);
                }
            } elseif ($currentNode instanceof Comment) {
                $s .= '<!--' . $currentNode->data . '-->';
            } elseif ($currentNode instanceof ProcessingInstruction) {
                $s .= '<?' . $currentNode->target
                    . ' '
                    . $currentNode->data
                    . '>';
            } elseif ($currentNode instanceof DocumentType) {
                $s .= '<!DOCTYPE ' . $currentNode->name . '>';
            }
        }

        return $s;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#escapingString
     *
     * @param string $string          The input string to be escaped.
     * @param bool   $inAttributeMode (optional)
     *
     * @return string The escaped input string.
     */
    private function escapeHTMLString(
        string $string,
        bool $inAttributeMode = false
    ): string {
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

        return str_replace($search, $replace, $string);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#attribute's-serialised-name
     *
     * @param \Rowbot\DOM\Attr $attr The attribute whose name is to be serialized.
     *
     * @return string The attribute's serialized name.
     */
    private function serializeContentAttributeName(Attr $attr): string
    {
        $namespace = $attr->getNamespace();

        if ($namespace === null) {
            return $attr->getLocalName();
        }

        if ($namespace === Namespaces::XML) {
            return 'xml:' . $attr->getLocalName();
        }

        if ($namespace === Namespaces::XMLNS) {
            $localName = $attr->getLocalName();

            if ($localName === 'xmlns') {
                return 'xmlns';
            }

            return 'xmlns:' . $localName;
        }

        if ($namespace === Namespaces::XLINK) {
            return 'xlink:' . $attr->getLocalName();
        }

        return $attr->getQualifiedName();
    }
}
