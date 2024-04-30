<?php

declare(strict_types=1);

namespace Rowbot\DOM\Range;

enum Position: int
{
    case BEFORE = -1;

    case EQUAL = 0;

    case AFTER = 1;
}
