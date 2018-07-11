<?php
declare(strict_types=1);

namespace Rowbot\DOM\Parser\Token;

/**
 * Represents the end-of-file.
 *
 * {@inheritDoc}
 */
class EOFToken implements Token
{
    /**
     * {@inheritDoc}
     */
    public function getType(): int
    {
        return self::EOF_TOKEN;
    }
}
