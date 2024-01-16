<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\Comment;
use Rowbot\DOM\DocumentType;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Parser\FragmentSerializerInterface;
use Rowbot\DOM\ProcessingInstruction;
use Rowbot\DOM\Text;

use function str_replace;

class FragmentSerializer implements FragmentSerializerInterface
{
    /**
     * @see https://html.spec.whatwg.org/multipage/parsing.html#serializes-as-void
     */
    private const EXTENDED_VOID_ELEMENTS = self::VOID_ELEMENTS + [
        'basefont' => true,
        'bgsound'  => true,
        'frame'    => true,
        'keygen'   => true,
        'param'    => true,
    ];

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#serialising-html-fragments
     *
     * @param \Rowbot\DOM\Element\Element|\Rowbot\DOM\Document|\Rowbot\DOM\DocumentFragment $node
     */
    public function serializeFragment(Node $node, bool $requireWellFormed = false): string
    {
        if ($node instanceof Element && isset(self::EXTENDED_VOID_ELEMENTS[$node->localName])) {
            return '';
        }

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
                    $attrName = match ($attr->getNamespace()) {
                        null => $attr->getLocalName(),
                        Namespaces::XML => 'xml:' . $attr->getLocalName(),
                        Namespaces::XMLNS => match ($attr->getLocalName()) {
                            'xmlns' => 'xmlns',
                            default => 'xmlns:' . $attr->getLocalName(),
                        },
                        Namespaces::XLINK => 'xlink:' . $attr->getLocalName(),
                        default => $attr->getQualifiedName(),
                    };
                    $attrValue = str_replace(['&', "\u{00A0}", '"'], ['&amp;', '&nbsp;', '&quot;'], $attr->getValue());

                    $s .= ' ' . $attrName . '="' . $attrValue . '"';
                }

                $s .= '>';
                $localName = $currentNode->localName;

                // If the current node's local name is a known void element,
                // then move on to current node's next sibling, if any.
                if (isset(self::EXTENDED_VOID_ELEMENTS[$localName])) {
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
                    || ($localName === 'noscript' && $currentNode->getNodeDocument()->isScriptingEnabled())
                ) {
                    $s .= $currentNode->data;
                } else {
                    $s .= str_replace(
                        ['&', "\u{00A0}", '<', '>'],
                        ['&amp;', '&nbsp;', '&lt;', '&gt;'],
                        $currentNode->data
                    );
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
}
