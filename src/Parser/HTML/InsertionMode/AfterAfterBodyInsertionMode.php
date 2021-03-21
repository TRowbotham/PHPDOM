<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Parser\HTML\TreeBuilderContext;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\DoctypeToken;
use Rowbot\DOM\Parser\Token\EOFToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#the-after-after-body-insertion-mode
 */
class AfterAfterBodyInsertionMode extends AbstractInsertionMode implements InsertionMode
{
    public function processToken(TreeBuilderContext $context, Token $token): void
    {
        if ($token instanceof CommentToken) {
            // Insert a comment as the last child of the Document object.
            $this->insertComment($context, $token, [$context->document, 'beforeend']);

            return;
        }

        if (
            $token instanceof DoctypeToken
            || (
                $token instanceof CharacterToken
                && (
                    $token->data === "\x09"
                    || $token->data === "\x0A"
                    || $token->data === "\x0C"
                    || $token->data === "\x0D"
                    || $token->data === "\x20"
                )
            )
            || ($token instanceof StartTagToken && $token->tagName === 'html')
        ) {
            // Process the token using the rules for the "in body" insertion mode.
            (new InBodyInsertionMode())->processToken($context, $token);

            return;
        }

        if ($token instanceof EOFToken) {
            // Stop parsing
            $this->stopParsing($context);

            return;
        }

        // Parse error.
        // Switch the insertion mode to "in body" and reprocess the token.
        $context->insertionMode = new InBodyInsertionMode();
        $context->insertionMode->processToken($context, $token);
    }
}
