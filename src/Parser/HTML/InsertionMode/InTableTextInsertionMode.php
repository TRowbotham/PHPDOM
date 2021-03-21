<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Parser\HTML\TreeBuilderContext;
use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intabletext
 */
class InTableTextInsertionMode extends InsertionMode
{
    use InTableInsertionModeAnythingElseTrait;

    public function processToken(TreeBuilderContext $context, Token $token): void
    {
        if ($token instanceof CharacterToken) {
            if ($token->data === "\x00") {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Append the character token to the pending table character tokens
            // list.
            $context->pendingTableCharacterTokens[] = $token;

            return;
        }

        // If any of the tokens in the pending table character tokens list
        // are character tokens that are not space characters, then this is
        // a parse error: reprocess the character tokens in the pending
        // table character tokens list using the rules given in the
        // "anything else" entry in the "in table" insertion mode.
        // Otherwise, insert the characters given by the pending table
        // character tokens list.
        $methodName = 'insertCharacter';

        foreach ($context->pendingTableCharacterTokens as $characterToken) {
            if (
                $characterToken->data !== "\x09"
                && $characterToken->data !== "\x0A"
                && $characterToken->data !== "\x0C"
                && $characterToken->data !== "\x0D"
                && $characterToken->data !== "\x20"
            ) {
                $methodName = 'inTableInsertionModeAnythingElse';

                break;
            }
        }

        foreach ($context->pendingTableCharacterTokens as $characterToken) {
            $this->{$methodName}($context, $characterToken);
        }

        // Switch the insertion mode to the original insertion mode and
        // reprocess the token.
        $context->insertionMode = $context->originalInsertionMode;
        $context->insertionMode->processToken($context, $token);
    }
}
