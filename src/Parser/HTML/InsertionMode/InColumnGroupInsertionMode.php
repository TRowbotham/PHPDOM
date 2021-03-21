<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Element\HTML\HTMLTableColElement;
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
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-incolgroup
 */
class InColumnGroupInsertionMode extends InsertionMode
{
    public function processToken(TreeBuilderContext $context, Token $token): void
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

            if ($token->tagName === 'col') {
                // Insert an HTML element for the token. Immediately pop the current
                // node off the stack of open elements.
                $this->insertForeignElement($context, $token, Namespaces::HTML);
                $context->parser->openElements->pop();

                // Acknowledge the token's self-closing flag, if it is set.
                if ($token->isSelfClosing()) {
                    $token->acknowledge();
                }

                return;
            }

            if ($token->tagName === 'template') {
                // Process the token using the rules for the "in head" insertion
                // mode.
                (new InHeadInsertionMode())->processToken($context, $token);

                return;
            }
        } elseif ($token instanceof EndTagToken) {
            if ($token->tagName === 'colgroup') {
                // If the current node is not a colgroup element, then this is a
                // parse error; ignore the token.
                $currentNode = $context->parser->openElements->bottom();

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
                $context->parser->openElements->pop();
                $context->insertionMode = new InTableInsertionMode();

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
                (new InHeadInsertionMode())->processToken($context, $token);

                return;
            }
        } elseif ($token instanceof EOFToken) {
            // Process the token using the rules for the "in body" insertion
            // mode.
            (new InBodyInsertionMode())->processToken($context, $token);

            return;
        }

        // If the current node is not a colgroup element, then this is a
        // parse error; ignore the token.
        $currentNode = $context->parser->openElements->bottom();

        if (!($currentNode instanceof HTMLTableColElement && $currentNode->localName === 'colgroup')) {
            // Parse error.
            // Ignore the token.
            return;
        }

        // Otherwise, pop the current node from the stack of open
        // elements.
        $context->parser->openElements->pop();

        // Switch the insertion mode to "in table".
        $context->insertionMode = new InTableInsertionMode();

        // Reprocess the token.
        $context->insertionMode->processToken($context, $token);
    }
}
