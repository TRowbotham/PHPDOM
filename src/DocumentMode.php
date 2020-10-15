<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#concept-document-mode
 */
final class DocumentMode
{
    public const NO_QUIRKS      = 1;
    public const LIMITED_QUIRKS = 2;
    public const QUIRKS         = 3;
}
