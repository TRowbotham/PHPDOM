<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Element\HTML\HTMLTableCellElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intd
 */
class InCellInsertionMode extends InsertionMode
{
    public function processToken(Token $token): void
    {
        if ($token instanceof EndTagToken) {
            if ($token->tagName === 'td' || $token->tagName === 'th') {
                // If the stack of open elements does not have an element in table
                // scope that is an HTML element with the same tag name as that of
                // the token, then this is a parse error; ignore the token.
                if (!$this->context->parser->openElements->hasElementInTableScope($token->tagName, Namespaces::HTML)) {
                    // Parse error.
                    // Ignore the token.
                    return;
                }

                // Generate implied end tags.
                $this->context->generateImpliedEndTags();

                // Now, if the current node is not an HTML element with the same
                // tag name as the token, then this is a parse error.
                if (
                    !$this->context->isHTMLElementWithName(
                        $this->context->parser->openElements->bottom(),
                        $token->tagName
                    )
                ) {
                    // Parse error
                }

                // Pop elements from the stack of open elements stack until an
                // HTML element with the same tag name as the token has been popped
                // from the stack.
                while (!$this->context->parser->openElements->isEmpty()) {
                    if (
                        $this->context->isHTMLElementWithName(
                            $this->context->parser->openElements->pop(),
                            $token->tagName
                        )
                    ) {
                        break;
                    }
                }

                // Clear the list of active formatting elements up to the last
                // marker.
                $this->context->activeFormattingElements->clearUpToLastMarker();

                // Switch the insertion mode to "in row".
                $this->context->insertionMode = new InRowInsertionMode($this->context);

                return;
            }

            if (
                $token->tagName === 'body'
                || $token->tagName === 'caption'
                || $token->tagName === 'col'
                || $token->tagName === 'colgroup'
                || $token->tagName === 'html'
            ) {
                // Parse error.
                // Ignore the token.

                return;
            }

            if (
                $token->tagName === 'table'
                || $token->tagName === 'tbody'
                || $token->tagName === 'tfoot'
                || $token->tagName === 'thead'
                || $token->tagName === 'tr'
            ) {
                // If the stack of open elements does not have an element in table
                // scope that is an HTML element with the same tag name as that of
                // the token, then this is a parse error; ignore the token.
                if (!$this->context->parser->openElements->hasElementInTableScope($token->tagName, Namespaces::HTML)) {
                    // Parse error.
                    // Ignore the token.
                    return;
                }

                // Otherwise, close the cell and reprocess the token.
                $this->closeCell();
                $this->context->insertionMode->processToken($token);

                return;
            }
        } elseif (
            $token instanceof StartTagToken
            && (
                $token->tagName === 'caption'
                || $token->tagName === 'col'
                || $token->tagName === 'colgroup'
                || $token->tagName === 'tbody'
                || $token->tagName === 'td'
                || $token->tagName === 'tfoot'
                || $token->tagName === 'th'
                || $token->tagName === 'thead'
                || $token->tagName === 'tr'
            )
        ) {
            // If the stack of open elements does not have a td or th element
            // in table scope, then this is a parse error; ignore the token.
            // (fragment case)
            if (
                !$this->context->parser->openElements->hasElementInTableScope('td', Namespaces::HTML)
                && !$this->context->parser->openElements->hasElementInTableScope(
                    'th',
                    Namespaces::HTML
                )
            ) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Otherwise, close the cell and reprocess the token.
            $this->closeCell();
            $this->context->insertionMode->processToken($token);

            return;
        }

        // Process the token using the rules for the "in body" insertion
        // mode.
        (new InBodyInsertionMode($this->context))->processToken($token);
    }

    /**
     * Performs the steps necessary to close a table cell (td) element.
     */
    private function closeCell(): void
    {
        // Generate implied end tags.
        $this->context->generateImpliedEndTags();

        // If the current node is not now a td element or a th element, then
        // this is a parse error.
        if (!$this->context->parser->openElements->bottom() instanceof HTMLTableCellElement) {
            // Parse error.
        }

        // Pop elements from the stack of open elements stack until a td
        // element or a th element has been popped from the stack.
        while (!$this->context->parser->openElements->isEmpty()) {
            if ($this->context->parser->openElements->pop() instanceof HTMLTableCellElement) {
                break;
            }
        }

        // Clear the list of active formatting elements up to the last marker.
        $this->context->activeFormattingElements->clearUpToLastMarker();

        // Switch the insertion mode to "in row".
        $this->context->insertionMode = new InRowInsertionMode($this->context);

        // NOTE: The stack of open elements cannot have both a td and a th
        // element in table scope at the same time, nor can it have neither
        // when the close the cell algorithm is invoked.
    }
}
