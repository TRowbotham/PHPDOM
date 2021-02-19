<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Element\HTML\HTMLSelectElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inselectintable
 */
class InSelectInTableInsertionMode extends InsertionMode
{
    public function processToken(Token $token): void
    {
        if (
            $token instanceof StartTagToken
            && (
                $token->tagName === 'caption'
                || $token->tagName === 'table'
                || $token->tagName === 'tbody'
                || $token->tagName === 'tfoot'
                || $token->tagName === 'thead'
                || $token->tagName === 'tr'
                || $token->tagName === 'td'
                || $token->tagName === 'th'
            )
        ) {
            // Parse error.
            // Pop elements from the stack of open elements until a select
            // element has been popped from the stack.
            while (!$this->context->parser->openElements->isEmpty()) {
                if ($this->context->parser->openElements->pop() instanceof HTMLSelectElement) {
                    break;
                }
            }

            // Reset the insertion mode appropriately.
            $this->context->resetInsertionMode();

            // Reprocess the token.
            $this->context->insertionMode->processToken($token);

            return;
        }

        if (
            $token instanceof EndTagToken
            && (
                $token->tagName === 'caption'
                || $token->tagName === 'table'
                || $token->tagName === 'tbody'
                || $token->tagName === 'tfoot'
                || $token->tagName === 'thead'
                || $token->tagName === 'tr'
                || $token->tagName === 'td'
                || $token->tagName === 'th'
            )
        ) {
            // Parse error
            // If the stack of open elements does not have an element in
            // table scope that is an HTML element with the same tag name as
            // that of the token, then ignore the token.
            if (!$this->context->parser->openElements->hasElementInTableScope($token->tagName, Namespaces::HTML)) {
                // Ignore the token.
                return;
            }

            // Pop elements from the stack of open elements until a select
            // element has been popped from the stack.
            while (!$this->context->parser->openElements->isEmpty()) {
                if ($this->context->parser->openElements->pop() instanceof HTMLSelectElement) {
                    break;
                }
            }

            // Reset the insertion mode appropriately.
            $this->context->resetInsertionMode();

            // Reprocess the token.
            $this->context->insertionMode->processToken($token);

            return;
        }

        // Process the token using the rules for the "in select" insertion mode.
        (new InSelectInsertionMode($this->context))->processToken($token);
    }
}
