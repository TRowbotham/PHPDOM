<?php
namespace Rowbot\DOM\Event;

/**
 * Represents a custom event defined by the user which they can use to signal
 * that an event has occured in their code.
 *
 * @link https://dom.spec.whatwg.org/#customevent
 * @link https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent
 *
 * @property mixed $detail A proprerty that the user may use to attach
 *     additional useful information to the event.
 */
class CustomEvent extends Event
{
    private $detail;

    public function __construct(
        string $type,
        CustomEventInit $eventInitDict = null
    ) {
        parent::__construct($type);

        $initDict = $eventInitDict ? $eventInitDict : new CustomEventInit();
        $this->bubbles = $initDict->bubbles;
        $this->cancelable = $initDict->cancelable;
        $this->detail =& $initDict->detail;
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'detail':
                return $this->detail;
            default:
                return parent::__get($name);
        }
    }

    /**
     * Initializes or reinitializes a CustomEvent.
     *
     * @see https://dom.spec.whatwg.org/#dom-customevent-initcustomevent
     *
     * @param string $type The type of event to be created.
     *
     * @param boolean $bubbles Optional.  Whether or not the event will bubble
     *     up the tree, if the event is dispatched on an object that
     *     participates in a tree.  Defaults to false.
     *
     * @param boolean $cancelable Optional.  Whether or not the event's default
     *     action can be prevented.  Defaults to false.
     *
     * @param mixed &$detail Optional.  Additional data to be sent along with
     *     the event.
     */
    public function initCustomEvent(
        string $type,
        $bubbles = false,
        $cancelable = false,
        &$detail = null
    ) {
        if ($this->flags & EventFlags::DISPATCH) {
            return;
        }

        $this->init($type, $bubbles, $cancelable);
        $this->detail =& $detail;
    }
}
