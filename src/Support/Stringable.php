<?php
declare(strict_types=1);

namespace Rowbot\DOM\Support;

interface Stringable
{
    public function toString();
    public function __toString();
}
