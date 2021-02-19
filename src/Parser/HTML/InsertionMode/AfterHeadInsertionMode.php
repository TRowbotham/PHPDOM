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
 * @see https://html.spec.whatwg.org/multipage/syntax.html#the-after-head-insertion-mode
 */
class AfterHeadInsertionMode extends InsertionMode
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
        } elseif ($token instanceof CommentToken) {
            // Insert a comment.
            $this->context->insertComment($token);

            return;
        } elseif ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
            return;
        } elseif ($token instanceof StartTagToken) {
            if ($token->tagName === 'html') {
                // Process the token using the rules for the "in body" insertion
                // mode.
                (new InBodyInsertionMode($this->context))->processToken($token);

                return;
            }

            if ($token->tagName === 'body') {
                // Insert an HTML element for the token.
                $this->context->insertForeignElement($token, Namespaces::HTML);

                // Set the frameset-ok flag to "not ok".
                $this->context->framesetOk = 'not ok';

                // Switch the insertion mode to "in body".
                $this->context->insertionMode = new InBodyInsertionMode($this->context);

                return;
            }

            if ($token->tagName === 'frameset') {
                // Insert an HTML element for the token.
                $this->context->insertForeignElement($token, Namespaces::HTML);

                // Switch the insertion mode to "in frameset".
                $this->context->insertionMode = new InFrameSetInsertionMode($this->context);

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
                $this->context->parser->openElements->push($this->context->parser->headElementPointer);

                // Process the token using the rules for the "in head" insertion
                // mode.
                (new InHeadInsertionMode($this->context))->processToken($token);

                // Remove the node pointed to by the head element pointer from the
                // stack of open elements. (It might not be the current node at
                // this point.)
                // NOTE: The head element pointer cannot be null at this point.
                $this->context->parser->openElements->remove($this->context->parser->headElementPointer);

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
                (new InHeadInsertionMode($this->context))->processToken($token);

                return;
            }

            if ($token->tagName === 'body' || $token->tagName === 'html' || $token->tagName === 'br') {
                // Act as described in the "anything else" entry below.
                $this->afterHeadInsertionModeAnythingElse($token);

                return;
            }

            // Parse error.
            // Ignore the token
            return;
        }

        $this->afterHeadInsertionModeAnythingElse($token);
    }

    /**
     * The "after head" insertion mode's "anything else" steps.
     */
    private function afterHeadInsertionModeAnythingElse(Token $token): void
    {
        // Insert an HTML element for a "body" start tag token with no
        // attributes.
        $this->context->insertForeignElement(new StartTagToken('body'), Namespaces::HTML);

        // Switch the insertion mode to "in body".
        $this->context->insertionMode = new InBodyInsertionMode($this->context);

        // Reprocess the current token
        $this->context->insertionMode->processToken($token);
    }
}
