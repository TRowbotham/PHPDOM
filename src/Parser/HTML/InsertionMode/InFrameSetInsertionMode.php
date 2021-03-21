<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Element\HTML\HTMLFrameSetElement;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\HTML\TreeBuilderContext;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\DoctypeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\EOFToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inframeset
 */
class InFrameSetInsertionMode extends InsertionMode
{
    public function processToken(TreeBuilderContext $context, Token $token): void
    {
        if (
            $token instanceof CharacterToken
            && (
                $token->data === "\x09"
                || $token->data === "\x0A"
                || $token->data === "\x0C"
                || $token->data === "\x0D"
                || $token->data === "\x20"
            )
        ) {
            // Insert the character.
            $this->insertCharacter($context, $token);

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
            if ($token->tagName === 'html') {
                // Process the token using the rules for the "in body" insertion mode.
                (new InBodyInsertionMode())->processToken($context, $token);

                return;
            }

            if ($token->tagName === 'frameset') {
                // Insert an HTML element for the token.
                $this->insertForeignElement($context, $token, Namespaces::HTML);

                return;
            }

            if ($token->tagName === 'frame') {
                // Insert an HTML element for the token. Immediately pop the current
                // node off the stack of open elements.
                $this->insertForeignElement($context, $token, Namespaces::HTML);
                $context->parser->openElements->pop();

                // Acknowledge the token's self-closing flag, if it is set.
                if ($token->isSelfClosing()) {
                    $token->acknowledge();
                }

                return;
            }

            if ($token->tagName === 'noframes') {
                // Process the token using the rules for the "in head" insertion mode.
                (new InHeadInsertionMode())->processToken($context, $token);

                return;
            }
        } elseif ($token instanceof EndTagToken) {
            if ($token->tagName === 'frameset') {
                // If the current node is the root html element, then this is a
                // parse error; ignore the token. (fragment case)
                if ($context->parser->openElements->bottom() instanceof HTMLHtmlElement) {
                    // Parse error.
                    // Ignore the token.
                } else {
                    // Otherwise, pop the current node from the stack of open
                    // elements.
                    $context->parser->openElements->pop();
                }

                // If the parser was not originally created as part of the HTML
                // fragment parsing algorithm (fragment case), and the current node
                // is no longer a frameset element, then switch the insertion mode
                // to "after frameset".
                if (
                    !$context->parser->isFragmentCase
                    && !$context->parser->openElements->bottom() instanceof HTMLFrameSetElement
                ) {
                    $context->insertionMode = new AfterFramesetInsertionMode();
                }

                return;
            }
        } elseif ($token instanceof EOFToken) {
            // If the current node is not the root html element, then this is a
            // parse error.
            if (!$context->parser->openElements->bottom() instanceof HTMLHtmlElement) {
                // Parse error.
            }

            // NOTE: The current node can only be the root html element in the
            // fragment case.

            // Stop parsing.
            $this->stopParsing($context);

            return;
        }

        // Parse error.
        // Ignore the token.
    }
}
