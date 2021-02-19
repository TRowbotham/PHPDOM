<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Element\HTML\HTMLTableColElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\DoctypeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\EOFToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-incolgroup
 */
class InColumnGroupInsertionMode extends InsertionMode
{
    public function processToken(Token $token): void
    {
        if (
            $token instanceof CharacterToken
            && (
                $token->data === "\x09"
                || $token->data === "\x0A"
                || $token->data === "\x0C"
                || $token->data === "\x0D"
                || $token->data === "\x20"
            )
        ) {
            // Insert the character.
            $this->context->insertCharacter($token);

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
            if ($token->tagName === 'html') {
                // Process the token using the rules for the "in body" insertion mode.
                (new InBodyInsertionMode($this->context))->processToken($token);

                return;
            }

            if ($token->tagName === 'col') {
                // Insert an HTML element for the token. Immediately pop the current
                // node off the stack of open elements.
                $this->context->insertForeignElement($token, Namespaces::HTML);
                $this->context->parser->openElements->pop();

                // Acknowledge the token's self-closing flag, if it is set.
                if ($token->isSelfClosing()) {
                    $token->acknowledge();
                }

                return;
            }

            if ($token->tagName === 'template') {
                // Process the token using the rules for the "in head" insertion
                // mode.
                (new InHeadInsertionMode($this->context))->processToken($token);

                return;
            }
        } elseif ($token instanceof EndTagToken) {
            if ($token->tagName === 'colgroup') {
                // If the current node is not a colgroup element, then this is a
                // parse error; ignore the token.
                $currentNode = $this->context->parser->openElements->bottom();

                if (
                    !(
                        $currentNode instanceof HTMLTableColElement
                        && $currentNode->localName === 'colgroup'
                    )
                ) {
                    // Parse error.
                    // Ignore the token.
                    return;
                }

                // Otherwise, pop the current node from the stack of open elements.
                // Switch the insertion mode to "in table".
                $this->context->parser->openElements->pop();
                $this->context->insertionMode = new InTableInsertionMode($this->context);

                return;
            }

            if ($token->tagName === 'col') {
                // Parse error.
                // Ignore the token.
                return;
            }

            if ($token->tagName === 'template') {
                // Process the token using the rules for the "in head" insertion
                // mode.
                (new InHeadInsertionMode($this->context))->processToken($token);

                return;
            }
        } elseif ($token instanceof EOFToken) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            (new InBodyInsertionMode($this->context))->processToken($token);

            return;
        }

        // If the current node is not a colgroup element, then this is a
        // parse error; ignore the token.
        $currentNode = $this->context->parser->openElements->bottom();

        if (!($currentNode instanceof HTMLTableColElement && $currentNode->localName === 'colgroup')) {
            // Parse error.
            // Ignore the token.
            return;
        }

        // Otherwise, pop the current node from the stack of open
        // elements.
        $this->context->parser->openElements->pop();

        // Switch the insertion mode to "in table".
        $this->context->insertionMode = new InTableInsertionMode($this->context);

        // Reprocess the token.
        $this->context->insertionMode->processToken($token);
    }
}
