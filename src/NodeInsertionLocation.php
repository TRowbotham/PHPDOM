<?php

declare(strict_types=1);

namespace Rowbot\DOM;

enum NodeInsertionLocation: string
{
    case BEFORE_BEGIN = 'beforebegin';

    case AFTER_BEGIN = 'afterbegin';

    case BEFORE_END = 'beforeend';

    case AFTER_END = 'afterend';
}
