<?php
declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#concept-document-mode
 */
abstract class DocumentMode
{
    const NO_QUIRKS      = 1;
    const LIMITED_QUIRKS = 2;
    const QUIRKS         = 3;
}
