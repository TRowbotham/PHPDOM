<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Parser\Collection\Exception\EmptyStackException;
use Rowbot\DOM\Parser\Collection\OpenElementStack;
use Rowbot\DOM\Support\CodePointStream;

final class ParserContext
{
    public const CONFIDENCE_TENTATIVE  = 1;
    public const CONFIDENCE_CERTAIN    = 2;
    public const CONFIDENCE_IRRELEVANT = 4;

    /**
     * The context element for the parser when it is created in the fragment
     * case.
     *
     * @var \Rowbot\DOM\Element\Element|null
     */
    public $contextElement;

    /**
     * How confident the parser is about the detected text encoding. It can be
     * one of three values.
     *
     * * tentative
     * * certain
     * * irrelevant
     *
     * If no encoding is necessary, i.e. because the input stream is Unicode,
     * then the confidence is irrelevant.
     *
     * @var self::CONFIDENCE_*
     */
    public $encodingConfidence;

    /**
     * The last form element that was opened and has not yet been closed.
     *
     * @var \Rowbot\DOM\Element\HTML\HTMLFormElement|null
     */
    public $formElementPointer;

    /**
     * The parsed head element.
     *
     * @var \Rowbot\DOM\Element\HTML\HTMLHeadElement|null
     */
    public $headElementPointer;

    /**
     * @var \Rowbot\DOM\Support\CodePointStream
     */
    public $input;

    /**
     * Whether or not the HTML parser was created using the fragment parsing
     * algorithim. This can occur when things like Element::innerHTML are set.
     *
     * @var bool
     */
    public $isFragmentCase;

    /**
     * Whether or not the parser is currently paused.
     *
     * @var bool
     */
    public $isPaused;

    /**
     * Whether or not scripting is enabled for the document that the parser is
     * associated with.
     *
     * @var bool
     */
    public $isScriptingEnabled;

    /**
     * The stack of open elements.
     *
     * @var \Rowbot\DOM\Parser\Collection\OpenElementStack
     */
    public $openElements;

    /**
     * Stores the current state of the tokenizer.
     *
     * @var \Rowbot\DOM\Parser\HTML\TokenizerState::*
     */
    public $tokenizerState;

    public function __construct(
        ?Element $contextElement,
        OpenElementStack $openElementStack,
        CodePointStream $input,
        bool $isFragmentCase = false,
        bool $isScriptingEnabled = false,
        int $tokenizerState = null
    ) {
        $this->contextElement     = $contextElement;
        $this->encodingConfidence = self::CONFIDENCE_IRRELEVANT;
        $this->input              = $input;
        $this->isFragmentCase     = $isFragmentCase;
        $this->isPaused           = false;
        $this->isScriptingEnabled = $isScriptingEnabled;
        $this->openElements       = $openElementStack;
        $this->tokenizerState     = $tokenizerState ?? TokenizerState::DATA;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/parsing.html#adjusted-current-node
     */
    public function getAdjustedCurrentNode(): ?Element
    {
        if ($this->isFragmentCase && $this->openElements->count() === 1) {
            /** @var \Rowbot\DOM\Element\Element */
            return $this->contextElement;
        }

        try {
            return $this->openElements->bottom();
        } catch (EmptyStackException $e) {
            return null;
        }
    }
}
