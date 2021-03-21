<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\HTML\InsertionMode\InForeignContentInsertionMode;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\EOFToken;
use Rowbot\DOM\Parser\Token\StartTagToken;
use Rowbot\DOM\Parser\Token\Token;

class TreeBuilder
{
    use IntegrationPointTrait;

    /**
     * @var \Rowbot\DOM\Parser\HTML\TreeBuilderContext
     */
    private $context;

    public function __construct(TreeBuilderContext $context)
    {
        $this->context = $context;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#tree-construction-dispatcher
     */
    public function run(Token $token): void
    {
        do {
            if ($this->context->parser->openElements->isEmpty()) {
                break;
            }

            $adjustedCurrentNode = $this->context->parser->getAdjustedCurrentNode();

            if (
                $adjustedCurrentNode instanceof Element
                && $adjustedCurrentNode->namespaceURI === Namespaces::HTML
            ) {
                break;
            }

            if ($this->isMathMLTextIntegrationPoint($adjustedCurrentNode)) {
                if (
                    (
                        $token instanceof StartTagToken
                        && $token->tagName !== 'mglyph'
                        && $token->tagName !== 'malignmark'
                    )
                    || $token instanceof CharacterToken
                ) {
                    break;
                }
            }

            if (
                $adjustedCurrentNode instanceof Element
                && $adjustedCurrentNode->namespaceURI === Namespaces::MATHML
                && $adjustedCurrentNode->localName === 'annotation-xml'
                && $token instanceof StartTagToken
                && $token->tagName === 'svg'
            ) {
                break;
            }

            if (
                $this->isHTMLIntegrationPoint($adjustedCurrentNode, $this->context->elementTokenMap)
                && ($token instanceof StartTagToken || $token instanceof CharacterToken)
            ) {
                break;
            }

            if ($token instanceof EOFToken) {
                break;
            }

            // Process the token according to the rules given in the section for parsing tokens in
            // foreign content.
            (new InForeignContentInsertionMode())->processToken($this->context, $token);

            return;
        } while (false);

        // Process the token according to the rules given in the section corresponding to the
        // current insertion mode in HTML content.
        $this->context->insertionMode->processToken($this->context, $token);
    }

    public function getContext(): TreeBuilderContext
    {
        return $this->context;
    }
}
