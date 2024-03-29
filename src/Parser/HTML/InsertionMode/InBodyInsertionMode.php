<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Attr;
use Rowbot\DOM\DocumentMode;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\HTML\HTMLAnchorElement;
use Rowbot\DOM\Element\HTML\HTMLBodyElement;
use Rowbot\DOM\Element\HTML\HTMLButtonElement;
use Rowbot\DOM\Element\HTML\HTMLElement;
use Rowbot\DOM\Element\HTML\HTMLFormElement;
use Rowbot\DOM\Element\HTML\HTMLHeadingElement;
use Rowbot\DOM\Element\HTML\HTMLLIElement;
use Rowbot\DOM\Element\HTML\HTMLOptionElement;
use Rowbot\DOM\Element\HTML\HTMLParagraphElement;
use Rowbot\DOM\Element\SVG\SVGDescElement;
use Rowbot\DOM\Element\SVG\SVGForeignObjectElement;
use Rowbot\DOM\Element\SVG\SVGTitleElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Parser\HTML\TokenizerState;
use Rowbot\DOM\Parser\HTML\TreeBuilderContext;
use Rowbot\DOM\Parser\Marker;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\DoctypeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\EOFToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\TagToken;
use Rowbot\DOM\Parser\Token\Token;
use Rowbot\DOM\Utils;

use function preg_match;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-inbody
 */
