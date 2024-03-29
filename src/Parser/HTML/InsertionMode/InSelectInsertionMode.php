<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Element\HTML\HTMLOptGroupElement;
use Rowbot\DOM\Element\HTML\HTMLOptionElement;
use Rowbot\DOM\Element\HTML\HTMLSelectElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\HTML\TreeBuilderContext;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\DoctypeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\EOFToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inselect
 */
class InSelectInsertionMode extends AbstractInsertionMode implements InsertionMode
{
    public function processToken(TreeBuilderContext $context, Token $token): void
    {
        if ($token instanceof CharacterToken) {
            if ($token->data === "\x00") {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Insert the token's character.
            $this->insertCharacter($context, $token);

            return;
        }

        if ($token instanceof CommentToken) {
            // Insert a comment.
            $this->insertComment($context, $token);

            return;
        }

        if ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.

            return;
        }

        if ($token instanceof StartTagToken) {
            if ($token->tagName === 'html') {
                // Process the token using the rules for the "in body" insertion mode.
                (new InBodyInsertionMode())->processToken($context, $token);

                return;
            }

            if ($token->tagName === 'option') {
                // If the current node is an option element, pop that node from the
                // stack of open elements.
                if ($context->parser->openElements->bottom() instanceof HTMLOptionElement) {
                    $context->parser->openElements->pop();
                }

                // Insert an HTML element for the token.
                $this->insertForeignElement($context, $token, Namespaces::HTML);

                return;
            }

            if ($token->tagName === 'optgroup') {
                // If the current node is an option element, pop that node from the
                // stack of open elements.
                if ($context->parser->openElements->bottom() instanceof HTMLOptionElement) {
                    $context->parser->openElements->pop();
                }

                // If the current node is an optgroup element, pop that node from
                // the stack of open elements.
                if ($context->parser->openElements->bottom() instanceof HTMLOptGroupElement) {
                    $context->parser->openElements->pop();
                }

                // Insert an HTML element for the token.
                $this->insertForeignElement($context, $token, Namespaces::HTML);

                return;
            }

            if ($token->tagName === 'select') {
                // Parse error
                // If the stack of open elements does not have a select
                // element in select scope, ignore the token. (fragment case)
                if (!$context->parser->openElements->hasElementInSelectScope('select', Namespaces::HTML)) {
                    // Ignore the token.
                    return;
                }

                // Pop elements from the stack of open elements until a select
                // element has been popped from the stack.
                while (!$context->parser->openElements->isEmpty()) {
                    if ($context->parser->openElements->pop() instanceof HTMLSelectElement) {
                        break;
                    }
                }

                // Reset the insertion mode appropriately.
                $context->resetInsertionMode();

                return;
            }

            if ($token->tagName === 'input' || $token->tagName === 'keygen' || $token->tagName === 'textarea') {
                // Parse error
                // If the stack of open elements does not have a select
                // element in select scope, ignore the token. (fragment case)
                if (!$context->parser->openElements->hasElementInSelectScope('select', Namespaces::HTML)) {
                    // Ignore the token.
                    return;
                }

                // Pop elements from the stack of open elements until a select
                // element has been popped from the stack.
                while (!$context->parser->openElements->isEmpty()) {
                    if ($context->parser->openElements->pop() instanceof HTMLSelectElement) {
                        break;
                    }
                }

                // Reset the insertion mode appropriately.
                $context->resetInsertionMode();

                // Reprocess the token.
                $context->insertionMode->processToken($context, $token);

                return;
            }

            if ($token->tagName === 'script' || $token->tagName === 'template') {
                // Process the token using the rules for the "in head" insertion mode.
                (new InHeadInsertionMode())->processToken($context, $token);

                return;
            }
        } elseif ($token instanceof EndTagToken) {
            if ($token->tagName === 'optgroup') {
                // First, if the current node is an option element, and the node
                // immediately before it in the stack of open elements is an
                // optgroup element, then pop the current node from the stack of
                // open elements.
                $iterator = $context->parser->openElements->getIterator();
                $iterator->rewind();
                $iterator->next();

                if (
                    $context->parser->openElements->bottom() instanceof HTMLOptionElement
                    && $iterator->current() instanceof HTMLOptGroupElement
                ) {
                    $context->parser->openElements->pop();
                }

                // If the current node is an optgroup element, then pop that node
                // from the stack of open elements. Otherwise, this is a parse
                // error; ignore the token.
                if ($context->parser->openElements->bottom() instanceof HTMLOptGroupElement) {
                    $context->parser->openElements->pop();

                    return;
                }

                // Parse error.
                // Ignore the token.
                return;
            }

            if ($token->tagName === 'option') {
                // If the current node is an option element, then pop that node
                // from the stack of open elements. Otherwise, this is a parse
                // error; ignore the token.
                if ($context->parser->openElements->bottom() instanceof HTMLOptionElement) {
                    $context->parser->openElements->pop();

                    return;
                }

                // Parse error.
                // Ignore the token.
                return;
            }

            if ($token->tagName === 'select') {
                // If the stack of open elements does not have a select element in
                // select scope, this is a parse error; ignore the token. (fragment
                // case)
                if (!$context->parser->openElements->hasElementInSelectScope('select', Namespaces::HTML)) {
                    // Parse error.
                    // Ignore the token.
                    return;
                }

                // Pop elements from the stack of open elements until a select
                // element has been popped from the stack.
                while (!$context->parser->openElements->isEmpty()) {
                    if ($context->parser->openElements->pop() instanceof HTMLSelectElement) {
                        break;
                    }
                }

                // Reset the insertion mode appropriately.
                $context->resetInsertionMode();

                return;
            }

            if ($token->tagName === 'template') {
                // Process the token using the rules for the "in head" insertion mode.
                (new InHeadInsertionMode())->processToken($context, $token);

                return;
            }
        } elseif ($token instanceof EOFToken) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            (new InBodyInsertionMode())->processToken($context, $token);

            return;
        }

        // Parse error.
        // Ignore the token.
    }
}
