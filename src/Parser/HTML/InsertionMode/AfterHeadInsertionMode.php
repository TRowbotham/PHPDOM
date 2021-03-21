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
 * @see https://html.spec.whatwg.org/multipage/syntax.html#the-after-head-insertion-mode
 */
class AfterHeadInsertionMode extends AbstractInsertionMode implements InsertionMode
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
        } elseif ($token instanceof CommentToken) {
            // Insert a comment.
            $this->insertComment($context, $token);

            return;
        } elseif ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
            return;
        } elseif ($token instanceof StartTagToken) {
            if ($token->tagName === 'html') {
                // Process the token using the rules for the "in body" insertion
                // mode.
                (new InBodyInsertionMode())->processToken($context, $token);

                return;
            }

            if ($token->tagName === 'body') {
                // Insert an HTML element for the token.
                $this->insertForeignElement($context, $token, Namespaces::HTML);

                // Set the frameset-ok flag to "not ok".
                $context->framesetOk = 'not ok';

                // Switch the insertion mode to "in body".
                $context->insertionMode = new InBodyInsertionMode();

                return;
            }

            if ($token->tagName === 'frameset') {
                // Insert an HTML element for the token.
                $this->insertForeignElement($context, $token, Namespaces::HTML);

                // Switch the insertion mode to "in frameset".
                $context->insertionMode = new InFrameSetInsertionMode();

                return;
            }

            if (
                $token->tagName === 'base'
                || $token->tagName === 'basefont'
                || $token->tagName === 'bgsound'
                || $token->tagName === 'link'
                || $token->tagName === 'meta'
                || $token->tagName === 'noframes'
                || $token->tagName === 'script'
                || $token->tagName === 'style'
                || $token->tagName === 'template'
                || $token->tagName === 'title'
            ) {
                // Parse error
                // Push the node pointed to by the head element pointer onto the
                // stack of open elements.
                $context->parser->openElements->push($context->parser->headElementPointer);

                // Process the token using the rules for the "in head" insertion
                // mode.
                (new InHeadInsertionMode())->processToken($context, $token);

                // Remove the node pointed to by the head element pointer from the
                // stack of open elements. (It might not be the current node at
                // this point.)
                // NOTE: The head element pointer cannot be null at this point.
                $context->parser->openElements->remove($context->parser->headElementPointer);

                return;
            }

            if ($token->tagName === 'head') {
                // Parse error.
                // Ignore the token
                return;
            }
        } elseif ($token instanceof EndTagToken) {
            if ($token->tagName === 'template') {
                // Process the token using the rules for the "in head" insertion mode.
                (new InHeadInsertionMode())->processToken($context, $token);

                return;
            }

            if ($token->tagName === 'body' || $token->tagName === 'html' || $token->tagName === 'br') {
                // Act as described in the "anything else" entry below.
                $this->afterHeadInsertionModeAnythingElse($context, $token);

                return;
            }

            // Parse error.
            // Ignore the token
            return;
        }

        $this->afterHeadInsertionModeAnythingElse($context, $token);
    }

    /**
     * The "after head" insertion mode's "anything else" steps.
     */
    private function afterHeadInsertionModeAnythingElse(TreeBuilderContext $context, Token $token): void
    {
        // Insert an HTML element for a "body" start tag token with no
        // attributes.
        $this->insertForeignElement($context, new StartTagToken('body'), Namespaces::HTML);

        // Switch the insertion mode to "in body".
        $context->insertionMode = new InBodyInsertionMode();

        // Reprocess the current token
        $context->insertionMode->processToken($context, $token);
    }
}
