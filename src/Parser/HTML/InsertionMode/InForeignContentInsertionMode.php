<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\SVG\SVGScriptElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\HTML\IntegrationPointTrait;
use Rowbot\DOM\Parser\HTML\TreeBuilderContext;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\DoctypeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\TagToken;
use Rowbot\DOM\Parser\Token\Token;
use Rowbot\DOM\Utils;

use function preg_match;

/**
 * This is a pseudo insertion mode. It cannot be set as an insertion mode in terms of the parser's
 * current insertion mode.
 */
class InForeignContentInsertionMode extends InsertionMode
{
    use IntegrationPointTrait;

    /**
     * @see https://html.spec.whatwg.org/multipage/parsing.html#parsing-main-inforeign:svg-namespace
     */
    private const SVG_ELEMENTS = [
        'altglyph'            => 'altGlyph',
        'altglyphdef'         => 'altGlyphDef',
        'altglyphitem'        => 'altGlyphItem',
        'animatecolor'        => 'animateColor',
        'animatemotion'       => 'animateMotion',
        'animatetransform'    => 'animateTransform',
        'clippath'            => 'clipPath',
        'feblend'             => 'feBlend',
        'fecolormatrix'       => 'feColorMatrix',
        'fecomponenttransfer' => 'feComponentTransfer',
        'fecomposite'         => 'feComposite',
        'feconvolvematrix'    => 'feConvolveMatrix',
        'fediffuselighting'   => 'feDiffuseLighting',
        'fedisplacementmap'   => 'feDisplacementMap',
        'fedistantlight'      => 'feDistantLight',
        'fedropshadow'        => 'feDropShadow',
        'feflood'             => 'feFlood',
        'fefunca'             => 'feFuncA',
        'fefuncb'             => 'feFuncB',
        'fefuncg'             => 'feFuncG',
        'fefuncr'             => 'feFuncR',
        'fegaussianblur'      => 'feGaussianBlur',
        'feimage'             => 'feImage',
        'femerge'             => 'feMerge',
        'femergenode'         => 'feMergeNode',
        'femorphology'        => 'feMorphology',
        'feoffset'            => 'feOffset',
        'fepointlight'        => 'fePointLight',
        'fespecularlighting'  => 'feSpecularLighting',
        'fespotlight'         => 'feSpotLight',
        'fetile'              => 'feTile',
        'feturbulence'        => 'feTurbulence',
        'foreignobject'       => 'foreignObject',
        'glyphref'            => 'glyphRef',
        'lineargradient'      => 'linearGradient',
        'radialgradient'      => 'radialGradient',
        'textpath'            => 'textPath',
    ];

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inforeign
     */
    public function processToken(TreeBuilderContext $context, Token $token): void
    {
        if ($token instanceof CharacterToken) {
            if ($token->data === "\x00") {
                // Parse error.
                // Insert a U+FFFD REPLACEMENT CHARACTER character.
                $this->insertCharacter($context, "\u{FFFD}");

                return;
            }

            if (
                $token->data === "\x09"
                || $token->data === "\x0A"
                || $token->data === "\x0C"
                || $token->data === "\x0D"
                || $token->data === "\x20"
            ) {
                // Insert the token's character.
                $this->insertCharacter($context, $token);

                return;
            }

            // Insert the token's character.
            $this->insertCharacter($context, $token);

            // Set the frameset-ok flag to "not ok".
            $context->framesetOk = 'not ok';

            return;
        }

        if ($token instanceof CommentToken) {
            // Insert a comment.
            $this->insertComment($context, $token);

            return;
        }

        if ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
            return;
        }

        if ($token instanceof StartTagToken) {
            $isMatchingFontToken = false;

            if ($token->tagName === 'font') {
                foreach ($token->attributes as $attr) {
                    if ($attr->name === 'color' || $attr->name === 'face' || $attr->name === 'size') {
                        $isMatchingFontToken = true;

                        break;
                    }
                }
            }

            if (
                $isMatchingFontToken
                || preg_match(
                    '/^(b|big|blockquote|body|br|center|code|dd|div|dl|dt|em|'
                    . 'embed|h[1-6]|head|hr|i|img|li|listing|menu|meta|nobr|ol|p|'
                    . 'pre|ruby|s|small|span|strong|strike|sub|sup|table|tt|u|ul|'
                    . 'var)$/',
                    $token->tagName
                )
            ) {
                // Parse error.
                // If the parser was originally created for the HTML fragment
                // parsing algorithm, then act as described in the "any other start
                // tag" entry below. (fragment case)
                if ($context->parser->isFragmentCase) {
                    $this->inForeignContentAnyOtherStartTag($context, $token);

                    return;
                }

                // Pop an element from the stack of open elements, and then keep
                // popping more elements from the stack of open elements until the
                // current node is a MathML text integration point, an HTML
                // integration point, or an element in the HTML namespace.
                while (!$context->parser->openElements->isEmpty()) {
                    $context->parser->openElements->pop();
                    $currentNode = $context->parser->openElements->bottom();

                    if (
                        $this->isMathMLTextIntegrationPoint($currentNode)
                        || $this->isHTMLIntegrationPoint($currentNode, $context->elementTokenMap)
                        || (
                            $currentNode instanceof Element
                            && $currentNode->namespaceURI === Namespaces::HTML
                        )
                    ) {
                        break;
                    }
                }

                // Then, reprocess the token.
                $context->insertionMode->processToken($context, $token);

                return;
            }

            $this->inForeignContentAnyOtherStartTag($context, $token);

            return;
        }

