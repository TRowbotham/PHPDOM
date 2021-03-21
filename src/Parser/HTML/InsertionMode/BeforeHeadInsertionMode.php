<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\HTML\TreeBuilderContext;
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
            // Ignore the token.
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

            if ($token->tagName === 'head') {
                // Insert an HTML element for the token.
                $node = $this->insertForeignElement($context, $token, Namespaces::HTML);

                // Set the head element pointer to the newly created head element.
                $context->parser->headElementPointer = $node;

                // Switch the insertion mode to "in head".
                $context->insertionMode = new InHeadInsertionMode();

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
                $this->beforeHeadInsertionModeAnythingElse($context, $token);

                return;
            }

            // Parse error.
            // Ignore the token.
            return;
        }

        $this->beforeHeadInsertionModeAnythingElse($context, $token);
    }

    /**
     * The "before head" insertion mode's "anything else" steps.
     */
    private function beforeHeadInsertionModeAnythingElse(TreeBuilderContext $context, Token $token): void
    {
        // Insert an HTML element for a "head" start tag token with no
        // attributes.
        $node = $this->insertForeignElement($context, new StartTagToken('head'), Namespaces::HTML);

        // Set the head element pointer to the newly created head element.
        $context->parser->headElementPointer = $node;

        // Switch the insertion mode to "in head".
        $context->insertionMode = new InHeadInsertionMode();

        // Reprocess the current token.
        $context->insertionMode->processToken($context, $token);
    }
}
