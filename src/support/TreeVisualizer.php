<?php
namespace phpjs\support;

use phpjs\Comment;
use phpjs\Document;
use phpjs\DocumentFragment;
use phpjs\DocumentType;
use phpjs\elements\Element;
use phpjs\elements\html\HTMLTemplateElement;
use phpjs\exceptions\InvalidNodeTypeError;
use phpjs\Namespaces;
use phpjs\parser\html\HTMLParser;
use phpjs\ProcessingInstruction;
use phpjs\Text;

abstract class TreeVisualizer
{
    public static function prettyPrint($node)
    {
        if (!$node instanceof Element &&
            !$node instanceof Document &&
            !$node instanceof DocumentFragment
        ) {
            throw new InvalidNodeTypeError();
        }

        echo '<ul class="prettyPrintTree">';
        self::prettyPrintInternal($node);
        echo '</ul>';
    }

    private static function prettyPrintInternal($node)
    {
        if ($node instanceof HTMLTemplateElement) {
            $node = $node->content;
        }

        foreach ($node->childNodes as $currentNode) {
            echo '<li>';

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

                echo '&lt;' . $tagname;

                foreach ($currentNode->attributes as $attr) {
                    echo ' ' . $attr->name;
                    echo '="' .
                        htmlentities(HTMLParser::escapeHTMLString(
                            $attr->value,
                            true
                        ) . '"');
                }

                echo '&gt;';
                $localName = $currentNode->localName;

                // If the current node's local name is a known void element,
                // then move on to the current node's next sibling, if any.
                switch ($localName) {
                    case 'area':
                    case 'base':
                    case 'basefont':
                    case 'bgsound':
                    case 'br':
                    case 'col':
                    case 'embed':
                    case 'frame':
                    case 'hr':
                    case 'img':
                    case 'input':
                    case 'keygen':
                    case 'link':
                    case 'menuitem':
                    case 'meta':
                    case 'param':
                    case 'source':
                    case 'track':
                    case 'wbr':
                        continue 2;
                }

                if ($currentNode->hasChildNodes()) {
                    echo '<ul>';
                    self::prettyPrintInternal($currentNode);
                    echo '</ul>';
                }

                echo '&lt;/' . $tagname . '&gt;';
            } elseif ($currentNode instanceof Text) {
                switch ($currentNode->parentNode->localName) {
                    case 'style':
                    case 'script':
                    case 'xmp':
                    case 'iframe':
                    case 'noembed':
                    case 'noframes':
                    case 'plaintext':
                    case 'noscript':
                        echo $currentNode->data;

                        break;

                    default:
                        echo htmlspecialchars($currentNode->data);
                }
            } elseif ($currentNode instanceof Comment) {
                echo '&lt;!--' . htmlentities($currentNode->data) . '--&gt;';
            } elseif ($currentNode instanceof ProcessingInstruction) {
                echo '&lt;?' . $currentNode->target . ' ';
                echo $currentNode->data . '&gt;';
            } elseif ($currentNode instanceof DocumentType) {
                echo '&lt;!DOCTYPE ' . $currentNode->name . '&gt;';
            }

            echo '</li>';
        }
    }

    public static function printTree($node)
    {
        if (!$node instanceof Element &&
            !$node instanceof Document &&
            !$node instanceof DocumentFragment
        ) {
            throw new InvalidNodeTypeError();
        }

        echo '<ul class="domTree">';
        self::printTreeInternal($node);
        echo '</ul>';
    }

    private static function printTreeInternal($node)
    {
        if ($node instanceof HTMLTemplateElement) {
            $node = $node->content;
        }

        foreach ($node->childNodes as $child) {
            echo '<li>';

            if ($child instanceof Element) {
                echo '<code>', $child->tagName, '</code>';

                foreach ($child->attributes as $attr) {
                    echo ' <code class="attribute name">', $attr->name,
                        '</code>="<code class="attribute value">',
                        htmlentities($attr->value), '</code>"';
                }

                if ($child->hasChildNodes()) {
                    echo '<ul>', self::printTreeInternal($child), '</ul>';
                }
            } elseif ($child instanceof DocumentType) {
                echo '<code>DOCTYPE ', $child->name, '</code>';
            } elseif ($child instanceof Text) {
                echo '<code>#text:</code> ', htmlentities($child->data);
            } elseif ($child instanceof Comment) {
                echo '<code>#comment:</code> ', htmlentities($child->data);
            } elseif ($child instanceof ProcessingInstruction) {
                echo $child->target;
            }

            echo '</li>';
        }
    }
}
