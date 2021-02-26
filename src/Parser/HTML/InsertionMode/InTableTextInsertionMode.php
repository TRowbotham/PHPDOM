<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML\InsertionMode;

use Rowbot\DOM\Parser\Token\CharacterToken;
use Rowbot\DOM\Parser\Token\Token;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#parsing-main-intabletext
 */
class InTableTextInsertionMode extends InsertionMode
{
    use InTableInsertionModeAnythingElseTrait;

    public function processToken(Token $token): void
    {
        if ($token instanceof CharacterToken) {
            if ($token->data === "\x00") {
                // Parse error.
                // Ignore the token.
                return;
            }

            // Append the character token to the pending table character tokens
            // list.
            $this->context->pendingTableCharacterTokens[] = $token;

            return;
        }

        // If any of the tokens in the pending table character tokens list
        // are character tokens that are not space characters, then this is
        // a parse error: reprocess the character tokens in the pending
        // table character tokens list using the rules given in the
        // "anything else" entry in the "in table" insertion mode.
        // Otherwise, insert the characters given by the pending table
        // character tokens list.
        $obj = $this->context;
        $methodName = 'insertCharacter';

        foreach ($this->context->pendingTableCharacterTokens as $characterToken) {
            if (
                $characterToken->data !== "\x09"
                && $characterToken->data !== "\x0A"
                && $characterToken->data !== "\x0C"
                && $characterToken->data !== "\x0D"
                && $characterToken->data !== "\x20"
            ) {
                $obj = $this;
                $methodName = 'inTableInsertionModeAnythingElse';

                break;
            }
        }

        foreach ($this->context->pendingTableCharacterTokens as $characterToken) {
            $obj->{$methodName}($characterToken);
        }

        // Switch the insertion mode to the original insertion mode and
        // reprocess the token.
        $this->context->insertionMode = $this->context->originalInsertionMode;
        $this->context->insertionMode->processToken($token);
    }
}
