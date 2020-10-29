<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

use Generator;
use IntlChar;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\Collection\OpenElementStack;
use Rowbot\DOM\Parser\Token\AttributeToken;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\CommentToken;
use Rowbot\DOM\Parser\Token\DoctypeToken;
use Rowbot\DOM\Parser\Token\EndTagToken;
use Rowbot\DOM\Parser\Token\EOFToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Support\CodePointStream;
use Rowbot\DOM\Utils;

use function array_pop;
use function ctype_alnum;
use function ctype_alpha;
use function ctype_digit;
use function ctype_lower;
use function ctype_upper;
use function ctype_xdigit;
use function file_get_contents;
use function json_decode;
use function ord;
use function preg_match;
use function strtolower;

/**
 * @see https://html.spec.whatwg.org/multipage/parsing.html#tokenization
 */
class Tokenizer
{
    use TokenizerOrTreeBuilder;

    /**
     * @see https://html.spec.whatwg.org/multipage/parsing.html#table-charref-overrides
     */
    private const CHARACTER_REFERENCE_MAP = [
        0x80 => 0x20AC, // EURO SIGN (€)
        0x82 => 0x201A, // SINGLE LOW-9 QUOTATION MARK (‚)
        0x83 => 0x0192, // LATIN SMALL LETTER F WITH HOOK (ƒ)
        0x84 => 0x201E, // DOUBLE LOW-9 QUOTATION MARK („)
        0x85 => 0x2026, // HORIZONTAL ELLIPSIS (…)
        0x86 => 0x2020, // DAGGER (†)
        0x87 => 0x2021, // DOUBLE DAGGER (‡)
        0x88 => 0x02C6, // MODIFIER LETTER CIRCUMFLEX ACCENT (ˆ)
        0x89 => 0x2030, // PER MILLE SIGN (‰)
        0x8A => 0x0160, // LATIN CAPITAL LETTER S WITH CARON (Š)
        0x8B => 0x2039, // SINGLE LEFT-POINTING ANGLE QUOTATION MARK (‹)
        0x8C => 0x0152, // LATIN CAPITAL LIGATURE OE (Œ)
        0x8E => 0x017D, // LATIN CAPITAL LETTER Z WITH CARON (Ž)
        0x91 => 0x2018, // LEFT SINGLE QUOTATION MARK (‘)
        0x92 => 0x2019, // RIGHT SINGLE QUOTATION MARK (’)
        0x93 => 0x201C, // LEFT DOUBLE QUOTATION MARK (“)
        0x94 => 0x201D, // RIGHT DOUBLE QUOTATION MARK (”)
        0x95 => 0x2022, // BULLET (•)
        0x96 => 0x2013, // EN DASH (–)
        0x97 => 0x2014, // EM DASH (—)
        0x98 => 0x02DC, // SMALL TILDE (˜)
        0x99 => 0x2122, // TRADE MARK SIGN (™)
        0x9A => 0x0161, // LATIN SMALL LETTER S WITH CARON (š)
        0x9B => 0x203A, // SINGLE RIGHT-POINTING ANGLE QUOTATION MARK (›)
        0x9C => 0x0153, // LATIN SMALL LIGATURE OE (œ)
        0x9E => 0x017E, // LATIN SMALL LETTER Z WITH CARON (ž)
        0x9F => 0x0178,  // LATIN CAPITAL LETTER Y WITH DIAERESIS (Ÿ)
    ];

    /**
     * Currrently, the longest named character reference, from the named
     * character references table, consists of 33 characters, however, because
     * the ampersand (&) is already in the temporary buffer, subtract 1.
     */
    private const MAX_NAMED_CHAR_REFERENCE_LENGTH = 32;

    /**
     * The path to the entities.json file.
     *
     * @see https://html.spec.whatwg.org/multipage/named-characters.html#named-character-references
     * @see https://html.spec.whatwg.org/entities.json
     */
    private const NAMED_CHAR_REFERENCES_PATH = __DIR__
        . DIRECTORY_SEPARATOR
        . '..'
        . DIRECTORY_SEPARATOR
        . 'entities.json';

    /**
     * The input stream.
     *
     * @var \Rowbot\DOM\Support\CodePointStream
     */
    private $input;

    /**
     * The last StartTagToken that was emitted by the tokenizer.
     *
     * @var \Rowbot\DOM\Parser\Token\StartTagToken|null
     */
    private $lastEmittedStartTagToken;

    /**
     * The contents of the entities.json file decoded as JSON. The contents are
     * lazily loaded.
     *
     * @var string
     */
    private static $namedCharacterReferences;

    public function __construct(
        CodePointStream $input,
        OpenElementStack $openElements,
        bool $isFragmentCase,
        ?Element $contextElement,
        ParserState $state
    ) {
        $this->contextElement = $contextElement;
        $this->input = $input;
        $this->isFragmentCase = $isFragmentCase;
        $this->openElements = $openElements;
        $this->state = $state;
    }

