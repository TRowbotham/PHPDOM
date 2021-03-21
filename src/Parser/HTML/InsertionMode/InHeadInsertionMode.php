<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Element\HTML\HTMLElement;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Encoding\EncodingUtils;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\HTML\ParserContext;
use Rowbot\DOM\Parser\HTML\TokenizerState;
use Rowbot\DOM\Parser\HTML\TreeBuilderContext;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\DoctypeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\Token;
use Rowbot\DOM\Utils;

use function preg_match;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inhead
 */
class InHeadInsertionMode extends InsertionMode
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

            if (
                $token->tagName === 'base'
                || $token->tagName === 'basefont'
                || $token->tagName === 'bgsound'
                || $token->tagName === 'link'
            ) {
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

            if ($token->tagName === 'meta') {
                // Insert an HTML element for the token. Immediately pop the current
                // node off the stack of open elements.
                $node = $this->insertForeignElement($context, $token, Namespaces::HTML);
                $context->parser->openElements->pop();

                // Acknowledge the token's self-closing flag, if it is set.
                if ($token->isSelfClosing()) {
                    $token->acknowledge();
                }

                // If the element has a charset attribute, and getting an encoding
                // from its value results in an encoding, and the confidence is
                // currently tentative, then change the encoding to the resulting
                // encoding. Otherwise, if the element has an http-equiv attribute
                // whose value is an ASCII case-insensitive match for the string
                // "Content-Type", and the element has a content attribute, and
                // applying the algorithm for extracting a character encoding
                // from a meta element to that attribute's value returns an
                // encoding, and the confidence is currently tentative, then
                // change the encoding to the extracted encoding.
                $charset = $node->getAttribute('charset');

                if (
                    $charset !== null
                    && EncodingUtils::getEncoding($charset) !== false
                    && $context->parser->encodingConfidence === ParserContext::CONFIDENCE_TENTATIVE
                ) {
                    // TODO: change the encoding to the resulting encoding
                } elseif (
                    ($attr = $node->getAttribute('http-equiv')) !== null
                    && Utils::toASCIILowercase($attr) === 'content-type'
                    && $node->hasAttribute('content')
                ) {
                    // TODO
                }

                return;
            }

            if ($token->tagName === 'title') {
                // Follow the generic RCDATA element parsing algorithm.
                $this->parseGenericTextElement($context, $token, self::RCDATA_ELEMENT_ALGORITHM);

                return;
            }

            if (
                ($token->tagName === 'noscript' && $context->parser->isScriptingEnabled)
                || ($token->tagName === 'noframes' || $token->tagName === 'style')
            ) {
                // Follow the generic raw text element parsing algorithm.
                $this->parseGenericTextElement($context, $token, self::RAW_TEXT_ELEMENT_ALGORITHM);

                return;
            }

            if ($token->tagName === 'noscript' && !$context->parser->isScriptingEnabled) {
                // Insert an HTML element for the token.
                $this->insertForeignElement($context, $token, Namespaces::HTML);

                // Switch the insertion mode to "in head noscript".
                $context->insertionMode = new InHeadNoScriptInsertionMode();

                return;
            }

            if ($token->tagName === 'script') {
                // Let the adjusted insertion location be the appropriate place for inserting a node.
                $adjustedInsertionLocation = $this->getAppropriatePlaceForInsertingNode($context);

                // Create an element for the token in the HTML namespace, with the
                // intended parent being the element in which the adjusted insertion
                // location finds itself.
                $node = $this->createElementForToken(
                    $token,
                    Namespaces::HTML,
                    $adjustedInsertionLocation[0]
                );

                // TODO: Mark the element as being "parser-inserted" and unset the
                // element's "non-blocking" flag.
                //
                // NOTE: This ensures that, if the script is external, any
                // document.write() calls in the script will execute in-line,
                // instead of blowing the document away, as would happen in most
                // other cases. It also prevents the script from executing until
                // the end tag is seen.

                // If the parser was originally created for the HTML fragment
                // parsing algorithm, then mark the script element as "already
                // started". (fragment case)
                if ($context->parser->isFragmentCase) {
                    // TODO
                }

                // TODO: If the parser was invoked via the document.write() or
                // document.writeln() methods, then optionally mark the script
                // element as "already started". (For example, the user agent might
                // use this clause to prevent execution of cross-origin scripts
                // inserted via document.write() under slow network conditions, or
                // when the page has already taken a long time to load.)

                // Insert the newly created element at the adjusted insertion
                // location.
                $this->insertNode($node, $adjustedInsertionLocation);

                // Push the element onto the stack of open elements so that it is
                // the new current node.
                $context->parser->openElements->push($node);

                // Switch the tokenizer to the script data state.
                $context->parser->tokenizerState = TokenizerState::SCRIPT_DATA;

                // Let the original insertion mode be the current insertion mode.
                $context->originalInsertionMode = $context->insertionMode;

                // Switch the insertion mode to "text".
                $context->insertionMode = new TextInsertionMode();

                return;
            }

            if ($token->tagName === 'template') {
                // Insert an HTML element for the token.
                $this->insertForeignElement($context, $token, Namespaces::HTML);

                // Insert a marker at the end of the list of active formatting
                // elements.
                $context->activeFormattingElements->insertMarker();

                // Set the frameset-ok flag to "not ok".
                $context->framesetOk = 'not ok';

                // Switch the insertion mode to "in template".
                $context->insertionMode = new InTemplateInsertionMode();

                // Push "in template" onto the stack of template insertion modes so
                // that it is the new current template insertion mode.
                $context->templateInsertionModes->push(InTemplateInsertionMode::class);

                return;
            }

            if ($token->tagName === 'head') {
                // Parse error.
                // Ignore the token.
                return;
            }
        } elseif ($token instanceof EndTagToken) {
            if ($token->tagName === 'head') {
                // Pop the current node (which will be the head element) off the
                // stack of open elements.
                $context->parser->openElements->pop();

                // Switch the insertion mode to "after head".
                $context->insertionMode = new AfterHeadInsertionMode();

                return;
            }

            if ($token->tagName === 'body' || $token->tagName === 'html' || $token->tagName === 'br') {
                // Act as described in the "anything else" entry below.
                $this->inHeadInsertionModeAnythingElse($context, $token);

                return;
            }

            if ($token->tagName === 'template') {
                if (!$context->parser->openElements->containsTemplateElement()) {
                    // Parse error.
                    // Ignore the token.
                    return;
                }

                // Generate all implied end tags thoroughly.
                $this->generateAllImpliedEndTagsThoroughly($context);

                // If the current node is not a template element, then this is
                // a parse error.
                $currentNode = $context->parser->openElements->bottom();

                if (!$currentNode instanceof HTMLTemplateElement) {
                    // Parse error
                }

                // Pop elements from the stack of open elements until a
                // template element has been popped from the stack.
                while (!$context->parser->openElements->isEmpty()) {
                    $popped = $context->parser->openElements->pop();

                    if ($popped instanceof HTMLTemplateElement) {
                        break;
                    }
                }

                // Clear the list of active formatting elements up to the last
                // marker.
                $context->activeFormattingElements->clearUpToLastMarker();

                // Pop the current template insertion mode off the stack of
                // template insertion modes.
                $context->templateInsertionModes->pop();

                // Reset the insertion mode appropriately.
                $context->resetInsertionMode();

                return;
            }

            // Parse error.
            // Ignore the token.
            return;
        }

        $this->inHeadInsertionModeAnythingElse($context, $token);
    }

    /**
     * The "in head" insertion mode "anything else" steps.
     */
    private function inHeadInsertionModeAnythingElse(TreeBuilderContext $context, Token $token): void
    {
        // Pop the current node (which will be the head element) off the
        // stack of open elements.
        $context->parser->openElements->pop();

        // Switch the insertion mode to "after head".
        $context->insertionMode = new AfterHeadInsertionMode();

        // Reprocess the token.
        $context->insertionMode->processToken($context, $token);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#generate-all-implied-end-tags-thoroughly
     */
    private function generateAllImpliedEndTagsThoroughly(TreeBuilderContext $context): void
    {
        $pattern = '/^(caption|colgroup|dd|dt|li|optgroup|option|p|rb|rp|rt';
        $pattern .= '|rtc|tbody|td|tfoot|th|thead|tr)$/';

        foreach ($context->parser->openElements as $currentNode) {
            if (
                !$currentNode instanceof HTMLElement
                || !preg_match($pattern, $currentNode->localName)
            ) {
                break;
            }

            $context->parser->openElements->pop();
        }
    }
}
