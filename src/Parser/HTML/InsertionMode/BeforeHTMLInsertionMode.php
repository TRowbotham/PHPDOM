<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\DoctypeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#the-before-html-insertion-mode
 */
class BeforeHTMLInsertionMode extends InsertionMode
{
    public function processToken(Token $token): void
    {
        if ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
            return;
        }

        if ($token instanceof CommentToken) {
            // Insert a comment as the last child of the Document object.
            $this->context->insertComment($token, [$this->context->document, 'beforeend']);

            return;
        }

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
            // Ignore the token.
            return;
        }

        if ($token instanceof StartTagToken && $token->tagName === 'html') {
            // Create an element for the token in the HTML namespace, with the
            // Document as the intended parent. Append it to the Document
            // object. Put this element in the stack of open elements.
            $node = $this->context->createElementForToken($token, Namespaces::HTML, $this->context->document);
            $this->context->document->appendChild($node);
            $this->context->parser->openElements->push($node);

            // TODO: If the Document is being loaded as part of navigation of a
            // browsing context, run these steps:

            // Switch the insertion mode to "before head".
            $this->context->insertionMode = new BeforeHeadInsertionMode($this->context);

            return;
        }

        if ($token instanceof EndTagToken) {
            if (
                $token->tagName === 'head'
                || $token->tagName === 'body'
                || $token->tagName === 'html'
                || $token->tagName === 'br'
            ) {
                // Act as described in the "anything else" entry below.
                $this->beforeHTMLInsertionModeAnythingElse($token);

                return;
            }

            // Parse error.
            // Ignore the token.
            return;
        }

        $this->beforeHTMLInsertionModeAnythingElse($token);
    }

    /**
     * The "before html" insertion mode's "anything else" steps.
     */
    private function beforeHTMLInsertionModeAnythingElse(Token $token): void
    {
        // Create an html element whose node document is the Document
        // object. Append it to the Document object. Put this element in
        // the stack of open elements.
        $node = ElementFactory::create($this->context->document, 'html', Namespaces::HTML);
        $this->context->document->appendChild($node);
        $this->context->parser->openElements->push($node);

        // TODO: If the Document is being loaded as part of navigation of a
        // browsing context, then: run the application cache selection
        // algorithm with no manifest, passing it the Document object.

        // Switch the insertion mode to "before head", then reprocess the token.
        $this->context->insertionMode = new BeforeHeadInsertionMode($this->context);
        $this->context->insertionMode->processToken($token);
    }
}
