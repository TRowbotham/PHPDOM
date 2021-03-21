<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Parser\HTML\TreeBuilderContext;
use Rowbot\DOM\Parser\Token\Token;

interface InsertionMode
{
    public function processToken(TreeBuilderContext $context, Token $token): void;
}
