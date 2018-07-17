<?php
declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://html.spec.whatwg.org/multipage/dom.html#documentreadystate
 */
abstract class DocumentReadyState
{
    const LOADING     = 'loading';
    const INTERACTIVE = 'interactive';
    const COMPLETE    = 'complete';
}
