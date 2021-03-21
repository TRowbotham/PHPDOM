<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Parser\HTML\TreeBuilderContext;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\DoctypeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\EOFToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intemplate
 */
class InTemplateInsertionMode extends InsertionMode
{
    public function processToken(TreeBuilderContext $context, Token $token): void
    {
        if (
            $token instanceof CharacterToken
            || $token instanceof CommentToken
            || $token instanceof DoctypeToken
        ) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            (new InBodyInsertionMode())->processToken($context, $token);

            return;
        }

        if ($token instanceof StartTagToken) {
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
                // Process the token using the rules for the "in head" insertion mode.
                (new InHeadInsertionMode())->processToken($context, $token);

                return;
            }

            if (
                $token->tagName === 'caption'
                || $token->tagName === 'colgroup'
                || $token->tagName === 'tbody'
                || $token->tagName === 'tfoot'
                || $token->tagName === 'thead'
            ) {
                // Pop the current template insertion mode off the stack of template
                // insertion modes.
                $context->templateInsertionModes->pop();

                // Push "in table" onto the stack of template insertion modes so
                // that it is the new current template insertion mode.
                $context->templateInsertionModes->push(InTableInsertionMode::class);

                // Switch the insertion mode to "in table", and reprocess the token.
                $context->insertionMode = new InTableInsertionMode();
                $context->insertionMode->processToken($context, $token);

                return;
            }

            if ($token->tagName === 'col') {
                // Pop the current template insertion mode off the stack of template
                // insertion modes.
                $context->templateInsertionModes->pop();

                // Push "in column group" onto the stack of template insertion modes
                // so that it is the new current template insertion mode.
                $context->templateInsertionModes->push(InColumnGroupInsertionMode::class);

                // Switch the insertion mode to "in column group", and reprocess the
                // token.
                $context->insertionMode = new InColumnGroupInsertionMode();
                $context->insertionMode->processToken($context, $token);

                return;
            }

            if ($token->tagName === 'tr') {
                // Pop the current template insertion mode off the stack of template
                // insertion modes.
                $context->templateInsertionModes->pop();

                // Push "in table body" onto the stack of template insertion modes
                // so that it is the new current template insertion mode.
                $context->templateInsertionModes->push(InTableBodyInsertionMode::class);

                // Switch the insertion mode to "in table body", and reprocess the
                // token.
                $context->insertionMode = new InTableBodyInsertionMode();
                $context->insertionMode->processToken($context, $token);

                return;
            }

            if ($token->tagName === 'td' || $token->tagName === 'th') {
                // Pop the current template insertion mode off the stack of template
                // insertion modes.
                $context->templateInsertionModes->pop();

                // Push "in row" onto the stack of template insertion modes so that
                // it is the new current template insertion mode.
                $context->templateInsertionModes->push(InRowInsertionMode::class);

                // Switch the insertion mode to "in row", and reprocess the token.
                $context->insertionMode = new InRowInsertionMode();
                $context->insertionMode->processToken($context, $token);

                return;
            }

            // Pop the current template insertion mode off the stack of template
            // insertion modes.
            $context->templateInsertionModes->pop();

            // Push "in body" onto the stack of template insertion modes so that
            // it is the new current template insertion mode.
            $context->templateInsertionModes->push(InBodyInsertionMode::class);

            // Switch the insertion mode to "in body", and reprocess the token.
            $context->insertionMode = new InBodyInsertionMode();
            $context->insertionMode->processToken($context, $token);

            return;
        }

        if ($token instanceof EndTagToken) {
            if ($token->tagName === 'template') {
                // Process the token using the rules for the "in head" insertion mode.
                (new InHeadInsertionMode())->processToken($context, $token);

                return;
            }

            // Parse error.
            // Ignore the token.
            return;
        }

        if ($token instanceof EOFToken) {
            // If there is no template element on the stack of open elements,
            // then stop parsing. (fragment case)
            if (!$context->parser->openElements->containsTemplateElement()) {
                $this->stopParsing($context);
            } else {
                // Parse  error
            }

            // Pop elements from the stack of open elements until a template
            // element has been popped from the stack.
            while (!$context->parser->openElements->isEmpty()) {
                $popped = $context->parser->openElements->pop();

                if ($popped instanceof HTMLTemplateElement) {
                    break;
                }
            }

            // Clear the list of active formatting elements up to the last
            // marker.
            $context->activeFormattingElements->clearUpToLastMarker();

            // Pop the current template insertion mode off the stack of template
            // insertion modes.
            $context->templateInsertionModes->pop();

            // Reset the insertion mode appropriately.
            $context->resetInsertionMode();

            // Reprocess the token.
            $context->insertionMode->processToken($context, $token);
        }
    }
}
