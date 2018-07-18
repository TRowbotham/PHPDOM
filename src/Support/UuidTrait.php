<?php
declare(strict_types=1);

namespace Rowbot\DOM\Support;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

trait UuidTrait
{
    private $uuid;

    public function uuid(): string
    {
        return $this->uuid ?? ($this->uuid = Uuid::uuid4()->toString());
    }
}
