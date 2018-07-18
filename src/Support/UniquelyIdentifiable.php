<?php
declare(strict_types=1);

namespace Rowbot\DOM\Support;

use Ramsey\Uuid\UuidInterface;

interface UniquelyIdentifiable
{
    public function uuid(): string;
}
