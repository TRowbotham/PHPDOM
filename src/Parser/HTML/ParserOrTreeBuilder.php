<?php
declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\DocumentReadyState;
use Rowbot\DOM\Element\HTML\HTMLBodyElement;
use Rowbot\DOM\Element\HTML\HTMLFrameSetElement;
use Rowbot\DOM\Element\HTML\HTMLHeadElement;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Element\HTML\HTMLTableCaptionElement;
use Rowbot\DOM\Element\HTML\HTMLTableCellElement;
use Rowbot\DOM\Element\HTML\HTMLTableColElement;
use Rowbot\DOM\Element\HTML\HTMLTableElement;
use Rowbot\DOM\Element\HTML\HTMLTableRowElement;
use Rowbot\DOM\Element\HTML\HTMLTableSectionElement;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Element\HTML\HTMLSelectElement;

trait ParserOrTreeBuilder
{
    use ParserCommon;

    /**
     * The stack of active formatting elements.
     *
     * @var \Rowbot\DOM\Parser\Collection\ActiveFormattingElementStack
     */
    private $activeFormattingElements;

    /**
     * The document the parser is associated with.
     *
     * @var \Rowbot\DOM\Document
     */
    private $document;

    /**
     * Whether or not scripting is enabled for the document that the parser is
     * associated with.
     *
     * @var bool
     */
    private $isScriptingEnabled;

    /**
     * The stack of template insertion modes.
     *
     * @var \SplStack<int>
     */
    private $templateInsertionModes;

    /**
     * A collection of nodes and the tokens that were used to create them.
     *
     * @var \SplObjectStorage<Node, Token>
     */
    private $tokenRepository;

    /**
     * @var \Rowbot\DOM\Parser\TextBuilder
     */
    private $textBuilder;

    /**
     * Resets the HTML Parser's insertion mode.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#reset-the-insertion-mode-appropriately
     *
     * @return void
     */
    public function resetInsertionMode(): void
    {
        $last = false;
        $iterator = $this->openElements->getIterator();

        foreach ($iterator as $node) {
            if ($this->openElements[0] === $node) {
                $last = true;

                if ($this->isFragmentCase) {
                    // Fragment case
                    $node = $this->contextElement;
                }
            }

            if ($node instanceof HTMLSelectElement) {
                if (!$last) {
                    $ancestor = $node;

                    while ($iterator->valid()) {
                        if ($ancestor === $this->openElements[0]) {
                            break;
                        }

                        $iterator->next();
                        $ancestor = $iterator->current();

                        if ($ancestor instanceof HTMLTemplateElement) {
                            break;
                        }

                        if ($ancestor instanceof HTMLTableElement) {
                            $this->state->insertionMode =
                                ParserInsertionMode::IN_SELECT_IN_TABLE;
                            break 2;
                        }
                    }
                }

                $this->state->insertionMode = ParserInsertionMode::IN_SELECT;
                break;
            } elseif ($node instanceof HTMLTableCellElement && !$last) {
                $this->state->insertionMode = ParserInsertionMode::IN_CELL;
                break;
            } elseif ($node instanceof HTMLTableRowElement) {
                $this->state->insertionMode = ParserInsertionMode::IN_ROW;
                break;
            } elseif ($node instanceof HTMLTableSectionElement) {
                $this->state->insertionMode = ParserInsertionMode::IN_TABLE_BODY;
                break;
            } elseif ($node instanceof HTMLTableCaptionElement) {
                $this->state->insertionMode = ParserInsertionMode::IN_CAPTION;
                break;
            } elseif ($node instanceof HTMLTableColElement &&
                $node->localName === 'colgroup'
            ) {
                $this->state->insertionMode = ParserInsertionMode::IN_COLUMN_GROUP;
                break;
            } elseif ($node instanceof HTMLTableElement) {
                $this->state->insertionMode = ParserInsertionMode::IN_TABLE;
                break;
            } elseif ($node instanceof HTMLTemplateElement) {
                $this->state->insertionMode = $this->templateInsertionModes->top();
                break;
            } elseif ($node instanceof HTMLHeadElement && !$last) {
                $this->state->insertionMode = ParserInsertionMode::IN_HEAD;
                break;
            } elseif ($node instanceof HTMLBodyElement) {
                $this->state->insertionMode = ParserInsertionMode::IN_BODY;
                break;
            } elseif ($node instanceof HTMLFrameSetElement) {
                // Fragment case
                $this->state->insertionMode = ParserInsertionMode::IN_FRAMESET;
                break;
            } elseif ($node instanceof HTMLHtmlElement) {
                if (!$this->state->headElementPointer) {
                    // Fragment case
                    $this->state->insertionMode = ParserInsertionMode::BEFORE_HEAD;
                } else {
                    $this->state->insertionMode = ParserInsertionMode::AFTER_HEAD;
                }

                break;
            } elseif ($last) {
                // Fragment case
                $this->state->insertionMode = ParserInsertionMode::IN_BODY;
            }
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#stop-parsing
     *
     * @return void
     */
    public function stopParsing(): void
    {
        // TODO: Set the current document readiness to "interactive" and the
        // insertion point to undefined.
        $this->document->setReadyState(DocumentReadyState::INTERACTIVE);

        // Pop all the nodes off the stack of open elements.
        $this->openElements->clear();

        // TODO: Lots of stuff
    }
}
