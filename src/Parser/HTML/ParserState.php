<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

class ParserState
{
    public const CONFIDENCE_TENTATIVE  = 1;
    public const CONFIDENCE_CERTAIN    = 2;
    public const CONFIDENCE_IRRELEVANT = 4;

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
     * @var ?int
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
     * Stores the insertion mode used by the Treebuilder.
     *
     * @var int
     */
    public $insertionMode;

    /**
     * Whether or not the parser is currently paused.
     *
     * @var bool
     */
    public $isPaused;

    /**
     * Stores the current state of the tokenizer.
     *
     * @var int
     */
    public $tokenizerState;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->encodingConfidence = null;
        $this->insertionMode      = ParserInsertionMode::INITIAL;
        $this->isPaused           = false;
        $this->tokenizerState     = TokenizerState::DATA;
    }
}
