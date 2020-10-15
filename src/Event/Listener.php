<?php

declare(strict_types=1);

namespace Rowbot\DOM\Event;

class Listener
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var callable
     */
    private $callback;

    /**
     * @var bool
     */
    private $capture;

    /**
     * @var bool
     */
    private $passive;

    /**
     * @var bool
     */
    private $once;

    /**
     * @var bool
     */
    private $removed;

    public function __construct(
        string $type,
        callable $callback,
        bool $capture,
        bool $once = false,
        bool $passive = false
    ) {
        $this->type = $type;
        $this->callback = $callback;
        $this->capture = $capture;
        $this->once = $once;
        $this->passive = $passive;
        $this->removed = false;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCallback(): callable
    {
        return $this->callback;
    }

    public function getCapture(): bool
    {
        return $this->capture;
    }

    public function getPassive(): bool
    {
        return $this->passive;
    }

    public function getOnce(): bool
    {
        return $this->once;
    }

    public function getRemoved(): bool
    {
        return $this->removed;
    }

    public function setRemoved(bool $removed): void
    {
        $this->removed = $removed;
    }

    public function isEqual(self $other): bool
    {
        return $other->type === $this->type
            && $other->callback === $this->callback
            && $other->capture === $this->capture;
    }
}
