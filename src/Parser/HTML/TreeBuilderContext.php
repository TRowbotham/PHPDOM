<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\Document;
use Rowbot\DOM\Element\HTML\HTMLBodyElement;
use Rowbot\DOM\Element\HTML\HTMLFrameSetElement;
use Rowbot\DOM\Element\HTML\HTMLHeadElement;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Element\HTML\HTMLSelectElement;
use Rowbot\DOM\Element\HTML\HTMLTableCaptionElement;
use Rowbot\DOM\Element\HTML\HTMLTableCellElement;
use Rowbot\DOM\Element\HTML\HTMLTableColElement;
use Rowbot\DOM\Element\HTML\HTMLTableElement;
use Rowbot\DOM\Element\HTML\HTMLTableRowElement;
use Rowbot\DOM\Element\HTML\HTMLTableSectionElement;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Parser\Collection\ActiveFormattingElementStack;
use Rowbot\DOM\Parser\HTML\InsertionMode\AfterHeadInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\BeforeHeadInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InBodyInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InCaptionInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InCellInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InColumnGroupInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InFrameSetInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InHeadInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InitialInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InRowInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InSelectInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InSelectInTableInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InTableBodyInsertionMode;
use Rowbot\DOM\Parser\HTML\InsertionMode\InTableInsertionMode;
use SplObjectStorage;
use SplStack;

final class TreeBuilderContext
{
    /**
     * The stack of active formatting elements.
     *
     * @var \Rowbot\DOM\Parser\Collection\ActiveFormattingElementStack
     */
    public $activeFormattingElements;

    /**
     * The document the parser is associated with.
     *
     * @var \Rowbot\DOM\Document
     */
    public $document;

    /**
     * Stores the insertion mode used by the Treebuilder.
     *
     * @var \Rowbot\DOM\Parser\HTML\InsertionMode\InsertionMode
     */
    public $insertionMode;

    /**
     * The stack of template insertion modes.
     *
     * @var \SplStack<class-string<\Rowbot\DOM\Parser\HTML\InsertionMode\InsertionMode>>
     */
    public $templateInsertionModes;

    /**
     * A collection of nodes and the tokens that were used to create them.
     *
     * @var \SplObjectStorage<\Rowbot\DOM\Element\Element, \Rowbot\DOM\Parser\Token\TagToken>
     */
    public $elementTokenMap;

    /**
     * Whether or not foster-parenting mode is active.
     *
     * @var bool
     */
    public $fosterParenting;

    /**
     * @var string
     */
    public $framesetOk;

    /**
     * Stores the insertion mode that the TreeBuilder should return to after
     * it is done processing the current token in the current insertion mode.
     *
     * @var \Rowbot\DOM\Parser\HTML\InsertionMode\InsertionMode
     */
    public $originalInsertionMode;

    /**
     * @var \Rowbot\DOM\Parser\HTML\ParserContext
     */
    public $parser;

    /**
     * A list of character tokens pending insertion during table building.
     *
     * @var list<\Rowbot\DOM\Parser\Token\CharacterToken>
     */
    public $pendingTableCharacterTokens;

    /**
     * @param \SplObjectStorage<\Rowbot\DOM\Element\Element, \Rowbot\DOM\Parser\Token\TagToken> $elementTokenMap
     */
    public function __construct(
        Document $document,
        ParserContext $parser,
        ActiveFormattingElementStack $activeFormattingElementStack,
        SplObjectStorage $elementTokenMap
    ) {
        $this->document                    = $document;
        $this->parser                      = $parser;
        $this->activeFormattingElements    = $activeFormattingElementStack;
        $this->elementTokenMap             = $elementTokenMap;
        $this->insertionMode               = new InitialInsertionMode();
        $this->originalInsertionMode       = $this->insertionMode;
        $this->framesetOk                  = 'ok';
        $this->templateInsertionModes      = new SplStack();
        $this->pendingTableCharacterTokens = [];
        $this->fosterParenting             = false;
    }

    /**
     * Resets the HTML Parser's insertion mode.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#reset-the-insertion-mode-appropriately
     */
    public function resetInsertionMode(): void
    {
        $last = false;
        $iterator = $this->parser->openElements->getIterator();

        while ($iterator->valid()) {
            $node = $iterator->current();

            if ($this->parser->openElements->top() === $node) {
                $last = true;

                if ($this->parser->isFragmentCase) {
                    // Fragment case
                    $node = $this->parser->contextElement;
                }
            }

            if ($node instanceof HTMLSelectElement) {
                if (!$last) {
                    $ancestor = $node;

                    while ($iterator->valid()) {
                        if ($ancestor === $this->parser->openElements->top()) {
                            break;
                        }

                        $iterator->next();
                        $ancestor = $iterator->current();

                        if ($ancestor instanceof HTMLTemplateElement) {
                            break;
                        }

                        if ($ancestor instanceof HTMLTableElement) {
                            $this->insertionMode = new InSelectInTableInsertionMode();

                            return;
                        }
                    }
                }

                $this->insertionMode = new InSelectInsertionMode();

                return;
            }

            if ($node instanceof HTMLTableCellElement && !$last) {
                $this->insertionMode = new InCellInsertionMode();

                return;
            }

            if ($node instanceof HTMLTableRowElement) {
                $this->insertionMode = new InRowInsertionMode();

                return;
            }

            if ($node instanceof HTMLTableSectionElement) {
                $this->insertionMode = new InTableBodyInsertionMode();

                return;
            }

            if ($node instanceof HTMLTableCaptionElement) {
                $this->insertionMode = new InCaptionInsertionMode();

                return;
            }

            if ($node instanceof HTMLTableColElement && $node->localName === 'colgroup') {
                $this->insertionMode = new InColumnGroupInsertionMode();

                return;
            }

            if ($node instanceof HTMLTableElement) {
                $this->insertionMode = new InTableInsertionMode();

                return;
            }

            if ($node instanceof HTMLTemplateElement) {
                $mode = $this->templateInsertionModes->top();
                $this->insertionMode = new $mode();

                return;
            }

            if ($node instanceof HTMLHeadElement && !$last) {
                $this->insertionMode = new InHeadInsertionMode();

                return;
            }

            if ($node instanceof HTMLBodyElement) {
                $this->insertionMode = new InBodyInsertionMode();

                return;
            }

            if ($node instanceof HTMLFrameSetElement) {
                // Fragment case
                $this->insertionMode = new InFrameSetInsertionMode();

                return;
            }

            if ($node instanceof HTMLHtmlElement) {
                if (!$this->parser->headElementPointer) {
                    // Fragment case
                    $this->insertionMode = new BeforeHeadInsertionMode();

                    return;
                }

                $this->insertionMode = new AfterHeadInsertionMode();

                return;
            }

            if ($last) {
                // Fragment case
                $this->insertionMode = new InBodyInsertionMode();

                return;
            }

            $iterator->next();
        }
    }
}