    /**
     * Runs the tokenization steps.
     */
    public function run(): Generator
    {
        $returnState = null;
        $characterReferenceCode = 0;
        $attributeToken = null;
        $commentToken = null;
        $doctypeToken = null;
        $tagToken = null;
        $buffer = '';

        do {
            // Before each step of the tokenizer, the user agent must first
            // check the parser pause flag. If it is true, then the tokenizer
            // must abort the processing of any nested invocations of the
            // tokenizer, yielding control back to the caller.
            if ($this->state->isPaused) {
                return;
            }

            switch ($this->state->tokenizerState) {
                // https://html.spec.whatwg.org/multipage/syntax.html#data-state
                case TokenizerState::DATA:
                    $c = $this->input->get();

                    if ($c === '&') {
                        // Set the return state to the data state. Switch to the
                        // character reference state.
                        $returnState = TokenizerState::DATA;
                        $this->state->tokenizerState = TokenizerState::CHARACTER_REFERENCE;
                    } elseif ($c === '<') {
                        // Switch to the tag open state.
                        $this->state->tokenizerState = TokenizerState::TAG_OPEN;
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Emit the current input character as a character
                        // token.
                        yield new CharacterToken($c);
                    } elseif ($this->input->isEoS()) {
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Emit the current input character as a character
                        // token.
                        yield new CharacterToken($c);
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#rcdata-state
                case TokenizerState::RCDATA:
                    $c = $this->input->get();

                    if ($c === '&') {
                        // Set the return state to the RCDATA state. Switch to
                        // the character reference state.
                        $returnState = TokenizerState::RCDATA;
                        $this->state->tokenizerState = TokenizerState::CHARACTER_REFERENCE;
                    } elseif ($c === '<') {
                        // Switch to the RCDATA less-than sign state.
                        $this->state->tokenizerState = TokenizerState::RCDATA_LESS_THAN_SIGN;
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Emit a U+FFFD REPLACEMENT CHARACTER character token.
                        yield new CharacterToken("\u{FFFD}");
                    } elseif ($this->input->isEoS()) {
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Emit the current input character as a character
                        // token.
                        yield new CharacterToken($c);
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#rawtext-state
                case TokenizerState::RAWTEXT:
                    $c = $this->input->get();

                    if ($c === '<') {
                        // Switch to the RAWTEXT less-than sign state.
                        $this->state->tokenizerState = TokenizerState::RAWTEXT_LESS_THAN_SIGN;
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Emit a U+FFFD REPLACEMENT CHARACTER character token.
                        yield new CharacterToken("\u{FFFD}");
                    } elseif ($this->input->isEoS()) {
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Emit the current input character as a character
                        // token.
                        yield new CharacterToken($c);
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-state
                case TokenizerState::SCRIPT_DATA:
                    $c = $this->input->get();

                    if ($c === '<') {
                        // Switch to the script data less-than sign state.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_LESS_THAN_SIGN;
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Emit a U+FFFD REPLACEMENT CHARACTER character token.
                        yield new CharacterToken("\u{FFFD}");
                    } elseif ($this->input->isEoS()) {
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Emit the current input character as a character
                        // token.
                        yield new CharacterToken($c);
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#plaintext-state
                case TokenizerState::PLAINTEXT:
                    $c = $this->input->get();

                    if ($c === "\0") {
                        // Parse error.
                        // Emit a U+FFFD REPLACEMENT CHARACTER character token.
                        yield new CharacterToken("\u{FFFD}");
                    } elseif ($this->input->isEoS()) {
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Emit the current input character as a character token.
                        yield new CharacterToken($c);
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#tag-open-state
                case TokenizerState::TAG_OPEN:
                    $c = $this->input->get();

                    if ($c === '!') {
                        // Switch to the markup declaration open state.
                        $this->state->tokenizerState = TokenizerState::MARKUP_DECLARATION_OPEN;
                    } elseif ($c === '/') {
                        // Switch to the end tag open state.
                        $this->state->tokenizerState = TokenizerState::END_TAG_OPEN;
                    } elseif (ctype_alpha($c)) {
                        // Create a new start tag token, set its tag name to the
                        // empty string. Reconsume in the tag name state.
                        $tagToken = new StartTagToken('');
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::TAG_NAME;
                    } elseif ($c === '?') {
                        // Parse error.
                        // Create a comment token whose data is the empty
                        // string. Reconsume in the bogus comment state.
                        $commentToken = new CommentToken('');
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::BOGUS_COMMENT;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit a U+003C LESS-THAN SIGN character token and an
                        // end-of-file token.
                        yield new CharacterToken('<');
                        yield new EOFToken();

                        return;
                    } else {
                        // Parse error.
                        // Emit a U+003C LESS-THAN SIGN character token.
                        // Reconsume in the data state.
                        yield new CharacterToken('<');

                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::DATA;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#end-tag-open-state
                case TokenizerState::END_TAG_OPEN:
                    $c = $this->input->get();

                    if (ctype_alpha($c)) {
                        // Create a new end tag token, set its tag name to the
                        // empty string. Reconsume in the tag name state.
                        $tagToken = new EndTagToken('');
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::TAG_NAME;
                    } elseif ($c === '>') {
                        // Parse error.
                        // Switch to the data state.
                        $this->state->tokenizerState = TokenizerState::DATA;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit a U+003C LESS-THAN SIGN character token, a
                        // U+002F SOLIDUS character token and an end-of-file
                        // token.
                        yield new CharacterToken('<');
                        yield new CharacterToken('/');
                        yield new EOFToken();

                        return;
                    } else {
                        // Parse error.
                        // Create a comment token whose data is the empty
                        // string. Reconsume in the bogus comment state.
                        $commentToken = new CommentToken('');
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::BOGUS_COMMENT;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#tag-name-state
                case TokenizerState::TAG_NAME:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Switch to the before attribute name state.
                        $this->state->tokenizerState = TokenizerState::BEFORE_ATTRIBUTE_NAME;
                    } elseif ($c === '/') {
                        // Switch to the self-closing start tag state.
                        $this->state->tokenizerState = TokenizerState::SELF_CLOSING_START_TAG;
                    } elseif ($c === '>') {
                        // Switch to the data state. Emit the current tag token.
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $tagToken;
                    } elseif (ctype_upper($c)) {
                        // Append the lowercase version of the current input
                        // character (add 0x0020 to the character's code point)
                        // to the current tag token's tag name.
                        $tagToken->tagName .= strtolower($c);
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Append a U+FFFD REPLACEMENT CHARACTER character to
                        // the current tag token's tag name.
                        $tagToken->tagName .= "\u{FFFD}";
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Append the current input character to the current tag
                        // token's tag name.
                        $tagToken->tagName .= $c;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#rcdata-less-than-sign-state
                case TokenizerState::RCDATA_LESS_THAN_SIGN:
                    $c = $this->input->get();

                    if ($c === '/') {
                        // Set the temporary buffer to the empty string. Switch
                        // to the RCDATA end tag open state.
                        $buffer = '';
                        $this->state->tokenizerState = TokenizerState::RCDATA_END_TAG_OPEN;
                    } else {
                        // Emit a U+003C LESS-THAN SIGN character token.
                        // Reconsume in the RCDATA state.
                        yield new CharacterToken('<');

                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::RCDATA;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#rcdata-end-tag-open-state
                case TokenizerState::RCDATA_END_TAG_OPEN:
                    $c = $this->input->get();

                    if (ctype_alpha($c)) {
                        // Create a new end tag token, set its tag name to the
                        // empty string. Reconsume in the RCDATA end tag name
                        // state.
                        $tagToken = new EndTagToken('');
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::RCDATA_END_TAG_NAME;
                    } else {
                        // Emit a U+003C LESS-THAN SIGN character token and a
                        // U+002F SOLIDUS character token. Reconsume in the
                        // RCDATA state.
                        yield new CharacterToken('<');
                        yield new CharacterToken('/');

                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::RCDATA;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#rcdata-end-tag-name-state
                case TokenizerState::RCDATA_END_TAG_NAME:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // If the current end tag token is an appropriate end
                        // tag token, then switch to the before attribute name
                        // state. Otherwise, treat it as per the "anything else"
                        // entry below.
                        $isAppropriateEndTag = $this->isAppropriateEndTag($tagToken);

                        if ($isAppropriateEndTag) {
                            $this->state->tokenizerState = TokenizerState::BEFORE_ATTRIBUTE_NAME;
                        } else {
                            yield new CharacterToken('<');
                            yield new CharacterToken('/');

                            $streamBuffer = new CodePointStream($buffer);

                            while (!$streamBuffer->isEoS()) {
                                yield new CharacterToken(
                                    $streamBuffer->get()
                                );
                            }

                            $this->input->seek(-1);
                            $this->state->tokenizerState = TokenizerState::RCDATA;
                        }
                    } elseif ($c === '/') {
                        // If the current end tag token is an appropriate end
                        // tag token, then switch to the self-closing start tag
                        // state. Otherwise, treat it as per the "anything else"
                        // entry below.
                        $isAppropriateEndTag = $this->isAppropriateEndTag($tagToken);

                        if ($isAppropriateEndTag) {
                            $this->state->tokenizerState = TokenizerState::SELF_CLOSING_START_TAG;
                        } else {
                            yield new CharacterToken('<');
                            yield new CharacterToken('/');

                            $streamBuffer = new CodePointStream($buffer);

                            while (!$streamBuffer->isEoS()) {
                                yield new CharacterToken(
                                    $streamBuffer->get()
                                );
                            }

                            $this->input->seek(-1);
                            $this->state->tokenizerState = TokenizerState::RCDATA;
                        }
                    } elseif ($c === '>') {
                        // If the current end tag token is an appropriate end
                        // tag token, then switch to the data state and emit the
                        // current tag token. Otherwise, treat it as per the
                        // "anything else" entry below.
                        $isAppropriateEndTag = $this->isAppropriateEndTag($tagToken);

                        if ($isAppropriateEndTag) {
                            $this->state->tokenizerState = TokenizerState::DATA;

                            yield $tagToken;
                        } else {
                            yield new CharacterToken('<');
                            yield new CharacterToken('/');

                            $streamBuffer = new CodePointStream($buffer);

                            while (!$streamBuffer->isEoS()) {
                                yield new CharacterToken(
                                    $streamBuffer->get()
                                );
                            }

                            $this->input->seek(-1);
                            $this->state->tokenizerState = TokenizerState::RCDATA;
                        }
                    } elseif (ctype_upper($c)) {
                        // Append the lowercase version of the current input
                        // character (add 0x0020 to the character's code point)
                        // to the current tag token's tag name. Append the
                        // current input character to the temporary buffer.
                        $tagToken->tagName .= strtolower($c);
                        $buffer .= $c;
                    } elseif (ctype_lower($c)) {
                        // Append the current input character to the current tag
                        // token's tag name. Append the current input character
                        // to the temporary buffer.
                        $tagToken->tagName .= $c;
                        $buffer .= $c;
                    } else {
                        // Emit a U+003C LESS-THAN SIGN character token, a
                        // U+002F SOLIDUS character token, and a character token
                        // for each of the characters in the temporary buffer
                        // (in the order they were added to the buffer).
                        // Reconsume in the RCDATA state.
                        yield new CharacterToken('<');
                        yield new CharacterToken('/');

                        $streamBuffer = new CodePointStream($buffer);

                        while (!$streamBuffer->isEoS()) {
                            yield new CharacterToken($streamBuffer->get());
                        }

                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::RCDATA;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#rawtext-less-than-sign-state
                case TokenizerState::RAWTEXT_LESS_THAN_SIGN:
                    $c = $this->input->get();

                    if ($c === '/') {
                        // Set the temporary buffer to the empty string. Switch
                        // to the RCDATA end tag open state.
                        $buffer = '';
                        $this->state->tokenizerState = TokenizerState::RAWTEXT_END_TAG_OPEN;
                    } else {
                        // Emit a U+003C LESS-THAN SIGN character token.
                        // Reconsume in the RAWTEXT state.
                        yield new CharacterToken('<');

                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::RAWTEXT;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#rawtext-end-tag-open-state
                case TokenizerState::RAWTEXT_END_TAG_OPEN:
                    $c = $this->input->get();

                    if (ctype_alpha($c)) {
                        // Create a new end tag token, set its tag name to the
                        // empty string. Reconsume in the RAWTEXT end tag name
                        // state.
                        $tagToken = new EndTagToken('');
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::RAWTEXT_END_TAG_NAME;
                    } else {
                        // Emit a U+003C LESS-THAN SIGN character token and a
                        // U+002F SOLIDUS character token. Reconsume in the
                        // RAWTEXT state.
                        yield new CharacterToken('<');
                        yield new CharacterToken('/');

                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::RAWTEXT;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#rawtext-end-tag-name-state
                case TokenizerState::RAWTEXT_END_TAG_NAME:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // If the current end tag token is an appropriate end
                        // tag token, then switch to the before attribute name
                        // state. Otherwise, treat it as per the "anything else"
                        // entry below.
                        $isAppropriateEndTag = $this->isAppropriateEndTag($tagToken);

                        if ($isAppropriateEndTag) {
                            $this->state->tokenizerState = TokenizerState::BEFORE_ATTRIBUTE_NAME;
                        } else {
                            yield new CharacterToken('<');
                            yield new CharacterToken('/');

                            $bufferStream = new CodePointStream($buffer);

                            while (!$bufferStream->isEoS()) {
                                yield new CharacterToken(
                                    $bufferStream->get()
                                );
                            }

                            $this->input->seek(-1);
                            $this->state->tokenizerState = TokenizerState::RAWTEXT;
                        }
                    } elseif ($c === '/') {
                        // If the current end tag token is an appropriate end
                        // tag token, then switch to the self-closing start tag
                        // state. Otherwise, treat it as per the "anything else"
                        // entry below.
                        $isAppropriateEndTag = $this->isAppropriateEndTag($tagToken);

                        if ($isAppropriateEndTag) {
                            $this->state->tokenizerState = TokenizerState::SELF_CLOSING_START_TAG;
                        } else {
                            yield new CharacterToken('<');
                            yield new CharacterToken('/');

                            $bufferStream = new CodePointStream($buffer);

                            while (!$bufferStream->isEoS()) {
                                yield new CharacterToken(
                                    $bufferStream->get()
                                );
                            }

                            $this->input->seek(-1);
                            $this->state->tokenizerState = TokenizerState::RAWTEXT;
                        }
                    } elseif ($c === '>') {
                        // If the current end tag token is an appropriate end
                        // tag token, then switch to the data state and emit the
                        // current tag token. Otherwise, treat it as per the
                        // "anything else" entry below.
                        $isAppropriateEndTag = $this->isAppropriateEndTag($tagToken);

                        if ($isAppropriateEndTag) {
                            $this->state->tokenizerState = TokenizerState::DATA;

                            yield $tagToken;
                        } else {
                            yield new CharacterToken('<');
                            yield new CharacterToken('/');

                            $bufferStream = new CodePointStream($buffer);

                            while (!$bufferStream->isEoS()) {
                                yield new CharacterToken(
                                    $bufferStream->get()
                                );
                            }

                            $this->input->seek(-1);
                            $this->state->tokenizerState = TokenizerState::RAWTEXT;
                        }
                    } elseif (ctype_upper($c)) {
                        // Append the lowercase version of the current input
                        // character (add 0x0020 to the character's code point)
                        // to the current tag token's tag name. Append the
                        // current input character to the temporary buffer.
                        $tagToken->tagName .= strtolower($c);
                        $buffer .= $c;
                    } elseif (ctype_lower($c)) {
                        // Append the current input character to the current tag
                        // token's tag name. Append the current input character
                        // to the temporary buffer.
                        $tagToken->tagName .= $c;
                        $buffer .= $c;
                    } else {
                        // Emit a U+003C LESS-THAN SIGN character token, a
                        // U+002F SOLIDUS character token, and a character token
                        // for each of the characters in the temporary buffer
                        // (in the order they were added to the buffer).
                        // Reconsume in the RAWTEXT state.
                        yield new CharacterToken('<');
                        yield new CharacterToken('/');

                        $bufferStream = new CodePointStream($buffer);

                        while (!$bufferStream->isEoS()) {
                            yield new CharacterToken($bufferStream->get());
                        }

                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::RAWTEXT;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-less-than-sign-state
                case TokenizerState::SCRIPT_DATA_LESS_THAN_SIGN:
                    $c = $this->input->get();

                    if ($c === '/') {
                        // Set the temporary buffer to the empty string. Switch
                        // to the script data end tag open state.
                        $buffer = '';
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_END_TAG_OPEN;
                    } elseif ($c === '!') {
                        // Switch to the script data escape start state. Emit a
                        // U+003C LESS-THAN SIGN character token and a U+0021
                        // EXCLAMATION MARK character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPE_START;

                        yield new CharacterToken('<');
                        yield new CharacterToken('!');
                    } else {
                        // Emit a U+003C LESS-THAN SIGN character token.
                        // Reconsume in the script data state.
                        yield new CharacterToken('<');

                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-end-tag-open-state
                case TokenizerState::SCRIPT_DATA_END_TAG_OPEN:
                    $c = $this->input->get();

                    if (ctype_alpha($c)) {
                        // Create a new end tag token, set its tag name to the
                        // empty string. Reconsume in the script data end tag
                        // name state.
                        $tagToken = new EndTagToken('');
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_END_TAG_NAME;
                    } else {
                        // Emit a U+003C LESS-THAN SIGN character token and a
                        // U+002F SOLIDUS character token. Reconsume in the
                        // script data state.
                        yield new CharacterToken('<');
                        yield new CharacterToken('/');

                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-end-tag-name-state
                case TokenizerState::SCRIPT_DATA_END_TAG_NAME:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // If the current end tag token is an appropriate end
                        // tag token, then switch to the before attribute name
                        // state. Otherwise, treat it as per the "anything else"
                        // entry below.
                        $isAppropriateEndTag = $this->isAppropriateEndTag($tagToken);

                        if ($isAppropriateEndTag) {
                            $this->state->tokenizerState = TokenizerState::BEFORE_ATTRIBUTE_NAME;
                        } else {
                            yield new CharacterToken('<');
                            yield new CharacterToken('/');

                            $bufferStream = new CodePointStream($buffer);

                            while (!$bufferStream->isEoS()) {
                                yield new CharacterToken(
                                    $bufferStream->get()
                                );
                            }

                            $this->input->seek(-1);
                            $this->state->tokenizerState = TokenizerState::SCRIPT_DATA;
                        }
                    } elseif ($c === '/') {
                        // If the current end tag token is an appropriate end
                        // tag token, then switch to the self-closing start tag
                        // state. Otherwise, treat it as per the "anything else"
                        // entry below.
                        $isAppropriateEndTag = $this->isAppropriateEndTag($tagToken);

                        if ($isAppropriateEndTag) {
                            $this->state->tokenizerState = TokenizerState::SELF_CLOSING_START_TAG;
                        } else {
                            yield new CharacterToken('<');
                            yield new CharacterToken('/');

                            $bufferStream = new CodePointStream($buffer);

                            while (!$bufferStream->isEoS()) {
                                yield new CharacterToken(
                                    $bufferStream->get()
                                );
                            }

                            $this->input->seek(-1);
                            $this->state->tokenizerState = TokenizerState::SCRIPT_DATA;
                        }
                    } elseif ($c === '>') {
                        // If the current end tag token is an appropriate end
                        // tag token, then switch to the data state and emit the
                        // current tag token. Otherwise, treat it as per the
                        // "anything else" entry below.
                        $isAppropriateEndTag = $this->isAppropriateEndTag($tagToken);

                        if ($isAppropriateEndTag) {
                            $this->state->tokenizerState = TokenizerState::DATA;

                            yield $tagToken;
                        } else {
                            yield new CharacterToken('<');
                            yield new CharacterToken('/');

                            $bufferStream = new CodePointStream($buffer);

                            while (!$bufferStream->isEoS()) {
                                yield new CharacterToken($bufferStream->get());
                            }

                            $this->input->seek(-1);
                            $this->state->tokenizerState = TokenizerState::SCRIPT_DATA;
                        }
                    } elseif (ctype_upper($c)) {
                        // Append the lowercase version of the current input
                        // character (add 0x0020 to the character's code point)
                        // to the current tag token's tag name. Append the
                        // current input character to the temporary buffer.
                        $tagToken->tagName .= strtolower($c);
                        $buffer .= $c;
                    } elseif (ctype_lower($c)) {
                        // Append the current input character to the current tag
                        // token's tag name. Append the current input character
                        // to the temporary buffer.
                        $tagToken->tagName .= $c;
                        $buffer .= $c;
                    } else {
                        // Emit a U+003C LESS-THAN SIGN character token, a
                        // U+002F SOLIDUS character token, and a character token
                        // for each of the characters in the temporary buffer
                        // (in the order they were added to the buffer).
                        // Reconsume in the script data state.
                        yield new CharacterToken('<');
                        yield new CharacterToken('/');

                        $bufferStream = new CodePointStream($buffer);

                        while (!$bufferStream->isEoS()) {
                            yield new CharacterToken($bufferStream->get());
                        }

                        $this->input->seek(-1);
                        $this->state->tokenizerState =
                            TokenizerState::SCRIPT_DATA;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-escape-start-state
                case TokenizerState::SCRIPT_DATA_ESCAPE_START:
                    $c = $this->input->get();

                    if ($c === '-') {
                        // Switch to the script data escape start dash state.
                        // Emit a U+002D HYPHEN-MINUS character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPE_START_DASH;

                        yield new CharacterToken('-');
                    } else {
                        // Reconsume in the script data state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-escape-start-dash-state
                case TokenizerState::SCRIPT_DATA_ESCAPE_START_DASH:
                    $c = $this->input->get();

                    if ($c === '-') {
                        // Switch to the script data escaped dash dash state.
                        // Emit a U+002D HYPHEN-MINUS character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED_DASH_DASH;

                        yield new CharacterToken('-');
                    } else {
                        // Reconsume in the script data state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-escaped-state
                case TokenizerState::SCRIPT_DATA_ESCAPED:
                    $c = $this->input->get();

                    if ($c === '-') {
                        // Switch to the script data escaped dash state. Emit a
                        // U+002D HYPHEN-MINUS character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED_DASH;

                        yield new CharacterToken('-');
                    } elseif ($c === '<') {
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN;
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Emit a U+FFFD REPLACEMENT CHARACTER character token.
                        yield new CharacterToken("\u{FFFD}");
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Emit the current input character as a character token.
                        yield new CharacterToken($c);
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-escaped-dash-state
                case TokenizerState::SCRIPT_DATA_ESCAPED_DASH:
                    $c = $this->input->get();

                    if ($c === '-') {
                        // Switch to the script data escaped dash state. Emit a
                        // U+002D HYPHEN-MINUS character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED_DASH_DASH;

                        yield new CharacterToken('-');
                    } elseif ($c === '<') {
                        // Switch to the script data escaped less-than sign state.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN;
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Emit a U+FFFD REPLACEMENT CHARACTER character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED;

                        yield new CharacterToken("\u{FFFD}");
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Switch to the script data escaped state. Emit the
                        // current input character as a character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED;

                        yield new CharacterToken($c);
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-escaped-dash-dash-state
                case TokenizerState::SCRIPT_DATA_ESCAPED_DASH_DASH:
                    $c = $this->input->get();

                    if ($c === '-') {
                        // Emit a U+002D HYPHEN-MINUS character token.
                        yield new CharacterToken('-');
                    } elseif ($c === '<') {
                        // Switch to the script data escaped less-than sign state.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN;
                    } elseif ($c === '>') {
                        // Switch to the script data state. Emit a
                        // U+003E GREATER-THAN SIGN character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA;

                        yield new CharacterToken('>');
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Switch to the script data escaped state. Emit a
                        // U+FFFD REPLACEMENT CHARACTER character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED;

                        yield new CharacterToken("\u{FFFD}");
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Switch to the script data escaped state. Emit the
                        // current input character as a character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED;

                        yield new CharacterToken($c);
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-escaped-less-than-sign-state
                case TokenizerState::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN:
                    $c = $this->input->get();

                    if ($c === '/') {
                        // Set the temporary buffer to the empty string. Switch
                        // to the script data escaped end tag open state.
                        $buffer = '';
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED_END_TAG_OPEN;
                    } elseif (ctype_alpha($c)) {
                        // Set the temporary buffer to the empty string. Emit a
                        // U+003C LESS-THAN SIGN character token. Reconsume in
                        // the script data double escape start state.
                        $buffer = '';

                        yield new CharacterToken('<');

                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPE_START;
                    } else {
                        // Emit a U+003C LESS-THAN SIGN character token.
                        // Reconsume in the script data escaped state.
                        yield new CharacterToken('<');

                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-escaped-end-tag-open-state
                case TokenizerState::SCRIPT_DATA_ESCAPED_END_TAG_OPEN:
                    $c = $this->input->get();

                    if (ctype_alpha($c)) {
                        // Create a new end tag token. Reconsume in the script
                        // data escaped end tag name state. (Don't emit the
                        // token yet; further details will be filled in before
                        // it is emitted.)
                        $tagToken = new EndTagToken();
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED_END_TAG_NAME;
                    } else {
                        // Emit a U+003C LESS-THAN SIGN character token and a
                        // U+002F SOLIDUS character token. Reconsume in the
                        // script data escaped state.
                        yield new CharacterToken('<');
                        yield new CharacterToken('/');

                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-escaped-end-tag-name-state
                case TokenizerState::SCRIPT_DATA_ESCAPED_END_TAG_NAME:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // If the current end tag token is an appropriate end
                        // tag token, then switch to the before attribute name
                        // state. Otherwise, treat it as per the "anything else"
                        // entry below.
                        $isAppropriateEndTag = $this->isAppropriateEndTag($tagToken);

                        if ($isAppropriateEndTag) {
                            $this->state->tokenizerState = TokenizerState::BEFORE_ATTRIBUTE_NAME;
                        } else {
                            yield new CharacterToken('<');
                            yield new CharacterToken('/');

                            $bufferStream = new CodePointStream($buffer);

                            while (!$bufferStream->isEoS()) {
                                yield new CharacterToken(
                                    $bufferStream->get()
                                );
                            }

                            $this->input->seek(-1);
                            $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED;
                        }
                    } elseif ($c === '/') {
                        // If the current end tag token is an appropriate end
                        // tag token, then switch to the self-closing start tag
                        // state. Otherwise, treat it as per the "anything else"
                        // entry below.
                        $isAppropriateEndTag = $this->isAppropriateEndTag($tagToken);

                        if ($isAppropriateEndTag) {
                            $this->state->tokenizerState = TokenizerState::SELF_CLOSING_START_TAG;
                        } else {
                            yield new CharacterToken('<');
                            yield new CharacterToken('/');

                            $bufferStream = new CodePointStream($buffer);

                            while (!$bufferStream->isEoS()) {
                                yield new CharacterToken(
                                    $bufferStream->get()
                                );
                            }

                            $this->input->seek(-1);
                            $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED;
                        }
                    } elseif ($c === '>') {
                        // If the current end tag token is an appropriate end
                        // tag token, then switch to the data state and emit the
                        // current tag token. Otherwise, treat it as per the
                        // "anything else" entry below.
                        $isAppropriateEndTag = $this->isAppropriateEndTag($tagToken);

                        if ($isAppropriateEndTag) {
                            $this->state->tokenizerState = TokenizerState::DATA;

                            yield $tagToken;
                        } else {
                            yield new CharacterToken('<');
                            yield new CharacterToken('/');

                            $bufferStream = new CodePointStream($buffer);

                            while (!$bufferStream->isEoS()) {
                                yield new CharacterToken(
                                    $bufferStream->get()
                                );
                            }

                            $this->input->seek(-1);
                            $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED;
                        }
                    } elseif (ctype_upper($c)) {
                        // Append the lowercase version of the current input
                        // character (add 0x0020 to the character's code point)
                        // to the current tag token's tag name. Append the
                        // current input character to the temporary buffer.
                        $tagToken->tagName .= strtolower($c);
                        $buffer .= $c;
                    } elseif (ctype_lower($c)) {
                        // Append the current input character to the current tag
                        // token's tag name. Append the current input character
                        // to the temporary buffer.
                        $tagToken->tagName .= $c;
                        $buffer .= $c;
                    } else {
                        // Emit a U+003C LESS-THAN SIGN character token, a
                        // U+002F SOLIDUS character token, and a character
                        // token for each of the characters in the temporary
                        // buffer (in the order they were added to the buffer).
                        // Reconsume in the script data escaped state.
                        yield new CharacterToken('<');
                        yield new CharacterToken('/');

                        $bufferStream = new CodePointStream($buffer);

                        while (!$bufferStream->isEoS()) {
                            yield new CharacterToken($bufferStream->get());
                        }

                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-double-escape-start-state
                case TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPE_START:
                    $c = $this->input->get();

                    if (
                        $c === "\x09"
                        || $c === "\x0A"
                        || $c === "\x0C"
                        || $c === "\x20"
                        || $c === '/'
                        || $c === '>'
                    ) {
                        // If the temporary buffer is the string "script",
                        // then switch to the script data double escaped state.
                        // Otherwise, switch to the script data escaped state.
                        // Emit the current input character as a character token.
                        if ($buffer === 'script') {
                            $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED;
                        } else {
                            $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED;
                        }

                        yield new CharacterToken($c);
                    } elseif (ctype_upper($c)) {
                        // Append the lowercase version of the current input
                        // character (add 0x0020 to the character's code point)
                        // to the temporary buffer. Emit the current input
                        // character as a character token.
                        $buffer .= strtolower($c);

                        yield new CharacterToken($c);
                    } elseif (ctype_lower($c)) {
                        // Append the current input character to the temporary
                        // buffer. Emit the current input character as a
                        // character token.
                        $buffer .= $c;

                        yield new CharacterToken($c);
                    } else {
                        // Reconsume in the script data escaped state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-double-escaped-state
                case TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED:
                    $c = $this->input->get();

                    if ($c === '-') {
                        // Switch to the script data double escaped dash state.
                        // Emit a U+002D HYPHEN-MINUS character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED_DASH;

                        yield new CharacterToken('-');
                    } elseif ($c === '<') {
                        // Switch to the script data double escaped less-than
                        // sign state. Emit a U+003C LESS-THAN SIGN character
                        // token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN;

                        yield new CharacterToken('<');
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Emit a U+FFFD REPLACEMENT CHARACTER character token.
                        yield new CharacterToken("\u{FFFD}");
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Emit the current input character as a character
                        // token.
                        yield new CharacterToken($c);
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-double-escaped-dash-state
                case TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED_DASH:
                    $c = $this->input->get();

                    if ($c === '-') {
                        // Switch to the script data double escaped dash dash
                        // state. Emit a U+002D HYPHEN-MINUS character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_DASH;

                        yield new CharacterToken('-');
                    } elseif ($c === '<') {
                        // Switch to the script data double escaped less-than
                        // sign state. Emit a U+003C LESS-THAN SIGN character
                        // token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN;

                        yield new CharacterToken('<');
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Switch to the script data double escaped state. Emit
                        // a U+FFFD REPLACEMENT CHARACTER character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED;

                        yield new CharacterToken("\u{FFFD}");
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Switch to the script data double escaped state. Emit
                        // the current input character as a character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED;

                        yield new CharacterToken($c);
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-double-escaped-dash-dash-state
                case TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_DASH:
                    $c = $this->input->get();

                    if ($c === '-') {
                        // Emit a U+002D HYPHEN-MINUS character token.
                        yield new CharacterToken('-');
                    } elseif ($c === '<') {
                        // Switch to the script data double escaped less-than
                        // sign state. Emit a U+003C LESS-THAN SIGN character
                        // token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN;

                        yield new CharacterToken('<');
                    } elseif ($c === '>') {
                        // Switch to the script data state. Emit a U+003E
                        // GREATER-THAN SIGN character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA;

                        yield new CharacterToken('>');
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Switch to the script data double escaped state. Emit
                        // a U+FFFD REPLACEMENT CHARACTER character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED;

                        yield new CharacterToken("\u{FFFD}");
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Switch to the script data double escaped state. Emit
                        // the current input character as a character token.
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED;

                        yield new CharacterToken($c);
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-double-escaped-less-than-sign-state
                case TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN:
                    $c = $this->input->get();

                    if ($c === '/') {
                        // Set the temporary buffer to the empty string. Switch
                        // to the script data double escape end state. Emit a
                        // U+002F SOLIDUS character token.
                        $buffer = '';
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPE_END;

                        yield new CharacterToken('/');
                    } else {
                        // Reconsume in the script data double escaped state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#script-data-double-escape-end-state
                case TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPE_END:
                    $c = $this->input->get();

                    if (
                        $c === "\x09"
                        || $c === "\x0A"
                        || $c === "\x0C"
                        || $c === "\x20"
                        || $c === "\x2F"
                        || $c === "\x3E"
                    ) {
                        // If the temporary buffer is the string "script", then
                        // switch to the script data escaped state. Otherwise,
                        // switch to the script data double escaped state. Emit
                        // the current input character as a character token.
                        if ($buffer === 'script') {
                            $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_ESCAPED;
                        } else {
                            $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED;
                        }

                        yield new CharacterToken($c);
                    } elseif (ctype_upper($c)) {
                        // Append the lowercase version of the current input
                        // character (add 0x0020 to the character's code point)
                        // to the temporary buffer. Emit the current input
                        // character as a character token.
                        $buffer .= strtolower($c);

                        yield new CharacterToken($c);
                    } elseif (ctype_lower($c)) {
                        // Append the current input character to the temporary
                        // buffer. Emit the current input character as a
                        // character token.
                        $buffer .= $c;

                        yield new CharacterToken($c);
                    } else {
                        // Reconsume in the script data double escaped state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::SCRIPT_DATA_DOUBLE_ESCAPED;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#before-attribute-name-state
                case TokenizerState::BEFORE_ATTRIBUTE_NAME:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Ignore the character.
                    } elseif ($c === '/' || $c === '>' || $this->input->isEoS()) {
                        // Reconsume in the after attribute name state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::AFTER_ATTRIBUTE_NAME;
                    } elseif ($c === '=') {
                        // Parse error.
                        // Start a new attribute in the current tag token. Set
                        // that attribute's name to the current input character,
                        // and its value to the empty string. Switch to the
                        // attribute name state.
                        $attributeToken = new AttributeToken($c, '');
                        $tagToken->attributes->push($attributeToken);
                        $this->state->tokenizerState = TokenizerState::ATTRIBUTE_NAME;
                    } else {
                        // Start a new attribute in the current tag token. Set
                        // that attribute name and value to the empty string.
                        // Reconsume in the attribute name state.
                        $attributeToken = new AttributeToken('', '');
                        $tagToken->attributes->push($attributeToken);
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::ATTRIBUTE_NAME;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#attribute-name-state
                case TokenizerState::ATTRIBUTE_NAME:
                    $c = $this->input->get();
                    $state = TokenizerState::ATTRIBUTE_NAME;

                    if (
                        $c === "\x09"
                        || $c === "\x0A"
                        || $c === "\x0C"
                        || $c === "\x20"
                        || $c === '/'
                        || $c === '>'
                        || $this->input->isEoS()
                    ) {
                        // Reconsume in the after attribute name state.
                        $this->input->seek(-1);
                        $state = TokenizerState::AFTER_ATTRIBUTE_NAME;
                        $this->state->tokenizerState = $state;
                    } elseif ($c === '=') {
                        // Switch to the before attribute value state.
                        $state = TokenizerState::BEFORE_ATTRIBUTE_VALUE;
                        $this->state->tokenizerState = $state;
                    } elseif (ctype_upper($c)) {
                        // Append the lowercase version of the current input
                        // character (add 0x0020 to the character's code point)
                        // to the current attribute's name.
                        $attributeToken->name .= strtolower($c);
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Append a U+FFFD REPLACEMENT CHARACTER character to
                        // the current attribute's name.
                        $attributeToken->name .= "\u{FFFD}";
                    } elseif ($c === '"' || $c === '\'' || $c === '<') {
                        // Parse error.
                        // Treat it as per the "anything else" entry below.
                        $attributeToken->name .= $c;
                    } else {
                        // Append the current input character to the current
                        // attribute's name.
                        $attributeToken->name .= $c;
                    }

                    // When the user agent leaves the attribute name state
                    // (and before emitting the tag token, if appropriate), the
                    // complete attribute's name must be compared to the other
                    // attributes on the same token; if there is already an
                    // attribute on the token with the exact same name, then
                    // this is a parse error and the new attribute must be
                    // removed from the token.
                    if ($state !== TokenizerState::ATTRIBUTE_NAME) {
                        $state = TokenizerState::ATTRIBUTE_NAME;
                        $attributes = $tagToken->attributes;
                        $attrName = $attributeToken->name;

                        foreach ($attributes as $attr) {
                            if ($attr->name === $attrName && $attr !== $attributeToken) {
                                // Parse error.
                                $attributes->pop();

                                break;
                            }
                        }
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#after-attribute-name-state
                case TokenizerState::AFTER_ATTRIBUTE_NAME:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Ignore the character.
                    } elseif ($c === '/') {
                        // Switch to the self-closing start tag state.
                        $this->state->tokenizerState = TokenizerState::SELF_CLOSING_START_TAG;
                    } elseif ($c === '=') {
                        // Switch to the before attribute value state.
                        $this->state->tokenizerState = TokenizerState::BEFORE_ATTRIBUTE_VALUE;
                    } elseif ($c === '>') {
                        // Switch to the data state. Emit the current tag token.
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $tagToken;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Start a new attribute in the current tag token. Set
                        // that attribute name and value to the empty string.
                        // Reconsume in the attribute name state.
                        $attributeToken = new AttributeToken('', '');
                        $tagToken->attributes->push($attributeToken);
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::ATTRIBUTE_NAME;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#before-attribute-value-state
                case TokenizerState::BEFORE_ATTRIBUTE_VALUE:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Ignore the character.
                    } elseif ($c === '"') {
                        // Switch to the attribute value (double-quoted) state.
                        $this->state->tokenizerState = TokenizerState::ATTRIBUTE_VALUE_DOUBLE_QUOTED;
                    } elseif ($c === '\'') {
                        // Switch to the attribute value (single-quoted) state.
                        $this->state->tokenizerState = TokenizerState::ATTRIBUTE_VALUE_SINGLE_QUOTED;
                    } elseif ($c === '>') {
                        // Parse error.
                        // Switch to the data state. Emit the current tag token.
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $tagToken;
                    } else {
                        // Reconsume in the attribute value (unquoted) state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::ATTRIBUTE_VALUE_UNQUOTED;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#attribute-value-(double-quoted)-state
                case TokenizerState::ATTRIBUTE_VALUE_DOUBLE_QUOTED:
                    $c = $this->input->get();

                    if ($c === '"') {
                        // Switch to the after attribute value (quoted) state.
                        $this->state->tokenizerState = TokenizerState::AFTER_ATTRIBUTE_VALUE_QUOTED;
                    } elseif ($c === '&') {
                        // Set the return state to the attribute value
                        // (double-quoted) state. Switch to the character
                        // reference state.
                        $returnState = TokenizerState::ATTRIBUTE_VALUE_DOUBLE_QUOTED;
                        $this->state->tokenizerState = TokenizerState::CHARACTER_REFERENCE;
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Append a U+FFFD REPLACEMENT CHARACTER character to
                        // the current attribute's value.
                        $attributeToken->value .= "\u{FFFD}";
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Append the current input character to the current
                        // attribute's value.
                        $attributeToken->value .= $c;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#attribute-value-(single-quoted)-state
                case TokenizerState::ATTRIBUTE_VALUE_SINGLE_QUOTED:
                    $c = $this->input->get();

                    if ($c === '\'') {
                        // Switch to the after attribute value (quoted) state.
                        $this->state->tokenizerState =
                            TokenizerState::AFTER_ATTRIBUTE_VALUE_QUOTED;
                    } elseif ($c === '&') {
                        // Set the return state to the attribute value
                        // (single-quoted) state. Switch to the character
                        // reference state.
                        $returnState =
                            TokenizerState::ATTRIBUTE_VALUE_SINGLE_QUOTED;
                        $this->state->tokenizerState = TokenizerState::CHARACTER_REFERENCE;
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Append a U+FFFD REPLACEMENT CHARACTER character to
                        // the current attribute's value.
                        $attributeToken->value .= "\u{FFFD}";
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Append the current input character to the current
                        // attribute's value.
                        $attributeToken->value .= $c;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#attribute-value-(unquoted)-state
                case TokenizerState::ATTRIBUTE_VALUE_UNQUOTED:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Switch to the before attribute name state.
                        $this->state->tokenizerState = TokenizerState::BEFORE_ATTRIBUTE_NAME;
                    } elseif ($c === '&') {
                        // Set the return state to the attribute value
                        // (unquoted) state. Switch to the character reference
                        // state.
                        $returnState = TokenizerState::ATTRIBUTE_VALUE_UNQUOTED;
                        $this->state->tokenizerState = TokenizerState::CHARACTER_REFERENCE;
                    } elseif ($c === '>') {
                        // Switch to the data state. Emit the current tag token.
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $tagToken;
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Append a U+FFFD REPLACEMENT CHARACTER character to
                        // the current attribute's value.
                        $attributeToken->value .= "\u{FFFD}";
                    } elseif ($c === '"' || $c === '\'' || $c === '<' || $c === '=' || $c === '`') {
                        // Parse error.
                        // Treat it as per the "anything else" entry below.
                        $attributeToken->value .= $c;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Append the current input character to the current
                        // attribute's value.
                        $attributeToken->value .= $c;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#after-attribute-value-(quoted)-state
                case TokenizerState::AFTER_ATTRIBUTE_VALUE_QUOTED:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Switch to the before attribute name state.
                        $this->state->tokenizerState = TokenizerState::BEFORE_ATTRIBUTE_NAME;
                    } elseif ($c === '/') {
                        // Switch to the self-closing start tag state.
                        $this->state->tokenizerState = TokenizerState::SELF_CLOSING_START_TAG;
                    } elseif ($c === '>') {
                        // Switch to the data state. Emit the current tag token.
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $tagToken;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Parse error.
                        // Reconsume in the before attribute name state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::BEFORE_ATTRIBUTE_NAME;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#self-closing-start-tag-state
                case TokenizerState::SELF_CLOSING_START_TAG:
                    $c = $this->input->get();

                    if ($c === '>') {
                        // Set the self-closing flag of the current tag token.
                        // Switch to the data state. Emit the current tag token.
                        $tagToken->setSelfClosingFlag();
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $tagToken;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Parse error.
                        // Reconsume in the before attribute name state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::BEFORE_ATTRIBUTE_NAME;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#bogus-comment-state
                case TokenizerState::BOGUS_COMMENT:
                    $c = $this->input->get();

                    if ($c === '>') {
                        // Switch to the data state. Emit the comment token.
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $commentToken;
                    } elseif ($this->input->isEoS()) {
                        // Emit the comment. Emit an end of-file token.
                        yield $commentToken;
                        yield new EOFToken();

                        return;
                    } elseif ($c === "\0") {
                        // Append a U+FFFD REPLACEMENT CHARACTER character to
                        // the comment token's data.
                        $commentToken->data .= "\u{FFFD}";
                    } else {
                        // Append the current input character to the comment
                        // token's data.
                        $commentToken->data .= $c;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#markup-declaration-open-state
                case TokenizerState::MARKUP_DECLARATION_OPEN:
                    // If the next two characters are both U+002D HYPHEN-MINUS
                    // characters (-), consume those two characters, create a
                    // comment token whose data is the empty string, and switch
                    // to the comment start state.
                    if ($this->input->peek(2) === '--') {
                        $this->input->get(2);
                        $commentToken = new CommentToken('');
                        $this->state->tokenizerState = TokenizerState::COMMENT_START;

                        // If the next few characters are an ASCII
                        // case-insensitive match for the word "DOCTYPE"...
                    } elseif (
                        Utils::toASCIILowercase($this->input->peek(7)) === Utils::toASCIILowercase(
                            'DOCTYPE'
                        )
                    ) {
                        // Consume those characters and switch to the DOCTYPE
                        // state.
                        $this->input->get(7);
                        $this->state->tokenizerState = TokenizerState::DOCTYPE;

                        // If the next few characters are a case-sensitive match
                        // for the string "[CDATA[" (the five uppercase letters
                        // "CDATA" with a U+005B LEFT SQUARE BRACKET character
                        // before and after)...
                    } elseif ($this->input->peek(7) === '[CDATA[') {
                        // Consume those characters.
                        $this->input->get(7);

                        // If there is an adjusted current node and it is not an
                        // element in the HTML namespace, then switch to the
                        // CDATA section state. Otherwise, this is a
                        // cdata-in-html-content parse error. Create a comment
                        // token whose data is the "[CDATA[" string. Switch to
                        // the bogus comment state.
                        $node = $this->getAdjustedCurrentNode();

                        if (
                            $node !== null
                            && (
                                !$node instanceof Element
                                || $node->namespaceURI !== Namespaces::HTML
                            )
                        ) {
                            $this->state->tokenizerState = TokenizerState::CDATA_SECTION;
                        } else {
                            $commentToken = new CommentToken('[CDATA[');
                            $this->state->tokenizerState = TokenizerState::BOGUS_COMMENT;
                        }
                    } else {
                        // This is an incorrectly-opened-comment parse error.
                        // Create a comment token whose data is the empty
                        // string. Switch to the bogus comment state (don't
                        // consume anything in the current state).
                        $commentToken = new CommentToken('');
                        $this->state->tokenizerState
                            = TokenizerState::BOGUS_COMMENT;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#comment-start-state
                case TokenizerState::COMMENT_START:
                    $c = $this->input->get();

                    if ($c === '-') {
                        // Switch to the comment start dash state.
                        $this->state->tokenizerState = TokenizerState::COMMENT_START_DASH;
                    } elseif ($c === '>') {
                        // Parse error.
                        // Switch to the data state. Emit the comment token.
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $commentToken;
                    } else {
                        // Reconsume in the comment state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::COMMENT;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#comment-start-dash-state
                case TokenizerState::COMMENT_START_DASH:
                    $c = $this->input->get();

                    if ($c === '-') {
                        // Switch to the comment end state
                        $this->state->tokenizerState = TokenizerState::COMMENT_END;
                    } elseif ($c === '>') {
                        // Parse error.
                        // Switch to the data state. Emit the comment token.
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $commentToken;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit the comment token. Emit an end-of-file token.
                        yield $commentToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Append a U+002D HYPHEN-MINUS character (-) to the
                        // comment token's data. Reconsume in the comment state.
                        $this->input->seek(-1);
                        $commentToken->data .= '-';
                        $this->state->tokenizerState = TokenizerState::COMMENT;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#comment
                case TokenizerState::COMMENT:
                    $c = $this->input->get();

                    if ($c === '<') {
                        // Append the current input character to the comment
                        // token's data. Switch to the comment less-than sign
                        // state.
                        $commentToken->data .= $c;
                        $this->state->tokenizerState = TokenizerState::COMMENT_LESS_THAN_SIGN;
                    } elseif ($c === '-') {
                        // Switch to the comment end dash state
                        $this->state->tokenizerState = TokenizerState::COMMENT_END_DASH;
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Append a U+FFFD REPLACEMENT CHARACTER character to
                        // the comment token's data.
                        $commentToken->data .= "\u{FFFD}";
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit the comment token. Emit an end-of-file token.
                        yield $commentToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Append the current input character to the comment
                        // token's data.
                        $commentToken->data .= $c;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#comment-less-than-sign-state
                case TokenizerState::COMMENT_LESS_THAN_SIGN:
                    $c = $this->input->get();

                    if ($c === '!') {
                        // Append the current input character to the comment
                        // token's data. Switch to the comment less-than sign
                        // bang state.
                        $commentToken->data .= $c;
                        $this->state->tokenizerState = TokenizerState::COMMENT_LESS_THAN_SIGN_BANG;
                    } elseif ($c === '<') {
                        // Append the current input character to the comment
                        // token's data.
                        $commentToken->data .= $c;
                    } else {
                        // Reconsume in the comment state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::COMMENT;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#comment-less-than-sign-bang-state
                case TokenizerState::COMMENT_LESS_THAN_SIGN_BANG:
                    $c = $this->input->get();

                    if ($c === '-') {
                        // Switch to the comment less-than sign bang dash state.
                        $this->state->tokenizerState = TokenizerState::COMMENT_LESS_THAN_SIGN_BANG_DASH;
                    } else {
                        //Reconsume in the comment state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::COMMENT;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#comment-less-than-sign-bang-dash-state
                case TokenizerState::COMMENT_LESS_THAN_SIGN_BANG_DASH:
                    $c = $this->input->get();

                    if ($c === '-') {
                        // Switch to the comment less-than sign bang dash dash
                        // state.
                        $this->state->tokenizerState = TokenizerState::COMMENT_LESS_THAN_SIGN_BANG_DASH_DASH;
                    } else {
                        // Reconsume in the comment end dash state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::COMMENT_END_DASH;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#comment-less-than-sign-bang-dash-dash-state
                case TokenizerState::COMMENT_LESS_THAN_SIGN_BANG_DASH_DASH:
                    $c = $this->input->get();

                    if ($c === '>' || $this->input->isEoS()) {
                        // Reconsume in the comment end state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::COMMENT_END;
                    } else {
                        // Parse error.
                        // Reconsume in the comment end state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::COMMENT_END;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#comment-end-dash-state
                case TokenizerState::COMMENT_END_DASH:
                    $c = $this->input->get();

                    if ($c === '-') {
                        // Switch to the comment end state
                        $this->state->tokenizerState = TokenizerState::COMMENT_END;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit the comment token. Emit an end-of-file token.
                        yield $commentToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Append a U+002D HYPHEN-MINUS character (-) to the
                        // comment token's data. Reconsume in the comment state.
                        $this->input->seek(-1);
                        $commentToken->data .= '-';
                        $this->state->tokenizerState = TokenizerState::COMMENT;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#comment-end-state
                case TokenizerState::COMMENT_END:
                    $c = $this->input->get();

                    if ($c === '>') {
                        // Switch to the data state. Emit the comment token.
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $commentToken;
                    } elseif ($c === '!') {
                        // Switch to the comment end bang state.
                        $this->state->tokenizerState = TokenizerState::COMMENT_END_BANG;
                    } elseif ($c === '-') {
                        // Append a U+002D HYPHEN-MINUS character (-) to the
                        // comment token's data.
                        $commentToken->data .= '-';
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit the comment token. Emit an end-of-file token.
                        yield $commentToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Parse error.
                        // Append two U+002D HYPHEN-MINUS characters (-) to the
                        // comment token's data. Reconsume in the comment state.
                        $commentToken->data .= '--';
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::COMMENT;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#comment-end-bang-state
                case TokenizerState::COMMENT_END_BANG:
                    $c = $this->input->get();

                    if ($c === '-') {
                        // Append two U+002D HYPHEN-MINUS characters (-) and a
                        // U+0021 EXCLAMATION MARK character (!) to the comment
                        // token's data. Switch to the comment end dash state.
                        $commentToken->data .= '--!';
                        $this->state->tokenizerState = TokenizerState::COMMENT_END_DASH;
                    } elseif ($c === '>') {
                        // Parse error.
                        // Switch to the data state. Emit the comment token.
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $commentToken;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit the comment token. Emit an end-of-file token.
                        yield $commentToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Append two U+002D HYPHEN-MINUS characters (-) and a U+0021 EXCLAMATION
                        // MARK character (!) to the comment token's data. Reconsume in the comment
                        // state.
                        $commentToken->data .= '--!';
                        $this->state->tokenizerState = TokenizerState::COMMENT;
                        $this->input->seek(-1);
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#doctype-state
                case TokenizerState::DOCTYPE:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Switch to the before DOCTYPE name state.
                        $this->state->tokenizerState = TokenizerState::BEFORE_DOCTYPE_NAME;
                    } elseif ($c === '>') {
                        // Reconsume in the before DOCTYPE name state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::BEFORE_DOCTYPE_NAME;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Create a new DOCTYPE token. Set its force-quirks flag
                        // to on. Emit the token. Emit an end-of-file token.
                        $doctypeToken = new DoctypeToken();
                        $doctypeToken->setQuirksMode('on');

                        yield $doctypeToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Parse error.
                        // Reconsume in the before DOCTYPE name state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::BEFORE_DOCTYPE_NAME;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#before-doctype-name-state
                case TokenizerState::BEFORE_DOCTYPE_NAME:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Ignore the character.
                    } elseif (ctype_upper($c)) {
                        // Create a new DOCTYPE token. Set the token's name to
                        // the lowercase version of the current input character
                        // (add 0x0020 to the character's code point). Switch to
                        // the DOCTYPE name state.
                        $doctypeToken = new DoctypeToken();
                        $doctypeToken->name = strtolower($c);
                        $this->state->tokenizerState = TokenizerState::DOCTYPE_NAME;
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Create a new DOCTYPE token. Set the token's name to a
                        // U+FFFD REPLACEMENT CHARACTER character. Switch to the
                        // DOCTYPE name state.
                        $doctypeToken = new DoctypeToken();
                        $doctypeToken->name = "\u{FFFD}";
                        $this->state->tokenizerState = TokenizerState::DOCTYPE_NAME;
                    } elseif ($c === '>') {
                        // Parse error.
                        // Create a new DOCTYPE token. Set its force-quirks flag
                        // to on. Switch to the data state. Emit the token.
                        $doctypeToken = new DoctypeToken();
                        $doctypeToken->setQuirksMode('on');
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $doctypeToken;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Create a new DOCTYPE token. Set its force-quirks flag
                        // to on. Emit the token. Emit an end-of-file token.
                        $doctypeToken = new DoctypeToken();
                        $doctypeToken->setQuirksMode('on');

                        yield $doctypeToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Create a new DOCTYPE token. Set the token's name to
                        // the current input character. Switch to the DOCTYPE
                        // name state.
                        $doctypeToken = new DoctypeToken();
                        $doctypeToken->name = $c;
                        $this->state->tokenizerState = TokenizerState::DOCTYPE_NAME;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#doctype-name-state
                case TokenizerState::DOCTYPE_NAME:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Switch to the after DOCTYPE name state.
                        $this->state->tokenizerState = TokenizerState::AFTER_DOCTYPE_NAME;
                    } elseif ($c === '>') {
                        // Switch to the data state. Emit the current DOCTYPE
                        // token.
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $doctypeToken;
                    } elseif (ctype_upper($c)) {
                        // Append the lowercase version of the current input
                        // character (add 0x0020 to the character's code point)
                        // to the current DOCTYPE token's name.
                        $doctypeToken->name .= strtolower($c);
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Append a U+FFFD REPLACEMENT CHARACTER character to
                        // the current DOCTYPE token's name.
                        $doctypeToken->name .= "\u{FFFD}";
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on. Emit
                        // that DOCTYPE token. Emit an end-of-file token.
                        $doctypeToken->setQuirksMode('on');

                        yield $doctypeToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Append the current input character to the current
                        // DOCTYPE token's name.
                        $doctypeToken->name .= $c;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#after-doctype-name-state
                case TokenizerState::AFTER_DOCTYPE_NAME:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Ignore the character.
                    } elseif ($c === '>') {
                        // Switch to the data state. Emit the current DOCTYPE
                        // token.
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $doctypeToken;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on. Emit
                        // that DOCTYPE token. Emit an end-of-file token.
                        $doctypeToken->setQuirksMode('on');

                        yield $doctypeToken;
                        yield new EOFToken();

                        return;
                    } else {
                        $chars = Utils::toASCIILowercase(
                            $c . $this->input->peek(5)
                        );

                        // If the six characters starting from the current input
                        // character are an ASCII case-insensitive match for the
                        // word "PUBLIC", then consume those characters and
                        // switch to the after DOCTYPE public keyword state.
                        if ($chars === Utils::toASCIILowercase('PUBLIC')) {
                            $this->input->get(5);
                            $this->state->tokenizerState = TokenizerState::AFTER_DOCTYPE_PUBLIC_KEYWORD;

                            // Otherwise, if the six characters starting from
                            // the current input character are an ASCII
                            // case-insensitive match for the word "SYSTEM",
                            // then consume those characters and switch to the
                            // after DOCTYPE system keyword state.
                        } elseif ($chars === Utils::toASCIILowercase('SYSTEM')) {
                            $this->input->get(5);
                            $this->state->tokenizerState = TokenizerState::AFTER_DOCTYPE_SYSTEM_KEYWORD;

                            // Otherwise, this is a parse error. Set the DOCTYPE
                            // token's force-quirks flag to on. Reconsume in the
                            // bogus DOCTYPE state.
                        } else {
                            $doctypeToken->setQuirksMode('on');
                            $this->input->seek(-1);
                            $this->state->tokenizerState = TokenizerState::BOGUS_DOCTYPE;
                        }
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#after-doctype-public-keyword-state
                case TokenizerState::AFTER_DOCTYPE_PUBLIC_KEYWORD:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Switch to the before DOCTYPE public identifier state.
                        $this->state->tokenizerState = TokenizerState::BEFORE_DOCTYPE_PUBLIC_IDENTIFIER;
                    } elseif ($c === '"') {
                        // Parse error.
                        // Set the DOCTYPE token's public identifier to the
                        // empty string (not missing), then switch to the
                        // DOCTYPE public identifier (double-quoted) state.
                        $doctypeToken->publicIdentifier = '';
                        $this->state->tokenizerState = TokenizerState::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED;
                    } elseif ($c === '\'') {
                        // Parse error.
                        // Set the DOCTYPE token's public identifier to the
                        // empty string (not missing), then switch to the
                        // DOCTYPE public identifier (single-quoted) state.
                        $doctypeToken->publicIdentifier = '';
                        $this->state->tokenizerState = TokenizerState::DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED;
                    } elseif ($c === '>') {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on.
                        // Switch to the data state. Emit that DOCTYPE token.
                        $doctypeToken->setQuirksMode('on');
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $doctypeToken;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on. Emit
                        // that DOCTYPE token. Emit an end-of-file token.
                        $doctypeToken->setQuirksMode('on');

                        yield $doctypeToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on.
                        // Reconsume in the bogus DOCTYPE state.
                        $doctypeToken->setQuirksMode('on');
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::BOGUS_DOCTYPE;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#before-doctype-public-identifier-state
                case TokenizerState::BEFORE_DOCTYPE_PUBLIC_IDENTIFIER:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Ignore the character.
                    } elseif ($c === '"') {
                        // Set the DOCTYPE token's public identifier to the
                        // empty string (not missing), then switch to the
                        // DOCTYPE public identifier (double-quoted) state.
                        $doctypeToken->publicIdentifier = '';
                        $this->state->tokenizerState = TokenizerState::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED;
                    } elseif ($c === '\'') {
                        // Set the DOCTYPE token's public identifier to the
                        // empty string (not missing), then switch to the
                        // DOCTYPE public identifier (single-quoted) state.
                        $doctypeToken->publicIdentifier = '';
                        $this->state->tokenizerState = TokenizerState::DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED;
                    } elseif ($c === '>') {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on.
                        // Switch to the data state. Emit that DOCTYPE token.
                        $doctypeToken->setQuirksMode('on');
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $doctypeToken;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on. Emit
                        // that DOCTYPE token. Emit an end-of-file token.
                        $doctypeToken->setQuirksMode('on');

                        yield $doctypeToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on.
                        // Reconsume in the bogus DOCTYPE state.
                        $doctypeToken->setQuirksMode('on');
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::BOGUS_DOCTYPE;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#doctype-public-identifier-(double-quoted)-state
                case TokenizerState::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED:
                    $c = $this->input->get();

                    if ($c === '"') {
                        // Switch to the after DOCTYPE public identifier state.
                        $this->state->tokenizerState = TokenizerState::AFTER_DOCTYPE_PUBLIC_IDENTIFIER;
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Append a U+FFFD REPLACEMENT CHARACTER character to
                        // the current DOCTYPE token's public identifier.
                        $doctypeToken->publicIdentifier .= "\u{FFFD}";
                    } elseif ($c === '>') {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on.
                        // Switch to the data state. Emit that DOCTYPE token.
                        $doctypeToken->setQuirksMode('on');
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $doctypeToken;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on. Emit
                        // that DOCTYPE token. Emit an end-of-file token.
                        $doctypeToken->setQuirksMode('on');

                        yield $doctypeToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Append the current input character to the current
                        // DOCTYPE token's public identifier.
                        $doctypeToken->publicIdentifier .= $c;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#doctype-public-identifier-(single-quoted)-state
                case TokenizerState::DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED:
                    $c = $this->input->get();

                    if ($c === '\'') {
                        // Switch to the after DOCTYPE public identifier state.
                        $this->state->tokenizerState =
                            TokenizerState::AFTER_DOCTYPE_PUBLIC_IDENTIFIER;
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Append a U+FFFD REPLACEMENT CHARACTER character to
                        // the current DOCTYPE token's public identifier.
                        $doctypeToken->publicIdentifier .= "\u{FFFD}";
                    } elseif ($c === '>') {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on.
                        // Switch to the data state. Emit that DOCTYPE token.
                        $doctypeToken->setQuirksMode('on');
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $doctypeToken;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on. Emit
                        // that DOCTYPE token. Emit an end-of-file token.
                        $doctypeToken->setQuirksMode('on');

                        yield $doctypeToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Append the current input character to the current
                        // DOCTYPE token's public identifier.
                        $doctypeToken->publicIdentifier .= $c;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#after-doctype-public-identifier-state
                case TokenizerState::AFTER_DOCTYPE_PUBLIC_IDENTIFIER:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Switch to the between DOCTYPE public and system
                        // identifiers state.
                        $this->state->tokenizerState = TokenizerState::BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS;
                    } elseif ($c === '>') {
                        // Switch to the data state. Emit the current DOCTYPE
                        // token.
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $doctypeToken;
                    } elseif ($c === '"') {
                        // Parse error.
                        // Set the DOCTYPE token's system identifier to the
                        // empty string (not missing), then switch to the
                        // DOCTYPE system identifier (double-quoted) state
                        $doctypeToken->systemIdentifier = '';
                        $this->state->tokenizerState = TokenizerState::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED;
                    } elseif ($c === '\'') {
                        // Parse error.
                        // Set the DOCTYPE token's system identifier to the
                        // empty string (not missing), then switch to the
                        // DOCTYPE system identifier (single-quoted) state.
                        $doctypeToken->systemIdentifier = '';
                        $this->state->tokenizerState = TokenizerState::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on. Emit
                        // that DOCTYPE token. Emit an end-of-file token.
                        $doctypeToken->setQuirksMode('on');

                        yield $doctypeToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on.
                        // Reconsume in the bogus DOCTYPE state.
                        $doctypeToken->setQuirksMode('on');
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::BOGUS_DOCTYPE;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#between-doctype-public-and-system-identifiers-state
                case TokenizerState::BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Ignore the character.
                    } elseif ($c === '>') {
                        // Switch to the data state. Emit the current DOCTYPE
                        // token.
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $doctypeToken;
                    } elseif ($c === '"') {
                        // Set the DOCTYPE token's system identifier to the
                        // empty string (not missing), then switch to the
                        // DOCTYPE system identifier (double-quoted) state.
                        $doctypeToken->systemIdentifier = '';
                        $this->state->tokenizerState = TokenizerState::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED;
                    } elseif ($c === '\'') {
                        // Set the DOCTYPE token's system identifier to the
                        // empty string (not missing), then switch to the
                        // DOCTYPE system identifier (single-quoted) state.
                        $doctypeToken->systemIdentifier = '';
                        $this->state->tokenizerState = TokenizerState::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on. Emit
                        // that DOCTYPE token. Emit an end-of-file token.
                        $doctypeToken->setQuirksMode('on');

                        yield $doctypeToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on.
                        // Reconsume in the bogus DOCTYPE state.
                        $doctypeToken->setQuirksMode('on');
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::BOGUS_DOCTYPE;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#after-doctype-system-keyword-state
                case TokenizerState::AFTER_DOCTYPE_SYSTEM_KEYWORD:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Switch to the before DOCTYPE system identifier state.
                        $this->state->tokenizerState = TokenizerState::BEFORE_DOCTYPE_SYSTEM_IDENTIFIER;
                    } elseif ($c === '"') {
                        // Parse error.
                        // Set the DOCTYPE token's system identifier to the
                        // empty string (not missing), then switch to the
                        // DOCTYPE system identifier (double-quoted) state.
                        $doctypeToken->systemIdentifier = '';
                        $this->state->tokenizerState = TokenizerState::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED;
                    } elseif ($c === '\'') {
                        // Parse error.
                        // Set the DOCTYPE token's system identifier to the
                        // empty string (not missing), then switch to the
                        // DOCTYPE system identifier (single-quoted) state.
                        $doctypeToken->systemIdentifier = '';
                        $this->state->tokenizerState = TokenizerState::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED;
                    } elseif ($c === '>') {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on.
                        // Switch to the data state. Emit that DOCTYPE token.
                        $doctypeToken->setQuirksMode('on');
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $doctypeToken;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on. Emit
                        // that DOCTYPE token. Emit an end-of-file token.
                        $doctypeToken->setQuirksMode('on');

                        yield $doctypeToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on.
                        // Reconsume in the bogus DOCTYPE state.
                        $doctypeToken->setQuirksMode('on');
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::BOGUS_DOCTYPE;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#before-doctype-system-identifier-state
                case TokenizerState::BEFORE_DOCTYPE_SYSTEM_IDENTIFIER:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Ignore the character.
                    } elseif ($c === '"') {
                        // Set the DOCTYPE token's system identifier to the
                        // empty string (not missing), then switch to the
                        // DOCTYPE system identifier (double-quoted) state.
                        $doctypeToken->systemIdentifier = '';
                        $this->state->tokenizerState = TokenizerState::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED;
                    } elseif ($c === '\'') {
                        // Set the DOCTYPE token's system identifier to the
                        // empty string (not missing), then switch to the
                        // DOCTYPE system identifier (single-quoted) state.
                        $doctypeToken->systemIdentifier = '';
                        $this->state->tokenizerState = TokenizerState::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED;
                    } elseif ($c === '>') {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on.
                        // Switch to the data state. Emit that DOCTYPE token.
                        $doctypeToken->setQuirksMode('on');
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $doctypeToken;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on. Emit
                        // that DOCTYPE token. Emit an end-of-file token.
                        $doctypeToken->setQuirksMode('on');

                        yield $doctypeToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Parse error. Set the DOCTYPE token's force-quirks
                        // flag to on. Reconsume in the bogus DOCTYPE state.
                        $doctypeToken->setQuirksMode('on');
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::BOGUS_DOCTYPE;
                    }

                    break;

                case TokenizerState::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED:
                    $c = $this->input->get();

                    if ($c === '"') {
                        // Switch to the after DOCTYPE system identifier state.
                        $this->state->tokenizerState = TokenizerState::AFTER_DOCTYPE_SYSTEM_IDENTIFIER;
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Append a U+FFFD REPLACEMENT CHARACTER character to
                        // the current DOCTYPE token's system identifier.
                        $doctypeToken->systemIdentifier .= "\u{FFFD}";
                    } elseif ($c === '>') {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on.
                        // Switch to the data state. Emit that DOCTYPE token.
                        $doctypeToken->setQuirksMode('on');
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $doctypeToken;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on. Emit
                        // that DOCTYPE token. Emit an end-of-file token.
                        $doctypeToken->setQuirksMode('on');

                        yield $doctypeToken;
                        yield new EOFToken();
                    } else {
                        // Append the current input character to the current
                        // DOCTYPE token's system identifier.
                        $doctypeToken->systemIdentifier .= $c;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#doctype-system-identifier-(single-quoted)-state
                case TokenizerState::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED:
                    $c = $this->input->get();

                    if ($c === '\'') {
                        // Switch to the after DOCTYPE system identifier state.
                        $this->state->tokenizerState = TokenizerState::AFTER_DOCTYPE_SYSTEM_IDENTIFIER;
                    } elseif ($c === "\0") {
                        // Parse error.
                        // Append a U+FFFD REPLACEMENT CHARACTER character to
                        // the current DOCTYPE token's system identifier.
                        $doctypeToken->systemIdentifier .= "\u{FFFD}";
                    } elseif ($c === '>') {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on.
                        // Switch to the data state. Emit that DOCTYPE token.
                        $doctypeToken->setQuirksMode('on');
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $doctypeToken;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on. Emit
                        // that DOCTYPE token. Emit an end-of-file token.
                        $doctypeToken->setQuirksMode('on');

                        yield $doctypeToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Append the current input character to the current
                        // DOCTYPE token's system identifier.
                        $doctypeToken->systemIdentifier .= $c;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#after-doctype-system-identifier-state
                case TokenizerState::AFTER_DOCTYPE_SYSTEM_IDENTIFIER:
                    $c = $this->input->get();

                    if ($c === "\x09" || $c === "\x0A" || $c === "\x0C" || $c === "\x20") {
                        // Ignore the character.
                    } elseif ($c === '>') {
                        // Switch to the data state. Emit the current DOCTYPE
                        // token.
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $doctypeToken;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Set the DOCTYPE token's force-quirks flag to on. Emit
                        // that DOCTYPE token. Emit an end-of-file token.
                        $doctypeToken->setQuirksMode('on');

                        yield $doctypeToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Parse error.
                        // Reconsume in the bogus DOCTYPE state. (This does not
                        // set the DOCTYPE token's force-quirks flag to on.)
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::BOGUS_DOCTYPE;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#bogus-doctype-state
                case TokenizerState::BOGUS_DOCTYPE:
                    $c = $this->input->get();

                    if ($c === '>') {
                        // Switch to the data state. Emit the DOCTYPE token.
                        $this->state->tokenizerState = TokenizerState::DATA;

                        yield $doctypeToken;
                    } elseif ($c === "\0") {
                        // This is an unexpected-null-character parse error.
                        // Ignore the character.
                    } elseif ($this->input->isEoS()) {
                        // Emit the DOCTYPE token. Emit an end-of-file token.
                        yield $doctypeToken;
                        yield new EOFToken();

                        return;
                    } else {
                        // Ignore the character.
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#cdata-section-state
                case TokenizerState::CDATA_SECTION:
                    $c = $this->input->get();

                    if ($c === ']') {
                        // Switch to the CDATA section bracket state.
                        $this->state->tokenizerState = TokenizerState::CDATA_SECTION_BRACKET;
                    } elseif ($this->input->isEoS()) {
                        // Parse error.
                        // Emit an end-of-file token.
                        yield new EOFToken();

                        return;
                    } else {
                        // Emit the current input character as a character token.
                        // NOTE: U+0000 NULL characters are handled in the tree
                        // construction stage, as part of the in foreign content
                        // insertion mode, which is the only place where CDATA
                        // sections can appear.
                        yield new CharacterToken($c);
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#cdata-section-bracket-state
                case TokenizerState::CDATA_SECTION_BRACKET:
                    $c = $this->input->get();

                    if ($c === ']') {
                        // Switch to the CDATA section end state.
                        $this->state->tokenizerState =
                            TokenizerState::CDATA_SECTION_END;
                    } else {
                        // Emit a U+005D RIGHT SQUARE BRACKET character token.
                        // Reconsume in the CDATA section state.
                        yield new CharacterToken(']');

                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::CDATA_SECTION;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#cdata-section-end-state
                case TokenizerState::CDATA_SECTION_END:
                    $c = $this->input->get();

                    if ($c === ']') {
                        // Emit a U+005D RIGHT SQUARE BRACKET character token.
                        yield new CharacterToken(']');
                    } elseif ($c === '>') {
                        // Switch to the data state.
                        $this->state->tokenizerState = TokenizerState::DATA;
                    } else {
                        // Emit two U+005D RIGHT SQUARE BRACKET character
                        // tokens. Reconsume in the CDATA section state.
                        yield new CharacterToken(']');
                        yield new CharacterToken(']');

                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::CDATA_SECTION;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#character-reference-state
                case TokenizerState::CHARACTER_REFERENCE:
                    $buffer = '&';
                    $c = $this->input->get();

                    if (ctype_alnum($c)) {
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::NAMED_CHARACTER_REFERENCE;
                    } elseif ($c === '#') {
                        $buffer .= $c;
                        $this->state->tokenizerState = TokenizerState::NUMERIC_CHARACTER_REFERENCE;
                    } else {
                        yield from $this->flush($buffer, $attributeToken, $returnState);

                        $this->input->seek(-1);
                        $this->state->tokenizerState = $returnState;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#named-character-reference-state
                case TokenizerState::NAMED_CHARACTER_REFERENCE:
                    $char = '';
                    $matches = [];

                    // Load the JSON file for the named character references
                    // table on demand.
                    if (!self::$namedCharacterReferences) {
                        // TODO: Handle json_decode() and file_get_contents()
                        // failing. Maybe html_entity_decode() or
                        // get_html_translation_table() can be used as a
                        // fallback.
                        self::$namedCharacterReferences = json_decode(
                            file_get_contents(self::NAMED_CHAR_REFERENCES_PATH),
                            true
                        );
                    }

                    // Consume the maximum number of characters possible, up to
                    // MAX_NAMED_CHAR_REFERENCE_LENGTH, where the consumed characters are one of the
                    // identifiers in the first column of the named character references table.
                    // Append each character to the temporary buffer when it's consumed.
                    for ($i = 0; $i < self::MAX_NAMED_CHAR_REFERENCE_LENGTH; $i++) {
                        $char = $this->input->get();

                        // We've reached the end of the input stream. Break out
                        // of the loop without a match.
                        if ($char === '') {
                            break;
                        }

                        // Append the character from the input stream to the
                        // temporary buffer.
                        $buffer .= $char;

                        $conformingMatch = isset(self::$namedCharacterReferences[$buffer . ';']);
                        $nonConformingMatch = isset(self::$namedCharacterReferences[$buffer]);

                        if ($nonConformingMatch || $conformingMatch) {
                            if ($conformingMatch && $this->input->peek() === ';') {
                                // we found a full match
                                $char = $this->input->get();
                                $buffer .= $char;
                                $matches[] = [$buffer, $i];

                                break;
                            }

                            // we found a potential match as it is possible to match a shorter
                            // non-semi-colon terminated entity before a longer one.
                            if ($nonConformingMatch) {
                                $matches[] = [$buffer, $i];
                            }

                            // dont't break as there could be a longer entity to match

                            // If we find a semi-colon that wasn't consumed as a full match above
                            // or we encounter a non-alphanumeric character then, that indicates
                            // that we should stop consuming characters for that entity.
                        } elseif ($char === ';' || !ctype_alnum($char)) {
                            if ($matches !== []) {
                                // partial match
                            }

                            break;
                        }
                    }

                    if ($matches !== []) {
                        // rewind the input stream by the difference between the number of
                        // characters consumed and the number of characters consumed by the last
                        // match.
                        $this->input->seek(end($matches)[1] - $i);
                        $buffer = array_pop($matches)[0];

                        // If the character reference was consumed as part of an
                        // attribute, and the last character is not a semi-colon
                        // (;), and the next character in the input stream is
                        // either an equals sign (=) or an ASCII alphanumeric,
                        // then, for historical reasons, switch to the character
                        // reference end state.
                        switch ($returnState) {
                            case TokenizerState::ATTRIBUTE_VALUE_DOUBLE_QUOTED:
                            case TokenizerState::ATTRIBUTE_VALUE_SINGLE_QUOTED:
                            case TokenizerState::ATTRIBUTE_VALUE_UNQUOTED:
                                if (
                                    $char !== ';'
                                    && preg_match('/^[=A-Za-z0-9]$/', $this->input->peek())
                                ) {
                                    yield from $this->flush($buffer, $attributeToken, $returnState);

                                    $this->state->tokenizerState = $returnState;

                                    // Leave the named character reference
                                    // state.
                                    break 2;
                                }
                        }

                        // If the last character consumed in a match is not a
                        // semi-colon (;), then this is a parse error.
                        if ($char !== ';') {
                            // Parse error.
                        }

                        $namedRef = self::$namedCharacterReferences[$buffer];

                        // Set the temporary buffer to the empty string.
                        $buffer = '';

                        // Append each character, from the named character
                        // references table, corresponding to the character
                        // reference name to the temporary buffer.
                        $buffer .= $namedRef['characters'];

                        yield from $this->flush($buffer, $attributeToken, $returnState);

                        $this->state->tokenizerState = $returnState;

                        // If no match was found, but the buffer contains an
                        // ampersand (&) followed by one or more ASCII
                        // alphanumerics, then this is a parse error.
                    } else {
                        yield from $this->flush($buffer, $attributeToken, $returnState);

                        $this->state->tokenizerState = TokenizerState::AMBIGUOUS_AMPERSAND;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/parsing.html#ambiguous-ampersand-state
                case TokenizerState::AMBIGUOUS_AMPERSAND:
                    $c = $this->input->get();

                    if (ctype_alnum($c)) {
                        switch ($returnState) {
                            case TokenizerState::ATTRIBUTE_VALUE_DOUBLE_QUOTED:
                            case TokenizerState::ATTRIBUTE_VALUE_SINGLE_QUOTED:
                            case TokenizerState::ATTRIBUTE_VALUE_UNQUOTED:
                                $attributeToken->value .= $c;

                                break;

                            default:
                                yield new CharacterToken($c);
                        }
                    } elseif ($c === ';') {
                        // Reconsume in the return state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = $returnState;
                    } else {
                        // Reconsume in the return state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = $returnState;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#numeric-character-reference-state
                case TokenizerState::NUMERIC_CHARACTER_REFERENCE:
                    $characterReferenceCode = 0;
                    $c = $this->input->get();

                    if ($c === 'x' || $c === 'X') {
                        // Append the current input character to the temporary
                        // buffer. Switch to the hexademical character reference
                        // start state.
                        $buffer .= $c;
                        $this->state->tokenizerState = TokenizerState::HEXADECIMAL_CHARACTER_REFERENCE_START;
                    } else {
                        // Reconsume in the decimal character reference start
                        // state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::DECIMAL_CHARACTER_REFERENCE_START;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#hexademical-character-reference-start-state
                case TokenizerState::HEXADECIMAL_CHARACTER_REFERENCE_START:
                    $c = $this->input->get();

                    if (ctype_xdigit($c)) {
                        // Reconsume in the hexademical character reference
                        // state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::HEXADECIMAL_CHARACTER_REFERENCE;
                    } else {
                        // Parse error.
                        // Reconsume in the character reference end state.
                        yield from $this->flush($buffer, $attributeToken, $returnState);

                        $this->input->seek(-1);
                        $this->state->tokenizerState = $returnState;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#decimal-character-reference-start-state
                case TokenizerState::DECIMAL_CHARACTER_REFERENCE_START:
                    $c = $this->input->get();

                    if (ctype_digit($c)) {
                        // Reconsume in the decimal character reference state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::DECIMAL_CHARACTER_REFERENCE;
                    } else {
                        // Parse error.
                        // Reconsume in the character reference end state.
                        yield from $this->flush($buffer, $attributeToken, $returnState);

                        $this->input->seek(-1);
                        $this->state->tokenizerState = $returnState;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#hexademical-character-reference-state
                case TokenizerState::HEXADECIMAL_CHARACTER_REFERENCE:
                    $c = $this->input->get();

                    if (ctype_digit($c)) {
                        // Multiply the character reference code by 16.
                        // Add a numeric version of the current input character
                        // (subtract 0x0030 from the character's code point) to
                        // the character reference code.
                        $characterReferenceCode *= 16;
                        $characterReferenceCode += ord($c) - 0x0030;
                    } elseif (ctype_upper($c)) {
                        // Multiply the character reference code by 16. Add a
                        // numeric version of the current input character as a
                        // hexademical digit (subtract 0x0037 from the
                        // character's code point) to the character reference
                        // code.
                        $characterReferenceCode *= 16;
                        $characterReferenceCode += ord($c) - 0x0037;
                    } elseif (ctype_lower($c)) {
                        // Multiply the character reference code by 16. Add a
                        // numeric version of the current input character as a
                        // hexademical digit (subtract 0x0057 from the
                        // character's code point) to the character reference
                        // code.
                        $characterReferenceCode *= 16;
                        $characterReferenceCode += ord($c) - 0x0057;
                    } elseif ($c === ';') {
                        // Switch to the numeric character reference end state.
                        $this->state->tokenizerState = TokenizerState::NUMERIC_CHARACTER_REFERENCE_END;
                    } else {
                        // Parse error.
                        // Reconsume in the numeric character reference end
                        // state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::NUMERIC_CHARACTER_REFERENCE_END;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#decimal-character-reference-state
                case TokenizerState::DECIMAL_CHARACTER_REFERENCE:
                    $c = $this->input->get();

                    if (ctype_digit($c)) {
                        // Multiply the character reference code by 10. Add a
                        // numeric version of the current input character
                        // (subtract 0x0030 from the character's code point) to
                        // the character reference code.
                        $characterReferenceCode *= 10;
                        $characterReferenceCode += (int) $c;
                    } elseif ($c === ';') {
                        // Switch to the numeric character reference end state.
                        $this->state->tokenizerState = TokenizerState::NUMERIC_CHARACTER_REFERENCE_END;
                    } else {
                        // Parse error.
                        // Reconsume in the numeric character reference end
                        // state.
                        $this->input->seek(-1);
                        $this->state->tokenizerState = TokenizerState::NUMERIC_CHARACTER_REFERENCE_END;
                    }

                    break;

                // https://html.spec.whatwg.org/multipage/syntax.html#numeric-character-reference-end-state
                case TokenizerState::NUMERIC_CHARACTER_REFERENCE_END:
                    if ($characterReferenceCode === 0x00) {
                        // This is a null-character-reference parse error. Set
                        // the character reference code to 0xFFFD
                        $characterReferenceCode = 0xFFFD;
                    } elseif ($characterReferenceCode > 0x10FFFF) {
                        // This is a character-reference-outside-unicode-range
                        // parse error. Set the character reference code to
                        // 0xFFFD.
                        $characterReferenceCode = 0xFFFD;

                        // If the number is a surrogate...
                    } elseif (
                        $characterReferenceCode >= 0xD800
                        && $characterReferenceCode <= 0xDFFF
                    ) {
                        // This is a surrogate-character-reference parse error.
                        // Set the character reference code to 0xFFFD.
                        $characterReferenceCode = 0xFFFD;

                        // If  the number is a noncharacter...
                    } elseif (
                        ($characterReferenceCode >= 0xFDD0 && $characterReferenceCode <= 0xFDEF)
                        || ($characterReferenceCode === 0xFFFE
                            || $characterReferenceCode === 0xFFFF
                            || $characterReferenceCode === 0x1FFFE
                            || $characterReferenceCode === 0x1FFFF
                            || $characterReferenceCode === 0x2FFFE
                            || $characterReferenceCode === 0x2FFFF
                            || $characterReferenceCode === 0x3FFFE
                            || $characterReferenceCode === 0x3FFFF
                            || $characterReferenceCode === 0x4FFFE
                            || $characterReferenceCode === 0x4FFFF
                            || $characterReferenceCode === 0x5FFFE
                            || $characterReferenceCode === 0x5FFFF
                            || $characterReferenceCode === 0x6FFFE
                            || $characterReferenceCode === 0x6FFFF
                            || $characterReferenceCode === 0x7FFFE
                            || $characterReferenceCode === 0x7FFFF
                            || $characterReferenceCode === 0x8FFFE
                            || $characterReferenceCode === 0x8FFFF
                            || $characterReferenceCode === 0x9FFFE
                            || $characterReferenceCode === 0x9FFFF
                            || $characterReferenceCode === 0xAFFFE
                            || $characterReferenceCode === 0xAFFFF
                            || $characterReferenceCode === 0xBFFFE
                            || $characterReferenceCode === 0xBFFFF
                            || $characterReferenceCode === 0xCFFFE
                            || $characterReferenceCode === 0xCFFFF
                            || $characterReferenceCode === 0xDFFFE
                            || $characterReferenceCode === 0xDFFFF
                            || $characterReferenceCode === 0xEFFFE
                            || $characterReferenceCode === 0xEFFFF
                            || $characterReferenceCode === 0xFFFFE
                            || $characterReferenceCode === 0xFFFFF
                            || $characterReferenceCode === 0x10FFFE
                            || $characterReferenceCode === 0x10FFFF)
                    ) {
                        // This is a noncharacter-character-reference parse
                        // error.

                        // If the number is 0x0D, or a control that's not ASCII
                        // whitespace...
                    } elseif (
                        $characterReferenceCode === 0x0D
                        || ((($characterReferenceCode >= 0x00 && $characterReferenceCode <= 0x1F)
                            || ($characterReferenceCode >= 0x7F && $characterReferenceCode <= 0x9F))
                        && $characterReferenceCode !== 0x09
                        && $characterReferenceCode !== 0x0A
                        && $characterReferenceCode !== 0x0C
                        && $characterReferenceCode !== 0x0D
                        && $characterReferenceCode !== 0x20)
                    ) {
                        // This is a control-character-reference parse error.

                        // If the number is one of the numbers in the first
                        // column of the character reference map, then find the
                        // row with that number in the first column, and set the
                        // character reference code to the number in the second
                        // column of that row.
                        if (isset(self::CHARACTER_REFERENCE_MAP[$characterReferenceCode])) {
                            $characterReferenceCode = self::CHARACTER_REFERENCE_MAP[
                                $characterReferenceCode
                            ];
                        }
                    }

                    // Set the temporary buffer to the empty string. Append a
                    // code point equal to the character reference code to the
                    // temporary buffer. Flush code points consumed as a
                    // character reference. Switch to the return state.
                    $buffer = IntlChar::chr($characterReferenceCode);

                    yield from $this->flush($buffer, $attributeToken, $returnState);

                    $this->state->tokenizerState = $returnState;

                    break;
            }
        } while (true);
    }

    /**
     * Sets the last emitted start tag token.
     */
    public function setLastEmittedStartTagToken(StartTagToken $token): void
    {
        $this->lastEmittedStartTagToken = $token;
    }

    /**
     * An appropriate end tag token is an end tag token whose tag name matches the tag name of the last start tag token
     * to have been emitted from this tokenizer. It is possible that no start tag token has been emitted, yet, in which
     * case, no end tag token is appropriate.
     *
     * @see https://html.spec.whatwg.org/#appropriate-end-tag-token
     */
    private function isAppropriateEndTag(EndTagToken $token): bool
    {
        return $this->lastEmittedStartTagToken !== null
            && $this->lastEmittedStartTagToken->tagName === $token->tagName;
    }

    /**
     * If the return state is either attribute value (double-quoted) state, attribute value (single-quoted) state, or
     * attribute value (unquoted) state, then all the code points in the temporary buffer must be appended to the
     * current attribute's value. Otherwise, it must emit every code point in the temporary buffer as a character token.
     *
     * @see https://html.spec.whatwg.org/multipage/parsing.html#flush-code-points-consumed-as-a-character-reference
     *
     * @return iterable<int, \Rowbot\DOM\Parser\Token\CharacterToken>
     */
    private function flush(string $codepoints, ?AttributeToken $token, int $returnState): iterable
    {
        switch ($returnState) {
            case TokenizerState::ATTRIBUTE_VALUE_DOUBLE_QUOTED:
            case TokenizerState::ATTRIBUTE_VALUE_SINGLE_QUOTED:
            case TokenizerState::ATTRIBUTE_VALUE_UNQUOTED:
                // Append each character in the temporary buffer
                // (in the order they were added to the buffer) to
                // the current attribute's value.
                $token->value .= $codepoints;

                // Return an empty array so that the yield from statement has
                // something it can work with.
                return [];

            default:
                $bufferStream = new CodePointStream($codepoints);

                // For each of the characters in the temporary
                // buffer (in the order they were added to the
                // buffer), emit the character as a character token.
                while (!$bufferStream->isEoS()) {
                    yield new CharacterToken($bufferStream->get());
                }
        }
    }
}
