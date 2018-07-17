<?php
declare(strict_types=1);

namespace Rowbot\DOM\Event;

class Listener
{
    private $type;
    private $callback;
    private $capture;
    private $passive;
    private $once;
    private $removed;

    public function __construct(
        $type,
        $callback,
        $capture,
        $once = false,
        $passive = false
    ) {
        $this->type = $type;
        $this->callback = $callback;
        $this->capture = $capture;
        $this->once = $once;
        $this->passive = $passive;
        $this->removed = false;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function getCapture()
    {
        return $this->capture;
    }

    public function getPassive()
    {
        return $this->passive;
    }

    public function getOnce()
    {
        return $this->once;
    }

    public function getRemoved()
    {
        return $this->removed;
    }

    public function setRemoved($removed)
    {
        $this->removed = $removed;
    }

    public function isEqual($other)
    {
        if ($other->type === $this->type
            && $other->callback === $this->callback
            && $other->capture === $this->capture
        ) {
            return true;
        }

        return false;
    }
}
