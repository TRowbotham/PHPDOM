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
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-afterbody
 */
class AfterBodyInsertionMode extends InsertionMode
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
            // Process the token using the rules for the "in body" insertion
            // mode.
            (new InBodyInsertionMode($this->context))->processToken($token);

            return;
        }

        if ($token instanceof CommentToken) {
            // Insert a comment as the last child of the first element in the
            // stack of open elements (the html element).
            $this->context->insertComment($token, [$this->context->parser->openElements->top(), 'beforeend']);

            return;
        }

        if ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
            return;
        }

        if ($token instanceof StartTagToken && $token->tagName === 'html') {
            // Process the token using the rules for the "in body" insertion
            // mode.
            (new InBodyInsertionMode($this->context))->processToken($token);

            return;
        }

        if ($token instanceof EndTagToken && $token->tagName === 'html') {
            // If the parser was originally created as part of the HTML fragment
            // parsing algorithm, this is a parse error; ignore the token.
            // (fragment case)
            if ($this->context->parser->isFragmentCase) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Otherwise, switch the insertion mode to "after after body".
            $this->context->insertionMode = new AfterAfterBodyInsertionMode($this->context);

            return;
        }

        if ($token instanceof EOFToken) {
            // Stop parsing.
            $this->context->stopParsing();

            return;
        }

        // Parse error.
        // Switch the insertion mode to "in body" and reprocess the token.
        $this->context->insertionMode = new InBodyInsertionMode($this->context);
        $this->context->insertionMode->processToken($token);
    }
}
