<?php

declare(strict_types=1);

namespace Rowbot\DOM\Support;

interface UniquelyIdentifiable
{
    public function uuid(): string;
}
