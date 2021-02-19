<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Parser\HTML\TreeBuilderContext;
use Rowbot\DOM\Parser\Token\Token;

abstract class InsertionMode
{
    /**
     * @var \Rowbot\DOM\Parser\HTML\TreeBuilderContext
     */
    protected $context;

    public function __construct(TreeBuilderContext $context)
    {
        $this->context = $context;
    }

    abstract public function processToken(Token $token): void;
}
