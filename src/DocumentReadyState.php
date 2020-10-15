<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://html.spec.whatwg.org/multipage/dom.html#documentreadystate
 */
final class DocumentReadyState
{
    public const LOADING     = 'loading';
    public const INTERACTIVE = 'interactive';
    public const COMPLETE    = 'complete';
}
