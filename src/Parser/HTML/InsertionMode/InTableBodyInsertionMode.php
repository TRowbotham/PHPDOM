<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\TagToken;
use Rowbot\DOM\Parser\Token\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intbody
 */
class InTableBodyInsertionMode extends InsertionMode
{
    public function processToken(Token $token): void
    {
        if ($token instanceof StartTagToken) {
            if ($token->tagName === 'tr') {
                // Clear the stack back to a table body context.
                $this->context->parser->openElements->clearBackToTableBodyContext();

                // Insert an HTML element for the token, then switch the insertion
                // mode to "in row".
                $this->context->insertForeignElement($token, Namespaces::HTML);
                $this->context->insertionMode = new InRowInsertionMode($this->context);

                return;
            }

            if ($token->tagName === 'th' || $token->tagName === 'td') {
                // Parse error.
                // Clear the stack back to a table body context.
                $this->context->parser->openElements->clearBackToTableBodyContext();

                // Insert an HTML element for a "tr" start tag token with no
                // attributes, then switch the insertion mode to "in row".
                $this->context->insertForeignElement(new StartTagToken('tr'), Namespaces::HTML);
                $this->context->insertionMode = new InRowInsertionMode($this->context);

                // Reprocess the current token.
                $this->context->insertionMode->processToken($token);

                return;
            }

            if (
                $token->tagName === 'caption'
                || $token->tagName === 'col'
                || $token->tagName === 'colgroup'
                || $token->tagName === 'tbody'
                || $token->tagName === 'tfoot'
                || $token->tagName === 'thead'
            ) {
                $this->extracted($token);

                return;
            }
        } elseif ($token instanceof EndTagToken) {
            if ($token->tagName === 'tbody' || $token->tagName === 'thead' || $token->tagName === 'tfoot') {
                // If the stack of open elements does not have an element in table
                // scope that is an HTML element with the same tag name as the
                // token, this is a parse error; ignore the token.
                if (!$this->context->parser->openElements->hasElementInTableScope($token->tagName, Namespaces::HTML)) {
                    // Parse error.
                    // Ignore the token.
                    return;
                }

                // Clear the stack back to a table body context.
                $this->context->parser->openElements->clearBackToTableBodyContext();

                // Pop the current node from the stack of open elements. Switch the
                // insertion mode to "in table".
                $this->context->parser->openElements->pop();
                $this->context->insertionMode = new InTableInsertionMode($this->context);

                return;
            }

            if ($token->tagName === 'table') {
                $this->extracted($token);

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
                || $token->tagName === 'tr'
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
        // If the stack of open elements does not have a tbody, thead, or
        // tfoot element in table scope, this is a parse error; ignore the
        // token.
        if (
            !$this->context->parser->openElements->hasElementInTableScope('tbody', Namespaces::HTML)
            && !$this->context->parser->openElements->hasElementInTableScope('tbody', Namespaces::HTML)
            && !$this->context->parser->openElements->hasElementInTableScope('tbody', Namespaces::HTML)
        ) {
            // Parse error.
            // Ignore the token.
            return;
        }

        // Clear the stack back to a table body context.
        $this->context->parser->openElements->clearBackToTableBodyContext();

        // Pop the current node from the stack of open elements. Switch the
        // insertion mode to "in table".
        $this->context->parser->openElements->pop();
        $this->context->insertionMode = new InTableInsertionMode($this->context);

        // Reprocess the token.
        $this->context->insertionMode->processToken($token);
    }
}
