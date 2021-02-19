<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Element\HTML\HTMLTableElement;
use Rowbot\DOM\Element\HTML\HTMLTableRowElement;
use Rowbot\DOM\Element\HTML\HTMLTableSectionElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\DoctypeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\EOFToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\Token;
use Rowbot\DOM\Utils;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intable
 */
class InTableInsertionMode extends InsertionMode
{
    use InTableInsertionModeAnythingElseTrait;

    public function processToken(Token $token): void
    {
        if (
            $token instanceof CharacterToken
            && (
                ($currentNode = $this->context->parser->openElements->bottom()) instanceof HTMLTableElement
                || $currentNode instanceof HTMLTableSectionElement
                || $currentNode instanceof HTMLTableRowElement
            )
        ) {
            // Let the pending table character tokens be an empty list of
            // tokens.
            $this->context->pendingTableCharacterTokens = [];

            // Let the original insertion mode be the current insertion mode.
            $this->context->originalInsertionMode = $this->context->insertionMode;

            // Switch the insertion mode to "in table text" and reprocess the
            // token.
            $this->context->insertionMode = new InTableTextInsertionMode($this->context);
            $this->context->insertionMode->processToken($token);

            return;
        }

        if ($token instanceof CommentToken) {
            // Insert a comment.
            $this->context->insertComment($token);

            return;
        }

        if ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
            return;
        }

        if ($token instanceof StartTagToken) {
            if ($token->tagName === 'caption') {
                // Clear the stack back to a table context.
                $this->context->parser->openElements->clearBackToTableContext();

                // Insert a marker at the end of the list of active formatting
                // elements.
                $this->context->activeFormattingElements->insertMarker();

                // Insert an HTML element for the token, then switch the insertion
                // mode to "in caption".
                $this->context->insertForeignElement($token, Namespaces::HTML);
                $this->context->insertionMode = new InCaptionInsertionMode($this->context);

                return;
            }

            if ($token->tagName === 'colgroup') {
                // Clear the stack back to a table context.
                $this->context->parser->openElements->clearBackToTableContext();

                // Insert an HTML element for the token, then switch the insertion
                // mode to "in column group".
                $this->context->insertForeignElement($token, Namespaces::HTML);
                $this->context->insertionMode = new InColumnGroupInsertionMode($this->context);

                return;
            }

            if ($token->tagName === 'col') {
                // Clear the stack back to a table context.
                $this->context->parser->openElements->clearBackToTableContext();

                // Insert an HTML element for a "colgroup" start tag token with no
                // attributes, then switch the insertion mode to "in column group".
                $this->context->insertForeignElement(new StartTagToken('colgroup'), Namespaces::HTML);
                $this->context->insertionMode = new InColumnGroupInsertionMode($this->context);

                // Reprocess the current token.
                $this->context->insertionMode->processToken($token);

                return;
            }

            if ($token->tagName === 'tbody' || $token->tagName === 'tfoot' || $token->tagName === 'thead') {
                // Clear the stack back to a table context.
                $this->context->parser->openElements->clearBackToTableContext();

                // Insert an HTML element for the token, then switch the insertion
                // mode to "in table body".
                $this->context->insertForeignElement($token, Namespaces::HTML);
                $this->context->insertionMode = new InTableBodyInsertionMode($this->context);

                return;
            }

            if ($token->tagName === 'td' || $token->tagName === 'th' || $token->tagName === 'tr') {
                // Clear the stack back to a table context.
                $this->context->parser->openElements->clearBackToTableContext();

                // Insert an HTML element for a "tbody" start tag token with no
                // attributes, then switch the insertion mode to "in table body".
                $this->context->insertForeignElement(new StartTagToken('tbody'), Namespaces::HTML);
                $this->context->insertionMode = new InTableBodyInsertionMode($this->context);

                // Reprocess the current token.
                $this->context->insertionMode->processToken($token);

                return;
            }

            if ($token->tagName === 'table') {
                // Parse error.
                // If the stack of open elements does not have a table element
                // in table scope, ignore the token.
                if (!$this->context->parser->openElements->hasElementInTableScope('table', Namespaces::HTML)) {
                    // Ignore the token.
                    return;
                }

                // Pop elements from this stack until a table element has been
                // popped from the stack.
                while (!$this->context->parser->openElements->isEmpty()) {
                    $popped = $this->context->parser->openElements->pop();

                    if ($popped instanceof HTMLTableElement) {
                        break;
                    }
                }

                // Reset the insertion mode appropriately.
                $this->context->resetInsertionMode();

                // Reprocess the token.
                $this->context->insertionMode->processToken($token);

                return;
            }

            if ($token->tagName === 'style' || $token->tagName === 'script' || $token->tagName === 'template') {
                // Process the token using the rules for the "in head" insertion mode.
                (new InHeadInsertionMode($this->context))->processToken($token);

                return;
            }

            if ($token->tagName === 'input') {
                // If the token does not have an attribute with the name "type", or
                // if it does, but that attribute's value is not an ASCII
                // case-insensitive match for the string "hidden", then: act as
                // described in the "anything else" entry below.
                $typeAttr = null;

                foreach ($token->attributes as $attr) {
                    if ($attr->name === 'type') {
                        $typeAttr = $attr;

                        break;
                    }
                }

                if ($typeAttr === null || Utils::toASCIILowercase($typeAttr->value) !== 'hidden') {
                    $this->inTableInsertionModeAnythingElse($token);

                    return;
                }

                // Parse error.
                // Insert an HTML element for the token.
                $this->context->insertForeignElement($token, Namespaces::HTML);

                // Pop that input element off the stack of open elements.
                $this->context->parser->openElements->pop();

                // Acknowledge the token's self-closing flag, if it is set.
                if ($token->isSelfClosing()) {
                    $token->acknowledge();
                }

                return;
            }

            if ($token->tagName === 'form') {
                // Parse error.
                // If there is a template element on the stack of open elements, or
                // if the form element pointer is not null, ignore the token.
                if (
                    $this->context->parser->openElements->containsTemplateElement()
                    || $this->context->parser->formElementPointer !== null
                ) {
                    // Ignore the token.
                    return;
                }

                // Insert an HTML element for the token, and set the form element
                // pointer to point to the element created.
                $this->context->parser->formElementPointer = $this->context->insertForeignElement(
                    $token,
                    Namespaces::HTML
                );

                // Pop that form element off the stack of open elements.
                $this->context->parser->openElements->pop();

                return;
            }
        } elseif ($token instanceof EndTagToken) {
            if ($token->tagName === 'table') {
                // If the stack of open elements does not have a table element in
                // table scope, this is a parse error; ignore the token.
                if (!$this->context->parser->openElements->hasElementInTableScope('table', Namespaces::HTML)) {
                    // Parse error.
                    // Ignore the token.
                    return;
                }

                // Pop elements from this stack until a table element has been
                // popped from the stack.
                while (!$this->context->parser->openElements->isEmpty()) {
                    $popped = $this->context->parser->openElements->pop();

                    if ($popped instanceof HTMLTableElement) {
                        break;
                    }
                }

                // Reset the insertion mode appropriately.
                $this->context->resetInsertionMode();

                return;
            }

            if (
                $token->tagName === 'body'
                || $token->tagName === 'caption'
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

            if ($token->tagName === 'template') {
                // Process the token using the rules for the "in head" insertion mode.
                (new InHeadInsertionMode($this->context))->processToken($token);

                return;
            }
        } elseif ($token instanceof EOFToken) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            (new InBodyInsertionMode($this->context))->processToken($token);

            return;
        }

        $this->inTableInsertionModeAnythingElse($token);
    }
}
