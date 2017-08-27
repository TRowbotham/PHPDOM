<?php
namespace Rowbot\DOM\Support;

use Ramsey\Uuid\UuidInterface;

interface UniquelyIdentifiable
{
    public function uuid(): UuidInterface;
}