class InBodyInsertionMode extends AbstractInsertionMode implements InsertionMode
{
    public function processToken(TreeBuilderContext $context, Token $token): void
    {
        if ($token instanceof CharacterToken) {
            if ($token->data === "\x00") {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements($context);

            // Insert the token's character.
            $this->insertCharacter($context, $token);

            if (
                $token->data !== "\x09"
                && $token->data !== "\x0A"
                && $token->data !== "\x0C"
                && $token->data !== "\x0D"
                && $token->data !== "\x20"
            ) {
                // Set the frameset-ok flag to "not ok".
                $context->framesetOk = 'not ok';
            }
        } elseif ($token instanceof CommentToken) {
            // Insert a comment.
            $this->insertComment($context, $token);
        } elseif ($token instanceof DoctypeToken) {
            // Parse error.
            // Ignore the token.
        } elseif ($token instanceof StartTagToken) {
            $this->processStartTagToken($context, $token);
        } elseif ($token instanceof EndTagToken) {
            $this->processEndTagToken($context, $token);
        } elseif ($token instanceof EOFToken) {
            // If the stack of template insertion modes is not empty, then
            // process the token using the rules for the "in template"
            // insertion mode.
            if (!$context->templateInsertionModes->isEmpty()) {
                (new InTemplateInsertionMode())->processToken($context, $token);

                return;
            }

            // If there is a node in the stack of open elements that is not
            // either a dd element, a dt element, an li element, an optgroup
            // element, an option element, a p element, an rb element, an rp
            // element, an rt element, an rtc element, a tbody element, a td
            // element, a tfoot element, a th element, a thead element, a tr
            // element, the body element, or the html element, then this is a
            // parse error.
            $pattern = '/^(dd|dt|li|optgroup|option|p|rb|rp|rt|rtc|';
            $pattern .= 'tbody|td|tfoot|th|thead|tr|body|html)$/';

            foreach ($context->parser->openElements as $el) {
                if (!($el instanceof HTMLElement && preg_match($pattern, $el->localName))) {
                    // Parse error.
                    break;
                }
            }

            // Stop parsing.
            $this->stopParsing($context);
        }
    }

    private function processStartTagToken(TreeBuilderContext $context, StartTagToken $token): void
    {
        if ($token->tagName === 'html') {
            // Parse error.
            // If there is a template element on the stack of open elements,
            // then ignore the token.
            if ($context->parser->openElements->containsTemplateElement()) {
                return;
            }

            // Otherwise, for each attribute on the token, check to see if the
            // attribute is already present on the top element of the stack of
            // open elements. If it is not, add the attribute and its
            // corresponding value to that element.
            $firstOnStack = $context->parser->openElements->top();

            foreach ($token->attributes as $attr) {
                $name = $attr->name;

                if (!$firstOnStack->hasAttribute($name)) {
                    $firstOnStack->setAttributeNode(new Attr(
                        $firstOnStack->getNodeDocument(),
                        $name,
                        $attr->value
                    ));
                }
            }

            return;
        }

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

        if ($token->tagName === 'body') {
            // Parse error.
            // If the second element on the stack of open elements is not a body
            // element, if the stack of open elements has only one node on it,
            // or if there is a template element on the stack of open elements,
            // then ignore the token. (fragment case)
            if (
                $context->parser->openElements->count() === 1
                || !$context->parser->openElements->itemAt(1) instanceof HTMLBodyElement
                || $context->parser->openElements->containsTemplateElement()
            ) {
                // Fragment case
                // Ignore the token.
                return;
            }

            // Otherwise, set the frameset-ok flag to "not ok"; then, for each
            // attribute on the token, check to see if the attribute is already
            // present on the body element (the second element) on the stack of
            // open elements, and if it is not, add the attribute and its
            // corresponding value to that element.
            $context->framesetOk = 'not ok';
            $body = $context->parser->openElements->itemAt(1);

            foreach ($token->attributes as $attr) {
                $name = $attr->name;

                if (!$body->hasAttribute($name)) {
                    $body->setAttribute($name, $attr->value);
                }
            }

            return;
        }

        if ($token->tagName === 'frameset') {
            // Parse error.
            // If the stack of open elements has only one node on it, or if the
            // second element on the stack of open elements is not a body
            // element, then ignore the token. (fragment case)
            $count = $context->parser->openElements->count();

            if ($count === 1 || !$context->parser->openElements->itemAt(1) instanceof HTMLBodyElement) {
                // Fragment case
                // Ignore the token
                return;
            }

            // If the frameset-ok flag is set to "not ok", ignore the token.
            if ($context->framesetOk === 'not ok') {
                // Ignore the token.
                return;
            }

            // Remove the second element on the stack of open elements from its
            // parent node, if it has one.
            $body = $context->parser->openElements->itemAt(1);
            $parent = $body->parentNode;

            if ($parent !== null) {
                $parent->removeChild($body);
            }

            // Pop all the nodes from the bottom of the stack of open elements,
            // from the current node up to, but not including, the root html
            // element.
            for ($i = $count - 1; $i > 0; $i--) {
                $context->parser->openElements->pop();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($context, $token, Namespaces::HTML);

            // Switch the insertion mode to "in frameset".
            $context->insertionMode = new InFrameSetInsertionMode();

            return;
        }

        if (
            preg_match(
                '/^(address|article|aside|blockquote|center|details|dialog|'
                . 'dir|div|dl|fieldset|figcaption|figure|footer|header|hgroup|'
                . 'main|menu|nav|ol|p|section|summary|ul)$/',
                $token->tagName
            )
        ) {
            // If the stack of open elements has a p element in button scope, then close a p element.
            if ($context->parser->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                $this->closePElement($context);
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($context, $token, Namespaces::HTML);

            return;
        }

        if (
            $token->tagName === 'h1'
            || $token->tagName === 'h2'
            || $token->tagName === 'h3'
            || $token->tagName === 'h4'
            || $token->tagName === 'h5'
            || $token->tagName === 'h6'
        ) {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($context->parser->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                $this->closePElement($context);
            }

            // If the current node is an HTML element whose tag name is one of
            // "h1", "h2", "h3", "h4", "h5", or "h6", then this is a parse
            // error; pop the current node off the stack of open elements.
            if ($context->parser->openElements->bottom() instanceof HTMLHeadingElement) {
                // Parse error.
                $context->parser->openElements->pop();
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($context, $token, Namespaces::HTML);

            return;
        }

        if ($token->tagName === 'pre' || $token->tagName === 'listing') {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($context->parser->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                $this->closePElement($context);
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($context, $token, Namespaces::HTML);

            // If the next token is a U+000A LINE FEED (LF) character
            // token, then ignore that token and move on to the next one.
            // (Newlines at the start of pre blocks are ignored as an authoring
            // convenience.)
            $this->ignoreNextLineFeed($context);

            // Set the frameset-ok flag to "not ok".
            $context->framesetOk = 'not ok';

            return;
        }

        if ($token->tagName === 'form') {
            // If the form element pointer is not null, and there is no
            // template element on the stack of open elements, then this is a
            // parse error; ignore the token.
            if (
                $context->parser->formElementPointer
                && !$context->parser->openElements->containsTemplateElement()
            ) {
                // Parse error.
                // Ignore the token.
            } else {
                // If the stack of open elements has a p element in button
                // scope, then close a p element.
                if ($context->parser->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                    $this->closePElement($context);
                }

                // Insert an HTML element for the token, and, if there is no
                // template element on the stack of open elements, set the
                // form element pointer to point to the element created.
                $node = $this->insertForeignElement($context, $token, Namespaces::HTML);

                if (!$context->parser->openElements->containsTemplateElement()) {
                    $context->parser->formElementPointer = $node;
                }
            }

            return;
        }

        if ($token->tagName === 'li') {
            // Set the frameset-ok flag to "not ok".
            $context->framesetOk = 'not ok';

            // Initialise node to be the current node (the bottommost node of
            // the stack).
            // Step "Loop".
            foreach ($context->parser->openElements as $node) {
                if ($node instanceof HTMLLIElement) {
                    // Generate implied end tags, except for li elements.
                    $this->generateImpliedEndTags($context, 'li');

                    // If the current node is not an li element, then this is a
                    // parse error.
                    $currentNode = $context->parser->openElements->bottom();

                    if (!$currentNode instanceof HTMLLIElement) {
                        // Parse error.
                    }

                    // Pop elements from the stack of open elements until an li
                    // element has been popped from the stack.
                    while (!$context->parser->openElements->isEmpty()) {
                        if ($context->parser->openElements->pop() instanceof HTMLLIElement) {
                            break;
                        }
                    }

                    // Jump to the step labeled done below.
                    break;
                }

                // If node is in the special category, but is not an address,
                // div, or p element, then jump to the step labeled done below.
                // Otherwise, set node to the previous entry in the stack
                // of open elements and return to the step labeled loop.
                if (
                    $this->isSpecialNode($node)
                    && !($node instanceof HTMLElement
                        && (($name = $node->localName) === 'address'
                            || $name === 'div'
                            || $name === 'p'
                        )
                    )
                ) {
                    break;
                }
            }

            // Step "Done".
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($context->parser->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                $this->closePElement($context);
            }

            // Finally, insert an HTML element for the token.
            $this->insertForeignElement($context, $token, Namespaces::HTML);

            return;
        }

        if ($token->tagName === 'dd' || $token->tagName === 'dt') {
            // Set the frameset-ok flag to "not ok".
            $context->framesetOk = 'not ok';

            // Initialise node to be the current node (the bottommost node of the stack).
            // Step "Loop".
            foreach ($context->parser->openElements as $node) {
                if ($node instanceof HTMLElement && $node->localName === 'dd') {
                    // Generate implied end tags, except for dd elements.
                    $this->generateImpliedEndTags($context, 'dd');

                    $currentNode = $context->parser->openElements->bottom();

                    // If the current node is not a dd element, then this is a
                    // parse error.
                    if (
                        !($currentNode instanceof HTMLElement && $currentNode->localName === 'dd')
                    ) {
                        // Parse error.
                    }

                    // Pop elements from the stack of open elements until a dd
                    // element has been popped from the stack.
                    while (!$context->parser->openElements->isEmpty()) {
                        $popped = $context->parser->openElements->pop();

                        if ($popped instanceof HTMLElement && $popped->localName === 'dd') {
                            break;
                        }
                    }

                    // Jump to the step labeled done below.
                    break;
                }

                if ($node instanceof HTMLElement && $node->localName === 'dt') {
                    // Generate implied end tags, except for dt elements.
                    $this->generateImpliedEndTags($context, 'dt');

                    $currentNode = $context->parser->openElements->bottom();

                    // If the current node is not a dt element, then this is a
                    // parse error.
                    if (
                        !($currentNode instanceof HTMLElement && $currentNode->localName === 'dt')
                    ) {
                        // Parse error
                    }

                    // Pop elements from the stack of open elements until a dt
                    // element has been popped from the stack.
                    while (!$context->parser->openElements->isEmpty()) {
                        $popped = $context->parser->openElements->pop();

                        if ($popped instanceof HTMLElement && $popped->localName === 'dt') {
                            break;
                        }
                    }

                    // Jump to the step labeled done below.
                    break;
                }

                // If node is in the special category, but is not an address,
                // div, or p element, then jump to the step labeled done below.
                // Otherwise, set node to the previous entry in the stack of
                // open elements and return to the step labeled loop.
                if (
                    $this->isSpecialNode($node)
                    && !($node instanceof HTMLElement
                        && (
                            ($name = $node->localName) === 'address'
                            || $name === 'div'
                            || $name === 'p'
                        )
                    )
                ) {
                    break;
                }
            }

            // Step "Done".
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($context->parser->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                $this->closePElement($context);
            }

            // Finally, insert an HTML element for the token.
            $this->insertForeignElement($context, $token, Namespaces::HTML);

            return;
        }

        if ($token->tagName === 'plaintext') {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($context->parser->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                $this->closePElement($context);
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($context, $token, Namespaces::HTML);

            // Switch the tokenizer to the PLAINTEXT state.
            // NOTE: Once a start tag with the tag name "plaintext" has been
            // seen, that will be the last token ever seen other than character
            // tokens (and the end-of-file token), because there is no way to
            // switch out of the PLAINTEXT state.
            $context->parser->tokenizerState = TokenizerState::PLAINTEXT;

            return;
        }

        if ($token->tagName === 'button') {
            // If the stack of open elements has a button element in scope,
            // then run these substeps:
            if ($context->parser->openElements->hasElementInScope('button', Namespaces::HTML)) {
                // Parse error.
                // Generate implied end tags.
                $this->generateImpliedEndTags($context);

                // Pop elements from the stack of open elements until a button
                // element has been popped from the stack.
                while (!$context->parser->openElements->isEmpty()) {
                    $popped = $context->parser->openElements->pop();

                    if ($popped instanceof HTMLButtonElement) {
                        break;
                    }
                }
            }

            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements($context);

            // Insert an HTML element for the token.
            $this->insertForeignElement($context, $token, Namespaces::HTML);

            // Set the frameset-ok flag to "not ok".
            $context->framesetOk = 'not ok';

            return;
        }

        if ($token->tagName === 'a') {
            // If the list of active formatting elements contains an a element
            // between the end of the list and the last marker on the list (or
            // the start of the list if there is no marker on the list), then
            // this is a parse error; run the adoption agency algorithm for the
            // token, then remove that element from the list of active
            // formatting elements and the stack of open elements if the
            // adoption agency algorithm didn't already remove it (it might not
            // have if the element is not in table scope).
            if (!$context->activeFormattingElements->isEmpty()) {
                $hasAnchorElement = false;
                $element = null;

                foreach ($context->activeFormattingElements as $element) {
                    if ($element instanceof Marker) {
                        break;
                    } elseif ($element instanceof HTMLAnchorElement) {
                        $hasAnchorElement = true;

                        break;
                    }
                }

                if ($hasAnchorElement) {
                    // Parse error.
                    $this->adoptionAgency($context, $token);

                    if ($element !== null && $context->activeFormattingElements->contains($element)) {
                        $context->activeFormattingElements->remove($element);
                        $context->parser->openElements->remove($element);
                    }
                }
            }

            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements($context);

            // Insert an HTML element for the token. Push onto the list of
            // active formatting elements that element.
            $node = $this->insertForeignElement($context, $token, Namespaces::HTML);
            $context->activeFormattingElements->push($node);

            return;
        }

        if (
            $token->tagName === 'b'
            || $token->tagName === 'big'
            || $token->tagName === 'code'
            || $token->tagName === 'em'
            || $token->tagName === 'font'
            || $token->tagName === 'i'
            || $token->tagName === 's'
            || $token->tagName === 'small'
            || $token->tagName === 'strike'
            || $token->tagName === 'strong'
            || $token->tagName === 'tt'
            || $token->tagName === 'u'
        ) {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements($context);

            // Insert an HTML element for the token. Push onto the list of
            // active formatting elements that element.
            $node = $this->insertForeignElement($context, $token, Namespaces::HTML);
            $context->activeFormattingElements->push($node);

            return;
        }

        if ($token->tagName === 'nobr') {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements($context);

            // If the stack of open elements has a nobr element in scope,
            // then this is a parse error; run the adoption agency algorithm for
            // the token, then once again reconstruct the active formatting
            // elements, if any.
            if ($context->parser->openElements->hasElementInScope('nobr', Namespaces::HTML)) {
                // Parse error.
                $this->adoptionAgency($context, $token);
                $this->reconstructActiveFormattingElements($context);
            }

            // Insert an HTML element for the token. Push onto the list of
            // active formatting elements that element.
            $node = $this->insertForeignElement($context, $token, Namespaces::HTML);
            $context->activeFormattingElements->push($node);

            return;
        }

        if ($token->tagName === 'applet' || $token->tagName === 'marquee' || $token->tagName === 'object') {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements($context);

            // Insert an HTML element for the token.
            $this->insertForeignElement($context, $token, Namespaces::HTML);

            // Insert a marker at the end of the list of active formatting
            // elements.
            $context->activeFormattingElements->insertMarker();

            // Set the frameset-ok flag to "not ok".
            $context->framesetOk = 'not ok';

            return;
        }

        if ($token->tagName === 'table') {
            // If the Document is not set to quirks mode, and the stack of
            // open elements has a p element in button scope, then close a p
            // element.
            if (
                $context->document->getMode() !== DocumentMode::QUIRKS
                && $context->parser->openElements->hasElementInButtonScope('p', Namespaces::HTML)
            ) {
                $this->closePElement($context);
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($context, $token, Namespaces::HTML);

            // Set the frameset-ok flag to "not ok".
            $context->framesetOk = 'not ok';

            // Switch the insertion mode to "in table".
            $context->insertionMode = new InTableInsertionMode();

            return;
        }

        if (
            $token->tagName === 'area'
            || $token->tagName === 'br'
            || $token->tagName === 'embed'
            || $token->tagName === 'img'
            || $token->tagName === 'keygen'
            || $token->tagName === 'wbr'
        ) {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements($context);

            // Insert an HTML element for the token. Immediately pop the
            // current node off the stack of open elements.
            $this->insertForeignElement($context, $token, Namespaces::HTML);
            $context->parser->openElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($token->isSelfClosing()) {
                $token->acknowledge();
            }

            // Set the frameset-ok flag to "not ok".
            $context->framesetOk = 'not ok';

            return;
        }

        if ($token->tagName === 'input') {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements($context);

            // Insert an HTML element for the token. Immediately pop the
            // current node off the stack of open elements.
            $this->insertForeignElement($context, $token, Namespaces::HTML);
            $context->parser->openElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($token->isSelfClosing()) {
                $token->acknowledge();
            }

            // If the token does not have an attribute with the name "type", or
            // if it does, but that attribute's value is not an ASCII
            // case-insensitive match for the string "hidden", then: set the
            // frameset-ok flag to "not ok".
            $typeAttribute = null;

            foreach ($token->attributes as $attr) {
                if ($attr->name === 'type') {
                    $typeAttribute = $attr;

                    break;
                }
            }

            if (
                $typeAttribute === null
                || Utils::toASCIILowercase($typeAttribute->value) !== 'hidden'
            ) {
                $context->framesetOk = 'not ok';
            }

            return;
        }

        if ($token->tagName === 'param' || $token->tagName === 'source' || $token->tagName === 'track') {
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

        if ($token->tagName === 'hr') {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($context->parser->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                $this->closePElement($context);
            }

            // Insert an HTML element for the token. Immediately pop the
            // current node off the stack of open elements.
            $this->insertForeignElement($context, $token, Namespaces::HTML);
            $context->parser->openElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($token->isSelfClosing()) {
                $token->acknowledge();
            }

            // Set the frameset-ok flag to "not ok".
            $context->framesetOk = 'not ok';

            return;
        }

        if ($token->tagName === 'image') {
            // Parse error.
            // Change the token's tag name to "img" and reprocess it. (Don't
            // ask.)
            $token->tagName = 'img';
            $context->insertionMode->processToken($context, $token);

            return;
        }

        if ($token->tagName === 'textarea') {
            // Insert an HTML element for the token.
            $this->insertForeignElement($context, $token, Namespaces::HTML);

            // If the next token is a U+000A LINE FEED (LF) character
            // token, then ignore that token and move on to the next one.
            // (Newlines at the start of textarea elements are ignored as an
            // authoring convenience.)
            $this->ignoreNextLineFeed($context);

            // Switch the tokenizer to the RCDATA state.
            $context->parser->tokenizerState = TokenizerState::RCDATA;

            // Let the original insertion mode be the current insertion mode.
            $context->originalInsertionMode = $context->insertionMode;

            // Set the frameset-ok flag to "not ok".
            $context->framesetOk = 'not ok';

            // Switch the insertion mode to "text".
            $context->insertionMode = new TextInsertionMode();

            return;
        }

        if ($token->tagName === 'xmp') {
            // If the stack of open elements has a p element in button scope,
            // then close a p element.
            if ($context->parser->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                $this->closePElement($context);
            }

            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements($context);

            // Set the frameset-ok flag to "not ok
            $context->framesetOk = 'not ok';

            // Follow the generic raw text element parsing algorithm.
            $this->parseGenericTextElement($context, $token, self::RAW_TEXT_ELEMENT_ALGORITHM);

            return;
        }

        if ($token->tagName === 'iframe') {
            // Set the frameset-ok flag to "not ok".
            $context->framesetOk = 'not ok';

            // Follow the generic raw text element parsing algorithm.
            $this->parseGenericTextElement($context, $token, self::RAW_TEXT_ELEMENT_ALGORITHM);

            return;
        }

        if (
            $token->tagName === 'noembed'
            || ($token->tagName === 'noscript' && $context->parser->isScriptingEnabled)
        ) {
            // Follow the generic raw text element parsing algorithm.
            $this->parseGenericTextElement($context, $token, self::RAW_TEXT_ELEMENT_ALGORITHM);

            return;
        }

        if ($token->tagName === 'select') {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements($context);

            // Insert an HTML element for the token.
            $this->insertForeignElement($context, $token, Namespaces::HTML);

            // Set the frameset-ok flag to "not ok".
            $context->framesetOk = 'not ok';

            // If the insertion mode is one of "in table", "in caption",
            // "in table body", "in row", or "in cell", then switch the
            // insertion mode to "in select in table". Otherwise, switch the
            // insertion mode to "in select".
            if (
                $context->insertionMode instanceof InTableInsertionMode
                || $context->insertionMode instanceof InCaptionInsertionMode
                || $context->insertionMode instanceof InTableBodyInsertionMode
                || $context->insertionMode instanceof InRowInsertionMode
                || $context->insertionMode instanceof InCellInsertionMode
            ) {
                $context->insertionMode = new InSelectInTableInsertionMode();

                return;
            }

            $context->insertionMode = new InSelectInsertionMode();

            return;
        }

        if ($token->tagName === 'optgroup' || $token->tagName === 'option') {
            // If the current node is an option element, then pop the current
            // node off the stack of open elements.
            if ($context->parser->openElements->bottom() instanceof HTMLOptionElement) {
                $context->parser->openElements->pop();
            }

            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements($context);

            // Insert an HTML element for the token.
            $this->insertForeignElement($context, $token, Namespaces::HTML);

            return;
        }

        if ($token->tagName === 'rb' || $token->tagName === 'rtc') {
            // If the stack of open elements has a ruby element in scope,
            // then generate implied end tags
            if ($context->parser->openElements->hasElementInScope('ruby', Namespaces::HTML)) {
                $this->generateImpliedEndTags($context);
                $currentNode = $context->parser->openElements->bottom();

                // If the current node is not now a ruby element, this is a
                // parse error.
                if (!($currentNode instanceof HTMLElement && $currentNode->localName === 'ruby')) {
                    // Parse error.
                }
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($context, $token, Namespaces::HTML);

            return;
        }

        if ($token->tagName === 'rp' || $token->tagName === 'rt') {
            // If the stack of open elements has a ruby element in scope,
            // then generate implied end tags, except for rtc elements.
            if ($context->parser->openElements->hasElementInScope('ruby', Namespaces::HTML)) {
                $this->generateImpliedEndTags($context, 'rtc');
                $currentNode = $context->parser->openElements->bottom();

                // If the current node is not now a rtc element or a ruby
                // element, this is a parse error.
                if (!($currentNode instanceof HTMLElement && $currentNode->localName === 'rtc')) {
                    // Parse error.
                }
            }

            // Insert an HTML element for the token.
            $this->insertForeignElement($context, $token, Namespaces::HTML);

            return;
        }

        if ($token->tagName === 'math') {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements($context);

            // Adjust MathML attributes for the token. (This fixes the case of
            // MathML attributes that are not all lowercase.)
            $this->adjustMathMLAttributes($token);

            // Adjust foreign attributes for the token. (This fixes the use of
            // namespaced attributes, in particular XLink.)
            $this->adjustForeignAttributes($token);

            // Insert a foreign element for the token, in the MathML namespace.
            $this->insertForeignElement($context, $token, Namespaces::MATHML);

            // If the token has its self-closing flag set, pop the current node
            // off the stack of open elements and acknowledge the token's
            // self-closing flag.
            if ($token->isSelfClosing()) {
                $context->parser->openElements->pop();
                $token->acknowledge();
            }

            return;
        }

        if ($token->tagName === 'svg') {
            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements($context);

            // Adjust SVG attributes for the token. (This fixes the case of SVG
            // attributes that are not all lowercase.)
            $this->adjustSVGAttributes($token);

            // Adjust foreign attributes for the token. (This fixes the use of
            // namespaced attributes, in particular XLink in SVG.)
            $this->adjustForeignAttributes($token);

            // Insert a foreign element for the token, in the SVG namespace.
            $this->insertForeignElement($context, $token, Namespaces::SVG);

            // If the token has its self-closing flag set, pop the current node
            // off the stack of open elements and acknowledge the token's
            // self-closing flag.
            if ($token->isSelfClosing()) {
                $context->parser->openElements->pop();
                $token->acknowledge();
            }

            return;
        }

        if (
            $token->tagName === 'caption'
            || $token->tagName === 'col'
            || $token->tagName === 'colgroup'
            || $token->tagName === 'frame'
            || $token->tagName === 'head'
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

        // Reconstruct the active formatting elements, if any.
        $this->reconstructActiveFormattingElements($context);

        // Insert an HTML element for the token.
        // NOTE: This element will be an ordinary element.
        $this->insertForeignElement($context, $token, Namespaces::HTML);
    }

    private function processEndTagToken(TreeBuilderContext $context, EndTagToken $token): void
    {
        if ($token->tagName === 'template') {
            // Process the token using the rules for the "in head" insertion mode.
            (new InHeadInsertionMode())->processToken($context, $token);

            return;
        }

        if ($token->tagName === 'body') {
            // If the stack of open elements does not have a body element
            // in scope, this is a parse error; ignore the token.
            if (!$context->parser->openElements->hasElementInScope('body', Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Otherwise, if there is a node in the stack of open elements
            // that is not either a dd element, a dt element, an li element, an
            // optgroup element, an option element, a p element, an rb element,
            // an rp element, an rt element, an rtc element, a tbody element, a
            // td element, a tfoot element, a th element, a thead element, a tr
            // element, the body element, or the html element, then this is a
            // parse error.
            $pattern = '/^(dd|dt|li|optgroup|option|p|rb|rp|rt|';
            $pattern .= 'rtc|tbody|td|tfoot|th|thead|tr|body|html)$/';

            foreach ($context->parser->openElements as $el) {
                if (!($el instanceof HTMLElement && preg_match($pattern, $el->localName))) {
                    // Parse error.
                    break;
                }
            }

            // Switch the insertion mode to "after body".
            $context->insertionMode = new AfterBodyInsertionMode();

            return;
        }

        if ($token->tagName === 'html') {
            // If the stack of open elements does not have a body element in
            // scope, this is a parse error; ignore the token.
            if (!$context->parser->openElements->hasElementInScope('body', Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Otherwise, if there is a node in the stack of open elements
            // that is not either a dd element, a dt element, an li element an
            // optgroup element, an option element, a p element, an rb element,
            // an rp element, an rt element, an rtc element, a tbody element, a
            // td element, a tfoot element, a th element, a thead element, a tr
            // element, the body element, or the html element, then this is a
            // parse error.
            $pattern = '/^(dd|dt|li|optgroup|option|p|rb|rp|rt|';
            $pattern .= 'rtc|tbody|td|tfoot|th|thead|tr|body|html)$/';

            foreach ($context->parser->openElements as $el) {
                if (!($el instanceof HTMLElement && preg_match($pattern, $el->localName))) {
                    // Parse error.
                    break;
                }
            }

            // Switch the insertion mode to "after body".
            $context->insertionMode = new AfterBodyInsertionMode();

            // Reprocess the token.
            $context->insertionMode->processToken($context, $token);

            return;
        }

        if (
            preg_match(
                '/^(address|article|aside|blockquote|button|center|details|'
                . 'dialog|dir|div|dl|fieldset|figcaption|figure|footer|header|'
                . 'hgroup|listing|main|menu|nav|ol|pre|section|summary|ul)$/',
                $token->tagName
            )
        ) {
            // If the stack of open elements does not have an element in
            // scope that is an HTML element with the same tag name as that of
            // the token, then this is a parse error; ignore the token.
            if (!$context->parser->openElements->hasElementInScope($token->tagName, Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags.
            $this->generateImpliedEndTags($context);

            // If the current node is not an HTML element with the same tag
            // name as that of the token, then this is a parse error.
            if (
                !$this->isHTMLElementWithName(
                    $context->parser->openElements->bottom(),
                    $token->tagName
                )
            ) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until an HTML
            // element with the same tag name as the token has been popped
            // from the stack.
            while (!$context->parser->openElements->isEmpty()) {
                if (
                    $this->isHTMLElementWithName(
                        $context->parser->openElements->pop(),
                        $token->tagName
                    )
                ) {
                    break;
                }
            }

            return;
        }

        if ($token->tagName === 'form') {
            if (!$context->parser->openElements->containsTemplateElement()) {
                // Let node be the element that the form element pointer is set
                // to, or null if it is not set to an element.
                $node = $context->parser->formElementPointer;

                // Set the form element pointer to null.
                $context->parser->formElementPointer = null;

                // If node is null or if the stack of open elements does not
                // have node in scope, then this is a parse error; abort these
                // steps and ignore the token.
                if ($node === null || !$context->parser->openElements->contains($node)) {
                    // Parse error.
                    // Ignore the token.
                    return;
                }

                // Generate implied end tags.
                $this->generateImpliedEndTags($context);

                // If the current node is not node, then this is a parse error.
                if ($context->parser->openElements->bottom() !== $node) {
                    // Parse error.
                }

                // Remove node from the stack of open elements.
                $context->parser->openElements->remove($node);

                return;
            }

            // If the stack of open elements does not have a form element
            // in scope, then this is a parse error; abort these steps and
            // ignore the token.
            if (!$context->parser->openElements->hasElementInScope('form', Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags.
            $this->generateImpliedEndTags($context);

            // If the current node is not a form element, then this is a parse
            // error.
            if (!$context->parser->openElements->bottom() instanceof HTMLFormElement) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until a form
            // element has been popped from the stack.
            while (!$context->parser->openElements->isEmpty()) {
                if ($context->parser->openElements->pop() instanceof HTMLFormElement) {
                    break;
                }
            }

            return;
        }

        if ($token->tagName === 'p') {
            // If the stack of open elements does not have a p element in
            // button scope, then this is a parse error; insert an HTML element
            // for a "p" start tag token with no attributes.
            if (!$context->parser->openElements->hasElementInButtonScope('p', Namespaces::HTML)) {
                // Parse error.
                $this->insertForeignElement($context, new StartTagToken('p'), Namespaces::HTML);
            }

            // Close a p element.
            $this->closePElement($context);

            return;
        }

        if ($token->tagName === 'li') {
            // If the stack of open elements does not have an li element
            // in list item scope, then this is a parse error; ignore the token.
            if (!$context->parser->openElements->hasElementInListItemScope('li', Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags, except for li elements.
            $this->generateImpliedEndTags($context, 'li');

            // If the current node is not an li element, then this is a parse
            // error.
            if (!$context->parser->openElements->bottom() instanceof HTMLLIElement) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until an li element
            // has been popped from the stack.
            while (!$context->parser->openElements->isEmpty()) {
                if ($context->parser->openElements->pop() instanceof HTMLLIElement) {
                    break;
                }
            }

            return;
        }

        if ($token->tagName === 'dd' || $token->tagName === 'dt') {
            // If the stack of open elements does not have an element in
            // scope that is an HTML element with the same tag name as that of
            // the token, then this is a parse error; ignore the token.
            if (!$context->parser->openElements->hasElementInScope($token->tagName, Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags, except for HTML elements with the
            // same tag name as the token.
            $this->generateImpliedEndTags($context, $token->tagName);

            // If the current node is not an HTML element with the same tag
            // name as that of the token, then this is a parse error.
            if (
                !$this->isHTMLElementWithName(
                    $context->parser->openElements->bottom(),
                    $token->tagName
                )
            ) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until an HTML
            // element with the same tag name as the token has been popped from
            // the stack.
            while (!$context->parser->openElements->isEmpty()) {
                if (
                    $this->isHTMLElementWithName(
                        $context->parser->openElements->pop(),
                        $token->tagName
                    )
                ) {
                    break;
                }
            }

            return;
        }

        if (
            $token->tagName === 'h1'
            || $token->tagName === 'h2'
            || $token->tagName === 'h3'
            || $token->tagName === 'h4'
            || $token->tagName === 'h5'
            || $token->tagName === 'h6'
        ) {
            // If the stack of open elements does not have an element in
            // scope that is an HTML element and whose tag name is one of "h1",
            // "h2", "h3", "h4", "h5", or "h6", then this is a parse error;
            // ignore the token.
            if (
                !$context->parser->openElements->hasElementInScope('h1', Namespaces::HTML)
                && !$context->parser->openElements->hasElementInScope('h2', Namespaces::HTML)
                && !$context->parser->openElements->hasElementInScope('h3', Namespaces::HTML)
                && !$context->parser->openElements->hasElementInScope('h4', Namespaces::HTML)
                && !$context->parser->openElements->hasElementInScope('h5', Namespaces::HTML)
                && !$context->parser->openElements->hasElementInScope('h6', Namespaces::HTML)
            ) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags.
            $this->generateImpliedEndTags($context);

            // If the current node is not an HTML element with the same tag
            // name as that of the token, then this is a parse error.
            if (
                !$this->isHTMLElementWithName(
                    $context->parser->openElements->bottom(),
                    $token->tagName
                )
            ) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until an HTML
            // element whose tag name is one of "h1", "h2", "h3", "h4", "h5",
            // or "h6" has been popped from the stack.
            while (!$context->parser->openElements->isEmpty()) {
                if ($context->parser->openElements->pop() instanceof HTMLHeadingElement) {
                    break;
                }
            }

            return;
        }

        if (
            $token->tagName === 'a'
            || $token->tagName === 'b'
            || $token->tagName === 'big'
            || $token->tagName === 'code'
            || $token->tagName === 'em'
            || $token->tagName === 'font'
            || $token->tagName === 'i'
            || $token->tagName === 'nobr'
            || $token->tagName === 's'
            || $token->tagName === 'small'
            || $token->tagName === 'strike'
            || $token->tagName === 'strong'
            || $token->tagName === 'tt'
            || $token->tagName === 'u'
        ) {
            // Run the adoption agency algorithm for the token.
            $this->adoptionAgency($context, $token);

            return;
        }

        if ($token->tagName === 'applet' || $token->tagName === 'marquee' || $token->tagName === 'object') {
            // If the stack of open elements does not have an element in scope
            // that is an HTML element with the same tag name as that of the
            // token, then this is a parse error; ignore the token.
            if (!$context->parser->openElements->hasElementInScope($token->tagName, Namespaces::HTML)) {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Generate implied end tags.
            $this->generateImpliedEndTags($context);

            // If the current node is not an HTML element with the same tag
            // name as that of the token, then this is a parse error.
            if (
                !$this->isHTMLElementWithName(
                    $context->parser->openElements->bottom(),
                    $token->tagName
                )
            ) {
                // Parse error.
            }

            // Pop elements from the stack of open elements until an HTML
            // element with the same tag name as the token has been popped from
            // the stack.
            while (!$context->parser->openElements->isEmpty()) {
                if (
                    $this->isHTMLElementWithName(
                        $context->parser->openElements->pop(),
                        $token->tagName
                    )
                ) {
                    break;
                }
            }

            // Clear the list of active formatting elements up to the last
            // marker.
            $context->activeFormattingElements->clearUpToLastMarker();

            return;
        }

        if ($token->tagName === 'br') {
            // Parse error.
            // Drop the attributes from the token, and act as described in the
            // next entry; i.e. act as if this was a "br" start tag token with
            // no attributes, rather than the end tag token that it actually is.
            $token->clearAttributes();

            // Reconstruct the active formatting elements, if any.
            $this->reconstructActiveFormattingElements($context);

            // Insert an HTML element for the token. Immediately pop the
            // current node off the stack of open elements.
            $this->insertForeignElement($context, $token, Namespaces::HTML);
            $context->parser->openElements->pop();

            // Acknowledge the token's self-closing flag, if it is set.
            if ($token->isSelfClosing()) {
                $token->acknowledge();
            }

            // Set the frameset-ok flag to "not ok".
            $context->framesetOk = 'not ok';
        }

        $this->applyAnyOtherEndTagForInBodyInsertionMode($context, $token);
    }

    private function applyAnyOtherEndTagForInBodyInsertionMode(TreeBuilderContext $context, EndTagToken $token): void
    {
        // Initialise node to be the current node (the bottommost node of the
        // stack).
        $tagName = $token->tagName;

        foreach ($context->parser->openElements as $node) {
            if ($this->isHTMLElementWithName($node, $tagName)) {
                // Generate implied end tags, except for HTML elements with
                // the same tag name as the token.
                $this->generateImpliedEndTags($context, $tagName);

                // If node is not the current node, then this is a parse error.
                if ($node !== $context->parser->openElements->bottom()) {
                    // Parse error.
                }

                // Pop all the nodes from the current node up to node, including
                // node, then stop these steps.
                while (!$context->parser->openElements->isEmpty()) {
                    if ($context->parser->openElements->pop() === $node) {
                        break 2;
                    }
                }
            } elseif ($this->isSpecialNode($node)) {
                // Parse error.
                // Ignore the token.
                break;
            }
        }
    }

    /**
     * Closes a paragraph <p> element.
     *
     * @see https://html.spec.whatwg.org/multipage/#close-a-p-element
     */
    private function closePElement(TreeBuilderContext $context): void
    {
        $this->generateImpliedEndTags($context, 'p');

        if (!$context->parser->openElements->bottom() instanceof HTMLParagraphElement) {
            // Parse error
        }

        while (!$context->parser->openElements->isEmpty()) {
            if ($context->parser->openElements->pop() instanceof HTMLParagraphElement) {
                break;
            }
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#reconstruct-the-active-formatting-elements
     */
    private function reconstructActiveFormattingElements(TreeBuilderContext $context): void
    {
        // If there are no entries in the list of active formatting elements,
        // then there is nothing to reconstruct; stop this algorithm.
        if ($context->activeFormattingElements->isEmpty()) {
            return;
        }

        // If the last (most recently added) entry in the list of active
        // formatting elements is a marker, or if it is an element that is in
        // the stack of open elements, then there is nothing to reconstruct;
        // stop this algorithm.
        $entry = $context->activeFormattingElements->top();

        if ($entry instanceof Marker || $context->parser->openElements->contains($entry)) {
            return;
        }

        $cursor = $context->activeFormattingElements->count() - 1;

        // If there are no entries before entry in the list of active formatting
        // elements, then jump to the step labeled create.
        Rewind:

        if ($cursor === 0) {
            goto Create;
        }

        // Let entry be the entry one earlier than entry in the list of active
        // formatting elements.
        $entry = $context->activeFormattingElements->itemAt(--$cursor);

        // If entry is neither a marker nor an element that is also in the stack
        // of open elements, go to the step labeled rewind.
        if (!$entry instanceof Marker && !$context->parser->openElements->contains($entry)) {
            goto Rewind;
        }

        Advance:
        // Let entry be the element one later than entry in the list of active
        // formatting elements.
        $entry = $context->activeFormattingElements->itemAt(++$cursor);

        Create:
        // Insert an HTML element for the token for which the element entry was
        // created, to obtain new element.
        $newElement = $this->insertForeignElement($context, $context->elementTokenMap[$entry], Namespaces::HTML);

        // Replace the entry for entry in the list with an entry for new
        // element.
        $context->activeFormattingElements->replace($newElement, $entry);

        // If the entry for new element in the list of active formatting
        // elements is not the last entry in the list, return to the step
        // labeled advance.
        if ($newElement !== $context->activeFormattingElements->top()) {
            goto Advance;
        }
    }

    /**
     * Ignore the next line feed character in the input stream, if any.
     *
     * This is a bit hacky doing it with the input stream instead of looking at the next token, but
     * it seems to work.
     */
    private function ignoreNextLineFeed(TreeBuilderContext $context): void
    {
        $peeked = $context->parser->input->peek();

        if ($peeked === "\n") {
            $context->parser->input->get();
        } elseif ($peeked === '&') {
            // need to account for numeric character reference as well
            $peeked = $context->parser->input->peek(6);

            if ($peeked === '&#x0a;' || $peeked === '&#x0A;') {
                $context->parser->input->get(6);
            }
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#adoption-agency-algorithm
     */
    private function adoptionAgency(TreeBuilderContext $context, TagToken $token): void
    {
        $subject = $token->tagName;
        $currentNode = $context->parser->openElements->bottom();

        // If the current node is an HTML Element with a tag name that matches
        // subject and the current node is not in the list of active formatting
        // elements, then remove the current node from the stack of open
        // elements and abort these steps.
        if (
            $this->isHTMLElementWithName($currentNode, $subject)
            && !$context->activeFormattingElements->contains($currentNode)
        ) {
            $context->parser->openElements->pop();

            return;
        }

        // Let outer loop counter be zero.
        $outerLoopCounter = 0;

        // Outer loop
        do {
            // If outer loop counter is greater than or equal to eight, then
            // abort these steps.
            if ($outerLoopCounter >= 8) {
                return;
            }

            // Increment outer loop counter by one.
            $outerLoopCounter++;

            // Let formatting element be the last element in the list of active
            // formatting elements that is between the end of the list and the
            // last marker in the list, if any, or the start of the list
            // otherwise, and has the tag name subject.
            $formattingElement = null;

            foreach ($context->activeFormattingElements as $e) {
                // TODO: Spec says use tag name, but it is broken unless I use
                // local name.
                if ($e instanceof Element && $e->localName === $subject) {
                    $formattingElement = $e;

                    break;
                } elseif ($e instanceof Marker) {
                    break;
                }
            }

            // If there is no such element, then abort these steps and instead
            // act as described in the "any other end tag" entry above.
            if (!$formattingElement) {
                $this->applyAnyOtherEndTagForInBodyInsertionMode($context, $token);

                return;
            }

            // If formatting element is not in the stack of open elements, then
            // this is a parse error; remove the element from the list, and
            // abort these steps.
            if (!$context->parser->openElements->contains($formattingElement)) {
                // Parse error.
                $context->activeFormattingElements->remove($formattingElement);

                return;
            }

            // If formatting element is in the stack of open elements, but
            // the element is not in scope, then this is a parse error; abort
            // these steps.
            if (
                $context->parser->openElements->contains($formattingElement)
                && !$context->parser->openElements->hasElementInScope(
                    $formattingElement->localName,
                    $formattingElement->namespaceURI
                )
            ) {
                // Parse error.
                return;
            }

            // If formatting element is not the current node, this is a parse
            // error. (But do not abort these steps.)
            if ($context->parser->openElements->bottom() !== $formattingElement) {
                // Parse error.
            }

            // Let furthest block be the topmost node in the stack of open
            // elements that is lower in the stack than formatting element, and
            // is an element in the special category. There might not be one.
            $furthestBlock = null;
            $formattingElementIndex = $context->parser->openElements->indexOf($formattingElement);
            $count = $context->parser->openElements->count();

            for ($i = $formattingElementIndex + 1; $i < $count; $i++) {
                $current = $context->parser->openElements->itemAt($i);

                if ($this->isSpecialNode($current)) {
                    $furthestBlock = $current;

                    break;
                }
            }

            // If there is no furthest block, then the UA must first pop all the
            // nodes from the bottom of the stack of open elements, from the
            // current node up to and including formatting element, then remove
            // formatting element from the list of active formatting elements,
            // and finally abort these steps.
            if (!$furthestBlock) {
                while (!$context->parser->openElements->isEmpty()) {
                    if ($context->parser->openElements->pop() === $formattingElement) {
                        break;
                    }
                }

                $context->activeFormattingElements->remove($formattingElement);

                return;
            }

            // Let common ancestor be the element immediately above formatting
            // element in the stack of open elements.
            $commonAncestor = $context->parser->openElements->itemAt($formattingElementIndex - 1);

            // Let a bookmark note the position of formatting element in the
            // list of active formatting elements relative to the elements on
            // either side of it in the list.
            $bookmark = $context->activeFormattingElements->indexOf($formattingElement);

            // Let node and last node be furthest block.
            $node = $furthestBlock;
            $lastNode = $furthestBlock;

            // Let inner loop counter be zero.
            $innerLoopCounter = 0;
            $clonedStack = clone $context->parser->openElements;

            // Inner loop
            do {
                // Increment inner loop counter by one.
                $innerLoopCounter++;

                // Let node be the element immediately above node in the stack
                // of open elements, or if node is no longer in the stack of
                // open elements (e.g. because it got removed by this
                // algorithm), the element that was immediately above node in
                // the stack of open elements before node was removed.
                $targetStack = !$context->parser->openElements->contains($node)
                    ? $clonedStack
                    : $context->parser->openElements;
                $node = $targetStack->itemAt($targetStack->indexOf($node) - 1);

                // If node is formatting element, then go to the next step in
                // the overall algorithm.
                if ($node === $formattingElement) {
                    break;
                }

                // If inner loop counter is greater than three and node is in
                // the list of active formatting elements, then remove node from
                // the list of active formatting elements.
                $nodeInList = $context->activeFormattingElements->contains($node);

                if ($innerLoopCounter > 3 && $nodeInList) {
                    $context->activeFormattingElements->remove($node);
                    $nodeInList = false;
                }

                // If node is not in the list of active formatting elements,
                // then remove node from the stack of open elements and then go
                // back to the step labeled inner loop.
                if (!$nodeInList) {
                    $context->parser->openElements->remove($node);

                    continue;
                }

                // Create an element for the token for which the element node
                // was created, in the HTML namespace, with common ancestor as
                // the intended parent; replace the entry for node in the list
                // of active formatting elements with an entry for the new
                // element, replace the entry for node in the stack of open
                // elements with an entry for the new element, and let node be
                // the new element.
                $newElement = $this->createElementForToken(
                    $context->elementTokenMap[$node],
                    Namespaces::HTML,
                    $commonAncestor
                );
                $context->elementTokenMap->attach($newElement, $context->elementTokenMap[$node]);

                $context->activeFormattingElements->replace($newElement, $node);
                $context->parser->openElements->replace($newElement, $node);
                $node = $newElement;

                // If last node is furthest block, then move the aforementioned
                // bookmark to be immediately after the new node in the list of
                // active formatting elements.
                if ($lastNode === $furthestBlock) {
                    $bookmark = $context->activeFormattingElements->indexOf($newElement) + 1;
                }

                // Insert last node into node, first removing it from its
                // previous parent node if any.
                $node->appendChild($lastNode);

                // Let last node be node.
                $lastNode = $node;
            } while (true);

            // Insert whatever last node ended up being in the previous step at
            // the appropriate place for inserting a node, but using common
            // ancestor as the override target.
            $this->insertNode(
                $lastNode,
                $this->getAppropriatePlaceForInsertingNode($context, $commonAncestor)
            );

            // Create an element for the token for which formatting element was
            // created, in the HTML namespace, with furthest block as the
            // intended parent.
            $element = $this->createElementForToken(
                $context->elementTokenMap[$formattingElement],
                Namespaces::HTML,
                $furthestBlock
            );
            $context->elementTokenMap->attach($element, $context->elementTokenMap[$formattingElement]);

            // Take all of the child nodes of furthest block and append them to
            // the element created in the last step.
            foreach ($furthestBlock->childNodes as $child) {
                $element->appendChild($child);
            }

            // Append that new element to furthest block.
            $furthestBlock->appendChild($element);

            // Remove formatting element from the list of active formatting
            // elements, and insert the new element into the list of active
            // formatting elements at the position of the aforementioned
            // bookmark.
            $context->activeFormattingElements->remove($formattingElement);
            $context->activeFormattingElements->insertAt($bookmark, $element);

            // Remove formatting element from the stack of open elements, and
            // insert the new element into the stack of open elements
            // immediately below the position of furthest block in that stack.
            $context->parser->openElements->remove($formattingElement);
            $context->parser->openElements->insertAfter($element, $furthestBlock);
        } while (true);
    }

    /**
     * Returns whether or not the element has special parsing rules.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#special
     */
    private function isSpecialNode(Node $node): bool
    {
        if (!$node instanceof Element) {
            return false;
        }

        $namespace = $node->namespaceURI;

        if ($namespace === Namespaces::HTML) {
            $localName = $node->localName;

            return $localName === 'address'
                || $localName === 'applet'
                || $localName === 'area'
                || $localName === 'article'
                || $localName === 'aside'
                || $localName === 'base'
                || $localName === 'basefont'
                || $localName === 'bgsound'
                || $localName === 'blockquote'
                || $localName === 'body'
                || $localName === 'br'
                || $localName === 'button'
                || $localName === 'caption'
                || $localName === 'center'
                || $localName === 'col'
                || $localName === 'colgroup'
                || $localName === 'dd'
                || $localName === 'details'
                || $localName === 'dir'
                || $localName === 'div'
                || $localName === 'dl'
                || $localName === 'dt'
                || $localName === 'embed'
                || $localName === 'fieldset'
                || $localName === 'figcaption'
                || $localName === 'figure'
                || $localName === 'footer'
                || $localName === 'form'
                || $localName === 'frame'
                || $localName === 'frameset'
                || $localName === 'h1'
                || $localName === 'h2'
                || $localName === 'h3'
                || $localName === 'h4'
                || $localName === 'h5'
                || $localName === 'h6'
                || $localName === 'head'
                || $localName === 'header'
                || $localName === 'hgroup'
                || $localName === 'hr'
                || $localName === 'html'
                || $localName === 'iframe'
                || $localName === 'img'
                || $localName === 'input'
                || $localName === 'keygen'
                || $localName === 'li'
                || $localName === 'link'
                || $localName === 'listing'
                || $localName === 'main'
                || $localName === 'marquee'
                || $localName === 'menu'
                || $localName === 'meta'
                || $localName === 'nav'
                || $localName === 'noembed'
                || $localName === 'noframes'
                || $localName === 'noscript'
                || $localName === 'object'
                || $localName === 'ol'
                || $localName === 'p'
                || $localName === 'param'
                || $localName === 'plaintext'
                || $localName === 'pre'
                || $localName === 'script'
                || $localName === 'section'
                || $localName === 'select'
                || $localName === 'source'
                || $localName === 'style'
                || $localName === 'summary'
                || $localName === 'table'
                || $localName === 'tbody'
                || $localName === 'td'
                || $localName === 'template'
                || $localName === 'textarea'
                || $localName === 'tfoot'
                || $localName === 'th'
                || $localName === 'thead'
                || $localName === 'title'
                || $localName === 'tr'
                || $localName === 'track'
                || $localName === 'ul'
                || $localName === 'wbr'
                || $localName === 'xmp';
        } elseif ($namespace === Namespaces::MATHML) {
            $localName = $node->localName;

            return $localName === 'mi'
                || $localName === 'mo'
                || $localName === 'mn'
                || $localName === 'ms'
                || $localName === 'mtext'
                || $localName === 'annotation-xml';
        } elseif (
            $node instanceof SVGForeignObjectElement
            || $node instanceof SVGDescElement
            || $node instanceof SVGTitleElement
        ) {
            return true;
        }

        return false;
    }
}
