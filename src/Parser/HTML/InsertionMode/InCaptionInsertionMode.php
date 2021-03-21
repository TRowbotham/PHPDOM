<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Element\HTML\HTMLTableCaptionElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\HTML\TreeBuilderContext;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\TagToken;
use Rowbot\DOM\Parser\Token\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-incaption
 */
class InCaptionInsertionMode extends AbstractInsertionMode implements InsertionMode
{
    public function processToken(TreeBuilderContext $context, Token $token): void
    {
        if ($token instanceof EndTagToken) {
            if ($token->tagName === 'caption') {
                // If the stack of open elements does not have a caption element in
                // table scope, this is a parse error; ignore the token. (fragment
                // case)
                if (!$context->parser->openElements->hasElementInTableScope('caption', Namespaces::HTML)) {
                    // Parse error.
                    // Ignore the token.
                    return;
                }

                // Generate implied end tags.
                $this->generateImpliedEndTags($context);

                // Now, if the current node is not a caption element, then this is
                // a parse error.
                $currentNode = $context->parser->openElements->bottom();

                if (!$currentNode instanceof HTMLTableCaptionElement) {
                    // Parse error.
                }

                // Pop elements from this stack until a caption element has been
                // popped from the stack.
                while (!$context->parser->openElements->isEmpty()) {
                    $popped = $context->parser->openElements->pop();

                    if ($popped instanceof HTMLTableCaptionElement) {
                        break;
                    }
                }

                // Clear the list of active formatting elements up to the last
                // marker.
                $context->activeFormattingElements->clearUpToLastMarker();

                // Switch the insertion mode to "in table".
                $context->insertionMode = new InTableInsertionMode();

                return;
            }

            if ($token->tagName === 'table') {
                $this->extracted($context, $token);

                return;
            }

            if (
                $token->tagName === 'body'
                || $token->tagName === 'col'
                || $token->tagName === 'colgroup'
                || $token->tagName === 'html'
                || $token->tagName === 'tbody'
                || $token->tagName === 'td'
                || $token->tagName === 'tfoot'
                || $token->tagName === 'th'
                || $token->tagName === 'thead'
                || $token->tagName === 'tr'
            ) {
                // Parse error.
                // Ignore the token.
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
            $this->extracted($context, $token);

            return;
        }

        // Process the token using the rules for the "in body" insertion
        // mode.
        (new InBodyInsertionMode())->processToken($context, $token);
    }

    private function extracted(TreeBuilderContext $context, TagToken $token): void
    {
        // If the stack of open elements does not have a caption element
        // in table scope, this is a parse error; ignore the token.
        // (fragment case)
        if (!$context->parser->openElements->hasElementInTableScope('caption', Namespaces::HTML)) {
            // Parse error.
            // Ignore the token.

            return;
        }

        // Generate implied end tags.
        $this->generateImpliedEndTags($context);

        // Now, if the current node is not a caption element, then this
        // is a parse error.
        $currentNode = $context->parser->openElements->bottom();

        if (!$currentNode instanceof HTMLTableCaptionElement) {
            // Parse error.
        }

        // Pop elements from this stack until a caption element has
        // been popped from the stack.
        while (!$context->parser->openElements->isEmpty()) {
            $popped = $context->parser->openElements->pop();

            if ($popped instanceof HTMLTableCaptionElement) {
                break;
            }
        }

        // Clear the list of active formatting elements up to the last
        // marker.
        $context->activeFormattingElements->clearUpToLastMarker();

        // Switch the insertion mode to "in table".
        $context->insertionMode = new InTableInsertionMode();

        // Reprocess the token.
        $context->insertionMode->processToken($context, $token);
    }
}
