<?php

declare(strict_types=1);

namespace Rowbot\DOM\Support;

interface Stringable
{
    public function toString(): string;

    public function __toString(): string;
}
