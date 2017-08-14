<?php
namespace Rowbot\DOM\Support;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

trait UuidTrait
{
    private $uuid;

    public function uuid(): UuidInterface
    {
        return $this->uuid ?? $this->uuid = Uuid::uuid4();
    }
}
