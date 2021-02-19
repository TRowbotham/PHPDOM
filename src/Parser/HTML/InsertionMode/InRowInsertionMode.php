<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\TagToken;
use Rowbot\DOM\Parser\Token\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intr
 */
class InRowInsertionMode extends InsertionMode
{
    public function processToken(Token $token): void
    {
        if ($token instanceof StartTagToken) {
            if ($token->tagName === 'th' || $token->tagName === 'td') {
                // Clear the stack back to a table row context.
                $this->context->parser->openElements->clearBackToTableRowContext();

                // Insert an HTML element for the token, then switch the insertion
                // mode to "in cell".
                $this->context->insertForeignElement($token, Namespaces::HTML);
                $this->context->insertionMode = new InCellInsertionMode($this->context);

                // Insert a marker at the end of the list of active formatting
                // elements.
                $this->context->activeFormattingElements->insertMarker();

                return;
            }

            if (
                $token->tagName === 'caption'
                || $token->tagName === 'col'
                || $token->tagName === 'colgroup'
                || $token->tagName === 'tbody'
                || $token->tagName === 'tfoot'
                || $token->tagName === 'thead'
                || $token->tagName === 'tr'
            ) {
                $this->extracted($token);

                return;
            }
        } elseif ($token instanceof EndTagToken) {
            if ($token->tagName === 'tr') {
                // If the stack of open elements does not have a tr element in
                // table scope, this is a parse error; ignore the token.
                if (!$this->context->parser->openElements->hasElementInTableScope('tr', Namespaces::HTML)) {
                    // Parse error.
                    // Ignore the token.
                    return;
                }

                // Clear the stack back to a table row context.
                $this->context->parser->openElements->clearBackToTableRowContext();

                // Pop the current node (which will be a tr element) from the stack
                // of open elements. Switch the insertion mode to "in table body".
                $this->context->parser->openElements->pop();
                $this->context->insertionMode = new InTableBodyInsertionMode($this->context);

                return;
            }

            if ($token->tagName === 'table') {
                $this->extracted($token);

                return;
            }

            if ($token->tagName === 'tbody' || $token->tagName === 'thead' || $token->tagName === 'tfoot') {
                // If the stack of open elements does not have an element in table
                // scope that is an HTML element with the same tag name as the
                // token, this is a parse error; ignore the token.
                if (!$this->context->parser->openElements->hasElementInTableScope($token->tagName, Namespaces::HTML)) {
                    // Parse error.
                    // Ignore the token.
                }

                // If the stack of open elements does not have a tr element in
                // table scope, ignore the token.
                if (!$this->context->parser->openElements->hasElementInTableScope('tr', Namespaces::HTML)) {
                    // Ignore the token.
                    return;
                }

                // Clear the stack back to a table row context.
                $this->context->parser->openElements->clearBackToTableRowContext();

                // Pop the current node (which will be a tr element) from the stack
                // of open elements. Switch the insertion mode to "in table body".
                $this->context->parser->openElements->pop();
                $this->context->insertionMode = new InTableBodyInsertionMode($this->context);

                // Reprocess the token.
                $this->context->insertionMode->processToken($token);

                return;
            }

            if (
                $token->tagName === 'body'
                || $token->tagName === 'caption'
                || $token->tagName === 'col'
                || $token->tagName === 'colgroup'
                || $token->tagName === 'html'
                || $token->tagName === 'td'
                || $token->tagName === 'th'
            ) {
                // Parse error.
                // Ignore the token.
                return;
            }
        }

        // Process the token using the rules for the "in table" insertion mode.
        (new InTableInsertionMode($this->context))->processToken($token);
    }

    private function extracted(TagToken $token): void
    {
        // If the stack of open elements does not have a tr element in
        // table scope, this is a parse error; ignore the token.
        if (!$this->context->parser->openElements->hasElementInTableScope('tr', Namespaces::HTML)) {
            // Parse error.
            // Ignore the token.
            return;
        }

        // Clear the stack back to a table row context.
        $this->context->parser->openElements->clearBackToTableRowContext();

        // Pop the current node (which will be a tr element) from the stack
        // of open elements. Switch the insertion mode to "in table body".
        $this->context->parser->openElements->pop();
        $this->context->insertionMode = new InTableBodyInsertionMode($this->context);

        // Reprocess the token.
        $this->context->insertionMode->processToken($token);
    }
}
