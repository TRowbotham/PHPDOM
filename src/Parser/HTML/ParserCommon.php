<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

trait ParserCommon
{
    /**
     * The context element for the parser when it is created in the fragment
     * case.
     *
     * @var \Rowbot\DOM\Element\Element|null
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
     * @var \Rowbot\DOM\Parser\Collection\OpenElementStack
     */
    private $openElements;

    /**
     * The shared state of the parser, tokenizer, and treebuilder.
     *
     * @var \Rowbot\DOM\Parser\HTML\ParserState
     */
    private $state;
}
