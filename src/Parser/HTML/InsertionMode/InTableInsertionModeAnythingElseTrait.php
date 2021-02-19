<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Parser\Token\Token;

trait InTableInsertionModeAnythingElseTrait
{
    /**
     * The "in table" insertion mode's "anything else" steps.
     */
    private function inTableInsertionModeAnythingElse(Token $token): void
    {
        // Parse error.
        // Enable foster parenting, process the token using the rules for
        // the "in body" insertion mode, and then disable foster parenting.
        $this->context->fosterParenting = true;
        (new InBodyInsertionMode($this->context))->processToken($token);
        $this->context->fosterParenting = false;
    }
}
