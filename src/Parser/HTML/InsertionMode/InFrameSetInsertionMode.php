<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Element\HTML\HTMLFrameSetElement;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Namespaces;
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
    public function processToken(Token $token): void
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
            $this->context->insertCharacter($token);

            return;
        }

        if ($token instanceof CommentToken) {
            // Insert a comment.
            $this->context->insertComment($token);

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
                (new InBodyInsertionMode($this->context))->processToken($token);

                return;
            }

            if ($token->tagName === 'frameset') {
                // Insert an HTML element for the token.
                $this->context->insertForeignElement($token, Namespaces::HTML);

                return;
            }

            if ($token->tagName === 'frame') {
                // Insert an HTML element for the token. Immediately pop the current
                // node off the stack of open elements.
                $this->context->insertForeignElement($token, Namespaces::HTML);
                $this->context->parser->openElements->pop();

                // Acknowledge the token's self-closing flag, if it is set.
                if ($token->isSelfClosing()) {
                    $token->acknowledge();
                }

                return;
            }

            if ($token->tagName === 'noframes') {
                // Process the token using the rules for the "in head" insertion mode.
                (new InHeadInsertionMode($this->context))->processToken($token);

                return;
            }
        } elseif ($token instanceof EndTagToken) {
            if ($token->tagName === 'frameset') {
                // If the current node is the root html element, then this is a
                // parse error; ignore the token. (fragment case)
                if ($this->context->parser->openElements->bottom() instanceof HTMLHtmlElement) {
                    // Parse error.
                    // Ignore the token.
                } else {
                    // Otherwise, pop the current node from the stack of open
                    // elements.
                    $this->context->parser->openElements->pop();
                }

                // If the parser was not originally created as part of the HTML
                // fragment parsing algorithm (fragment case), and the current node
                // is no longer a frameset element, then switch the insertion mode
                // to "after frameset".
                if (
                    !$this->context->parser->isFragmentCase
                    && !$this->context->parser->openElements->bottom() instanceof HTMLFrameSetElement
                ) {
                    $this->context->insertionMode = new AfterFramesetInsertionMode($this->context);
                }

                return;
            }
        } elseif ($token instanceof EOFToken) {
            // If the current node is not the root html element, then this is a
            // parse error.
            if (!$this->context->parser->openElements->bottom() instanceof HTMLHtmlElement) {
                // Parse error.
            }

            // NOTE: The current node can only be the root html element in the
            // fragment case.

            // Stop parsing.
            $this->context->stopParsing();

            return;
        }

        // Parse error.
        // Ignore the token.
    }
}
