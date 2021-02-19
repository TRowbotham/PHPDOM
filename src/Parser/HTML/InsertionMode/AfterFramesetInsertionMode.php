<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\DoctypeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\EOFToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-afterframeset
 */
class AfterFramesetInsertionMode extends InsertionMode
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
                // Process the token using the rules for the "in body" insertion
                // mode.
                (new InBodyInsertionMode($this->context))->processToken($token);

                return;
            }

            if ($token->tagName === 'noframes') {
                // Process the token using the rules for the "in head" insertion mode.
                (new InHeadInsertionMode($this->context))->processToken($token);

                return;
            }
        } elseif ($token instanceof EndTagToken && $token->tagName === 'html') {
            // Switch the insertion mode to "after after frameset".
            $this->context->insertionMode = new AfterAfterFramesetInsertionMode($this->context);

            return;
        } elseif ($token instanceof EOFToken) {
            // Stop parsing
            $this->context->stopParsing();

            return;
        }

        // Parse error.
        // Ignore the token.
    }
}