        if ($token instanceof EndTagToken) {
            if (
                $token->tagName === 'script'
                && $context->parser->openElements->bottom() instanceof SVGScriptElement
            ) {
                $this->inForeignContentScriptEndTag($context, $token);

                return;
            }

            // 1. Initialize node to be the current node (the bottommost node of the stack).
            $node = $context->parser->openElements->bottom();

            // 2. If node's tag name, converted to ASCII lowercase, is not the same as the tag name
            // of the token, then this is a parse error.
            if (Utils::toASCIILowercase($node->tagName) !== $token->tagName) {
                // Parse error.
            }

            $iter = $context->parser->openElements->getIterator();
            $iter->rewind();

            // 3. Loop: If node is the topmost element in the stack of open elements, then return.
            // (fragment case)
            while ($iter->valid()) {
                if ($node === $context->parser->openElements->top()) {
                    return;
                }

                // 4. If node's tag name, converted to ASCII lowercase, is the same as the tag name
                // of the token, pop elements from the stack of open elements until node has been
                // popped from the stack, and then return.
                if (Utils::toASCIILowercase($node->tagName) === $token->tagName) {
                    while (!$context->parser->openElements->isEmpty()) {
                        $iter->next();

                        if ($context->parser->openElements->pop() === $node) {
                            return;
                        }
                    }

                    $iter = $context->parser->openElements->getIterator();
                    $iter->rewind();
                } else {
                    $iter->next();
                }

                // 5. Set node to the previous entry in the stack of open elements.
                $node = $iter->current();

                // 6. If node is not an element in the HTML namespace, return to the step labeled loop.
                if ($node instanceof Element && $node->namespaceURI === Namespaces::HTML) {
                    break;
                }
            }

            // 7. Otherwise, process the token according to the rules given in the section
            // corresponding to the current insertion mode in HTML content.
            $context->insertionMode->processToken($context, $token);
        }
    }

    /**
     * The in foreign content's "any other start tag" steps.
     */
    private function inForeignContentAnyOtherStartTag(TreeBuilderContext $context, StartTagToken $token): void
    {
        $adjustedCurrentNode = $context->parser->getAdjustedCurrentNode();
        $isElementInSVGNamespace = false;
        $namespace = $adjustedCurrentNode->namespaceURI;

        // If the adjusted current node is an element in the MathML
        // namespace, adjust MathML attributes for the token. (This
        // fixes the case of MathML attributes that are not all
        // lowercase.)
        if ($namespace === Namespaces::MATHML) {
            $this->adjustMathMLAttributes($token);
        }

        $isElementInSVGNamespace = $namespace === Namespaces::SVG;

        // If the adjusted current node is an element in the SVG namespace,
        // and the token's tag name is one of the ones in the first column
        // of the following table, change the tag name to the name given in
        // the corresponding cell in the second column. (This fixes the case
        // of SVG elements that are not all lowercase.)
        if ($isElementInSVGNamespace && isset(self::SVG_ELEMENTS[$token->tagName])) {
            $token->tagName = self::SVG_ELEMENTS[$token->tagName];
        }

        // If the adjusted current node is an element in the SVG namespace,
        // adjust SVG attributes for the token. (This fixes the case of SVG
        // attributes that are not all lowercase.)
        if ($isElementInSVGNamespace) {
            $this->adjustSVGAttributes($token);
        }

        // Adjust foreign attributes for the token. (This fixes the use of
        // namespaced attributes, in particular XLink in SVG.)
        $this->adjustForeignAttributes($token);

        // Insert a foreign element for the token, in the same namespace as the
        // adjusted current node.
        $this->insertForeignElement($context, $token, $namespace);

        // If the token has its self-closing flag set...
        if ($token->isSelfClosing()) {
            // If the token's tag name is "script", and the new current node is
            // in the SVG namespace...
            if (
                $token->tagName === 'script'
                && $context->parser->openElements->bottom()->namespaceURI === Namespaces::SVG
            ) {
                // Acknowledge the token's self-closing flag, and then act as
                // described in the steps for a "script" end tag below.
                $token->acknowledge();
                $this->inForeignContentScriptEndTag($context, $token);
            } else {
                // Pop the current node off the stack of open elements and
                // acknowledge the token's self-closing flag.
                $context->parser->openElements->pop();
                $token->acknowledge();
            }
        }
    }

    /**
     * The in foreign content's "script end tag" steps.
     */
    private function inForeignContentScriptEndTag(TreeBuilderContext $context, TagToken $token): void
    {
        // Pop the current node off the stack of open elements.
        $context->parser->openElements->pop();

        // TODO: More stuff that will probably never be fully supported.
    }
}
