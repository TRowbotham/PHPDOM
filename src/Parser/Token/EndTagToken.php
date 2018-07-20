<?php
declare(strict_types=1);

namespace Rowbot\DOM\Parser\Token;

/**
 * When an end tag token is emitted with attributes, that is an end-tag-with-attributes parse error. When an end tag
 * token is emitted with its self-closing flag set, that is an end-tag-with-trailing-solidus parse error.
 *
 * {@inheritDoc}
 */
class EndTagToken extends TagToken
{
}
