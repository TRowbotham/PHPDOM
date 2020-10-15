<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser;

use Rowbot\DOM\Support\UniquelyIdentifiable;
use Rowbot\DOM\Support\UuidTrait;

class Bookmark implements UniquelyIdentifiable
{
    use UuidTrait;
}
