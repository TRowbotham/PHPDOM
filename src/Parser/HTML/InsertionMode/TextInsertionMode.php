<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Element\HTML\HTMLScriptElement;
use Rowbot\DOM\Parser\HTML\TreeBuilderContext;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\EOFToken;
use Rowbot\DOM\Parser\Token\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-incdata
 */
class TextInsertionMode extends InsertionMode
{
    public function processToken(TreeBuilderContext $context, Token $token): void
    {
        if ($token instanceof CharacterToken) {
            // Insert the token's character.
            // NOTE: This can never be a U+0000 NULL character; the tokenizer
            // converts those to U+FFFD REPLACEMENT CHARACTER characters.
            $this->insertCharacter($context, $token);

            return;
        }

        if ($token instanceof EOFToken) {
            // Parse error.
            // If the current node is a script element, mark the script element
            // as "already started".
            if ($context->parser->openElements->bottom() instanceof HTMLScriptElement) {
                // TODO: Mark the script element as "already started".
            }

            // Pop the current node off the stack of open elements.
            $context->parser->openElements->pop();

            // Switch the insertion mode to the original insertion mode and
            // reprocess the token.
            $context->insertionMode = $context->originalInsertionMode;
            $context->insertionMode->processToken($context, $token);

            return;
        }

        if ($token instanceof EndTagToken) {
            if ($token->tagName === 'script') {
                // TODO: If the JavaScript execution context stack is empty, perform
                // a microtask checkpoint.

                // Let script be the current node (which will be a script element).
                $script = $context->parser->openElements->bottom();

                // Pop the current node off the stack of open elements.
                $context->parser->openElements->pop();

                // Switch the insertion mode to the original insertion mode.
                $context->insertionMode = $context->originalInsertionMode;

                // TODO: More stuff that will probably never be fully supported
                return;
            }

            // Pop the current node off the stack of open elements.
            $context->parser->openElements->pop();

            // Switch the insertion mode to the original insertion mode.
            $context->insertionMode = $context->originalInsertionMode;
        }
    }
}
