<?php

declare(strict_types=1);

namespace Rowbot\DOM\Event;

final class EventFlags
{
    public const STOP_PROPAGATION           = 1;
    public const STOP_IMMEDIATE_PROPAGATION = 2;
    public const CANCELED                   = 4;
    public const IN_PASSIVE_LISTENER        = 8;
    public const COMPOSED                   = 16;
    public const INITIALIZED                = 32;
    public const DISPATCH                   = 64;
}
