<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\DocumentMode;
use Rowbot\DOM\DocumentType;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\DoctypeToken;
use Rowbot\DOM\Parser\Token\Token;
use Rowbot\DOM\Utils;

use function mb_strpos;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#the-initial-insertion-mode
 */
class InitialInsertionMode extends InsertionMode
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
            // Ignore the token.

            return;
        }

        if ($token instanceof CommentToken) {
            $this->context->insertComment($token, [$this->context->document, 'beforeend']);

            return;
        }

        if ($token instanceof DoctypeToken) {
            $this->processDoctypeToken($token);

            return;
        }

        // If the document is not an iframe srcdoc document, then this
        // is a parse error; set the Document to quirks mode.
        if (!$this->context->document->isIframeSrcdoc()) {
            // Parse error.
            $this->context->document->setMode(DocumentMode::QUIRKS);
        }

        // In any case, switch the insertion mode to "before html", then
        // reprocess the token.
        $this->context->insertionMode = new BeforeHTMLInsertionMode($this->context);
        $this->context->insertionMode->processToken($token);
    }

    private function processDoctypeToken(DoctypeToken $token): void
    {
        $publicId = $token->publicIdentifier;
        $systemId = $token->systemIdentifier;
        $name = $token->name;

        if (
            $name !== 'html'
            || $publicId !== null
            || ($systemId !== null && $systemId !== 'about:legacy-compat')
        ) {
            // Parse error
        }

        // Append a DocumentType node to the Document node, with the name
        // attribute set to the name given in the DOCTYPE token, or the
        // empty string if the name was missing; the publicId attribute set
        // to the public identifier given in the DOCTYPE token, or the empty
        // string if the public identifier was missing; the systemId
        // attribute set to the system identifier given in the DOCTYPE
        // token, or the empty string if the system identifier was missing;
        // and the other attributes specific to DocumentType objects set to
        // null and empty lists as appropriate. Associate the DocumentType
        // node with the Document object so that it is returned as the value
        // of the doctype attribute of the Document object.
        $doctype = new DocumentType($this->context->document, $name ?? '', $publicId ?? '', $systemId ?? '');
        $this->context->document->appendChild($doctype);

        // If the document is not an iframe srcdoc document...
        if (!$this->context->document->isIframeSrcdoc()) {
            // and the DOCTYPE token matches one of the conditions in the
            // following list, the set the Document to quirks mode.
            if (
                $token->getQuirksMode() === 'on'
                || $name !== 'html'
                || $publicId === Utils::toASCIILowercase('-//W3O//DTD W3 HTML Strict 3.0//EN//')
                || $publicId === Utils::toASCIILowercase('-/W3C/DTD HTML 4.0 Transitional/EN')
                || $publicId === Utils::toASCIILowercase('HTML')
                || $systemId === Utils::toASCIILowercase(
                    'http://www.ibm.com/data/dtd/v11/ibmxhtml1-transitional.dtd'
                )
                || $this->identifierBeginsWith($publicId, [
                    '+//Silmaril//dtd html Pro v0r11 19970101//',
                    '-//AS//DTD HTML 3.0 asWedit + extensions//',
                    '-//AdvaSoft Ltd//DTD HTML 3.0 asWedit + extensions//',
                    '-//IETF//DTD HTML 2.0 Level 1//',
                    '-//IETF//DTD HTML 2.0 Level 2//',
                    '-//IETF//DTD HTML 2.0 Strict Level 1//',
                    '-//IETF//DTD HTML 2.0 Strict Level 2//',
                    '-//IETF//DTD HTML 2.0 Strict//',
                    '-//IETF//DTD HTML 2.0//',
                    '-//IETF//DTD HTML 2.1E//',
                    '-//IETF//DTD HTML 3.0//',
                    '-//IETF//DTD HTML 3.2 Final//',
                    '-//IETF//DTD HTML 3.2//',
                    '-//IETF//DTD HTML 3//',
                    '-//IETF//DTD HTML Level 0//',
                    '-//IETF//DTD HTML Level 1//',
                    '-//IETF//DTD HTML Level 2//',
                    '-//IETF//DTD HTML Level 3//',
                    '-//IETF//DTD HTML Strict Level 0//',
                    '-//IETF//DTD HTML Strict Level 1//',
                    '-//IETF//DTD HTML Strict Level 2//',
                    '-//IETF//DTD HTML Strict Level 3//',
                    '-//IETF//DTD HTML Strict//',
                    '-//IETF//DTD HTML//',
                    '-//Metrius//DTD Metrius Presentational//',
                    '-//Microsoft//DTD Internet Explorer 2.0 HTML Strict//',
                    '-//Microsoft//DTD Internet Explorer 2.0 HTML//',
                    '-//Microsoft//DTD Internet Explorer 2.0 Tables//',
                    '-//Microsoft//DTD Internet Explorer 3.0 HTML Strict//',
                    '-//Microsoft//DTD Internet Explorer 3.0 HTML//',
                    '-//Microsoft//DTD Internet Explorer 3.0 Tables//',
                    '-//Netscape Comm. Corp.//DTD HTML//',
                    '-//Netscape Comm. Corp.//DTD Strict HTML//',
                    '-//O\'Reilly and Associates//DTD HTML 2.0//',
                    '-//O\'Reilly and Associates//DTD HTML Extended 1.0//',
                    '-//O\'Reilly and Associates//DTD HTML Extended Relaxed 1.0//',
                    '-//SQ//DTD HTML 2.0 HoTMetaL + extensions//',
                    '-//SoftQuad Software//DTD HoTMetaL PRO 6.0::19990601::extensions to HTML 4.0//',
                    '-//SoftQuad//DTD HoTMetaL PRO 4.0::19971010::extensions to HTML 4.0//',
                    '-//Spyglass//DTD HTML 2.0 Extended//',
                    '-//Sun Microsystems Corp.//DTD HotJava HTML//',
                    '-//Sun Microsystems Corp.//DTD HotJava Strict HTML//',
                    '-//W3C//DTD HTML 3 1995-03-24//',
                    '-//W3C//DTD HTML 3.2 Draft//',
                    '-//W3C//DTD HTML 3.2 Final//',
                    '-//W3C//DTD HTML 3.2//',
                    '-//W3C//DTD HTML 3.2S Draft//',
                    '-//W3C//DTD HTML 4.0 Frameset//',
                    '-//W3C//DTD HTML 4.0 Transitional//',
                    '-//W3C//DTD HTML Experimental 19960712//',
                    '-//W3C//DTD HTML Experimental 970421//',
                    '-//W3C//DTD W3 HTML//',
                    '-//W3O//DTD W3 HTML 3.0//',
                    '-//WebTechs//DTD Mozilla HTML 2.0//',
                    '-//WebTechs//DTD Mozilla HTML//',
                ])
                || ($systemId === null && $this->identifierBeginsWith($publicId, [
                    '-//W3C//DTD HTML 4.01 Frameset//',
                    '-//W3C//DTD HTML 4.01 Transitional//',
                ]))
            ) {
                $this->context->document->setMode(DocumentMode::QUIRKS);

                // Otherwise, if the DOCTYPE token matches one of the
                // conditions in the following list, then set the Document
                // to limited-quirks mode.
            } elseif (
                $this->identifierBeginsWith($publicId, [
                    '-//W3C//DTD XHTML 1.0 Frameset//',
                    '-//W3C//DTD XHTML 1.0 Transitional//',
                ])
                || ($systemId !== null && $this->identifierBeginsWith($publicId, [
                    '-//W3C//DTD HTML 4.01 Frameset//',
                    '-//W3C//DTD HTML 4.01 Transitional//',
                ]))
            ) {
                $this->context->document->setMode(DocumentMode::LIMITED_QUIRKS);
            }
        }

        // The, switch the insertion mode to "before html".
        $this->context->insertionMode = new BeforeHTMLInsertionMode($this->context);
    }

    /**
     * Performs an ASCII case-insensitive compare of a DOCTYPE token's public
     * or system identifier to see if the identifier begins with one of the
     * fragment strings.
     *
     * @param non-empty-list<string> $fragments
     */
    private function identifierBeginsWith(?string $identifier, array $fragments): bool
    {
        // If the given identifier is null, this means that the DOCTYPE token's
        // identifier was missing and it cannot possibly match anything in the
        // list.
        if ($identifier === null) {
            return false;
        }

        // Make the identifier ASCII lowercased for comparison.
        $identifier = Utils::toASCIILowercase($identifier);

        foreach ($fragments as $identifierFragment) {
            if (
                mb_strpos(
                    $identifier,
                    Utils::toASCIILowercase($identifierFragment),
                    0,
                    'utf-8'
                ) === 0
            ) {
                return true;
            }
        }

        return false;
    }
}
