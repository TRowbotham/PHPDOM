<?php
namespace Rowbot\DOM\Parser\HTML;

trait ParserCommon
{
    /**
     * The context element for the parser when it is created in the fragment
     * case.
     *
     * @var ?Element
     */
    private $contextElement;

    /**
     * Whether or not the HTML parser was created using the fragment parsing
     * algorithim. This can occur when things like Element::innerHTML are set.
     *
     * @var bool
     */
    private $isFragmentCase;

    /**
     * The stack of open elements.
     *
     * @var OpenElementStack
     */
    private $openElements;

    /**
     * The shared state of the parser, tokenizer, and treebuilder.
     *
     * @var ParserState
     */
    private $state;
}
