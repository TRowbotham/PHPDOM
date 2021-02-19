<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\DoctypeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#the-before-head-insertion-mode
 */
class BeforeHeadInsertionMode extends InsertionMode
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
            // Ignore the token.
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

            if ($token->tagName === 'head') {
                // Insert an HTML element for the token.
                $node = $this->context->insertForeignElement($token, Namespaces::HTML);

                // Set the head element pointer to the newly created head element.
                $this->context->parser->headElementPointer = $node;

                // Switch the insertion mode to "in head".
                $this->context->insertionMode = new InHeadInsertionMode($this->context);

                return;
            }
        } elseif ($token instanceof EndTagToken) {
            if (
                $token->tagName === 'head'
                || $token->tagName === 'body'
                || $token->tagName === 'html'
                || $token->tagName === 'br'
            ) {
                // Act as described in the "anything else" entry below.
                $this->beforeHeadInsertionModeAnythingElse($token);

                return;
            }

            // Parse error.
            // Ignore the token.
            return;
        }

        $this->beforeHeadInsertionModeAnythingElse($token);
    }

    /**
     * The "before head" insertion mode's "anything else" steps.
     */
    private function beforeHeadInsertionModeAnythingElse(Token $token): void
    {
        // Insert an HTML element for a "head" start tag token with no
        // attributes.
        $node = $this->context->insertForeignElement(new StartTagToken('head'), Namespaces::HTML);

        // Set the head element pointer to the newly created head element.
        $this->context->parser->headElementPointer = $node;

        // Switch the insertion mode to "in head".
        $this->context->insertionMode = new InHeadInsertionMode($this->context);

        // Reprocess the current token.
        $this->context->insertionMode->processToken($token);
    }
}
