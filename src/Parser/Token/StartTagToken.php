<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\Token;

/**
 * When a start tag token is emitted with its self-closing flag set, if the flag is not acknowledged by the tree
 * construction stage, that is a non-void-html-element-start-tag-with-trailing-solidus parse error.
 *
 * {@inheritDoc}
 */
class StartTagToken extends TagToken
{
}
