<?php

declare(strict_types=1);

namespace Rowbot\DOM\Support;

use function bin2hex;
use function random_bytes;

trait UuidTrait
{
    /**
     * @var string
     */
    private $uuid;

    public function uuid(): string
    {
        return $this->uuid ?? ($this->uuid = bin2hex(random_bytes(16)));
    }
}
