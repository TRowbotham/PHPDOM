<?php

declare(strict_types=1);

namespace Rowbot\DOM\Event;

use Rowbot\DOM\Node;
use SplDoublyLinkedList;

use function microtime;

/**
 * Represents an event which can be dispatched to different objects to signal the occurance of an event.
 *
 * @see https://dom.spec.whatwg.org/#event
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Event
 *
 * @property-read bool                          $bubbles          Returns true if the event will traverse through its
 *                                                                ancestors in reverse tree order, otherwise false.
 *
 * @property-read bool                          $cancelable       Returns true if the event's default action can be
 *                                                                prevented, otherwise false.
 *
 * @property-read bool                          $composed         Returns true if the event crosses a shadow root
 *                                                                boundary, false otherwise.
 *
 * @property-read \Rowbot\DOM\Event\EventTarget $currentTarget    Returns the current EventTarget whose event listeners
 *                                                                are currently being invoked.
 *
 * @property-read bool                          $defaultPrevented Returns true if the event's preventDefault() method is
 *                                                                invoked and the event's cancelable attribute is true,
 *                                                                otherwise false.
 *
 * @property-read int                           $eventPhase       Returns the current phase that the event is in. One of
 *                                                                the following constants:
 *
 *                                                                  - NONE:
 *                                                                      Events that are not currently being dispatched.
 *                                                                  - CAPTURING_PHASE:
 *                                                                      Events that are currently invoking event
 *                                                                      listeners in tree order.
 *                                                                  - AT_TARGET:
 *                                                                      Events that are currently invoking event
 *                                                                      listeners on the target Node or object.
 *                                                                  - BUBBLING_PHASE:
 *                                                                      Events that are currently invoking event
 *                                                                      listeners in reverse tree order, assuming that
 *                                                                      the event's bubbling property is true.
 *
 * @property-read bool                          $isTrusted         Returns true if the event was dispatched by the
 *                                                                 browser, otherwise false.
 *
 * @property-read \Rowbot\DOM\Event\EventTarget $target            Returns the EventTarget that the event was dispatched
 *                                                                 to.
 *
 * @property-read int                           $timeStamp         Returns the creation time of the even in
 *                                                                 milliseconds.
 *
 * @property-read string                        $type              Returns the type of event that was created.
 */
class Event
{
    public const NONE            = 0;
    public const CAPTURING_PHASE = 1;
    public const AT_TARGET       = 2;
    public const BUBBLING_PHASE  = 3;

    /**
     * @var bool
     */
    protected $bubbles;

    /**
     * @var bool
     */
    protected $cancelable;

    /**
     * @var \Rowbot\DOM\Event\EventTarget
     */
    protected $currentTarget;

    /**
     * @var int
     */
    protected $eventPhase;

    /**
     * @var int
     */
    protected $flags;

    /**
     * @var bool
     */
    protected $isTrusted;

    /**
     * @var \SplDoublyLinkedList<array{
     *     item: \Rowbot\DOM\Event\EventTarget,
     *     target: \Rowbot\DOM\Event\EventTarget|null,
     *     relatedTarget: \Rowbot\DOM\Event\EventTarget|null
     * }>
     */
    protected $path;

    /**
     * @var \Rowbot\DOM\Event\EventTarget|null
     */
    protected $relatedTarget;

    /**
     * @var \Rowbot\DOM\Event\EventTarget
     */
    protected $target;

    /**
     * @var string
     */
    protected $timeStamp;

    /**
     * @var string
     */
    protected $type;

    public function __construct(string $type, EventInit $eventInitDict = null)
    {
        $initDict = $eventInitDict ?: new EventInit();
        $this->bubbles = $initDict->bubbles;
        $this->cancelable =  $initDict->cancelable;
        $this->currentTarget = null;
        $this->eventPhase = self::NONE;
        $this->flags |= EventFlags::INITIALIZED;
        $this->isTrusted = false;
        $this->path = new SplDoublyLinkedList();
        $this->target = null;
        $this->timeStamp = microtime();
        $this->type = $type;
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'bubbles':
                return $this->bubbles;

            case 'cancelable':
                return $this->cancelable;

            case 'composed':
                return (bool) ($this->flags & EventFlags::COMPOSED);

            case 'currentTarget':
                return $this->currentTarget;

            case 'defaultPrevented':
                return (bool) ($this->flags & EventFlags::CANCELED);

            case 'eventPhase':
                return $this->eventPhase;

            case 'isTrusted':
                return $this->isTrusted;

            case 'target':
                return $this->target;

            case 'timeStamp':
                return $this->timeStamp;

            case 'type':
                return $this->type;
        }
    }

    /**
     * Initializes or reinitializes an event.
     *
     * @see https://dom.spec.whatwg.org/#dom-event-initevent
     *
     * @param string $type       The type of event to be created.
     * @param bool   $bubbles    (optional) Whether or not the event will bubble up the tree, if the event is dispatched
     *                           on an object that participates in a tree.
     * @param bool   $cancelable (optional) Whether or not the event's default action can be prevented.
     */
    public function initEvent(string $type, bool $bubbles = false, bool $cancelable = false): void
    {
        if ($this->flags & EventFlags::DISPATCH) {
            return;
        }

        $this->init($type, $bubbles, $cancelable);
    }

    /**
     * Returns the path that the event will take to and from the target that the
     * event was dispated to.
     *
     * @see https://dom.spec.whatwg.org/#dom-event-composedpath
     *
     * @return list<\Rowbot\DOM\Event\EventTarget>
     */
    public function composedPath(): array
    {
        $composedPath = [];

        // Clone event's path and set its iterator mode to the default as
        // the Event's path could have its iterator set in reverse if the Event
        // is currently in the capturing phase.
        $path = clone $this->path;
        $path->setIteratorMode(
            SplDoublyLinkedList::IT_MODE_FIFO | SplDoublyLinkedList::IT_MODE_KEEP
        );

        foreach ($path as $tuple) {
            // If $currentTarget is a node and $tuple’s item is not
            // closed-shadow-hidden from $currentTarget, or $currentTarget
            // is not a node, then append $tuple’s item to $composedPath.
            if (
                ($this->currentTarget instanceof Node
                && !$tuple['item']->isClosedShadowHiddenFrom(
                    $this->currentTarget
                ))
                || !$this->currentTarget instanceof Node
            ) {
                $composedPath[] = $tuple['item'];
            }
        }

        return $composedPath;
    }

    /**
     * If the even'ts cancelable property is true, it signals that the operation
     * that caused the event needs to be canceled.
     *
     * @see https://dom.spec.whatwg.org/#dom-event-preventdefault
     */
    public function preventDefault(): void
    {
        if ($this->cancelable && !($this->flags & EventFlags::IN_PASSIVE_LISTENER)) {
            $this->flags |= EventFlags::CANCELED;
        }
    }

    /**
     * If the event's target participates in a tree, this method will prevent
     * the event from reaching any objects that follow the current object.
     */
    public function stopPropagation(): void
    {
        $this->flags |= EventFlags::STOP_PROPAGATION;
    }

    /**
     * If the event's target participates in a tree, this method will prevent
     * the event from reaching any objects that follow the current object as
     * well as preventing any following event listeners from being invoked.
     */
    public function stopImmediatePropagation(): void
    {
        $this->flags |= EventFlags::STOP_PROPAGATION | EventFlags::STOP_IMMEDIATE_PROPAGATION;
    }

    /**
     * Gets the flags set for the Event object.
     *
     * @internal
     */
    public function getFlags(): int
    {
        return $this->flags;
    }

    /**
     * Sets the Event object's current target.
     *
     * @internal
     *
     * @param mixed $target The current event target.
     */
    public function setCurrentTarget($target): void
    {
        $this->currentTarget = $target;
    }

    /**
     * Sets the Event object's event phase.
     *
     * @internal
     */
    public function setEventPhase(int $phase): void
    {
        $this->eventPhase = $phase;
    }

    /**
     * Sets a bitwise flag.
     *
     * @internal
     */
    public function setFlag(int $flag): void
    {
        $this->flags |= $flag;
    }

    /**
     * Gets the Event object's path.
     *
     * @internal
     *
     * @return \SplDoublyLinkedList<array{
     *     item: \Rowbot\DOM\Event\EventTarget,
     *     target: \Rowbot\DOM\Event\EventTarget|null,
     *     relatedTarget: \Rowbot\DOM\Event\EventTarget|null
     * }>
     */
    public function getPath(): SplDoublyLinkedList
    {
        return $this->path;
    }

    /**
     * Empties the Event object's path by creating a new one.
     *
     * @internal
     */
    public function emptyPath(): void
    {
        $this->path = new SplDoublyLinkedList();
    }

    /**
     * Sets the Event object's trusted state.
     *
     * @internal
     */
    public function setIsTrusted(bool $isTrusted): void
    {
        $this->isTrusted = $isTrusted;
    }

    /**
     * Sets the Event object's target.
     *
     * @internal
     *
     * @param mixed $target The event's target.
     */
    public function setTarget($target): void
    {
        $this->target = $target;
    }

    /**
     * Sets the Event object's type.
     *
     * @internal
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Unsets a bitwise flag.
     *
     * @internal
     */
    public function unsetFlag(int $flag): void
    {
        $this->flags &= ~$flag;
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-event-initialize
     */
    protected function init(string $type, bool $bubbles, bool $cancelable): void
    {
        $this->flags |= EventFlags::INITIALIZED;
        $this->flags &= ~(EventFlags::STOP_PROPAGATION
            | EventFlags::STOP_IMMEDIATE_PROPAGATION
            | EventFlags::CANCELED);
        $this->isTrusted = false;
        $this->target = null;
        $this->type = $type;
        $this->bubbles = $bubbles;
        $this->cancelable = $cancelable;
    }

    public function getRelatedTarget(): ?EventTarget
    {
        return $this->relatedTarget;
    }

    public function setRelatedTarget(EventTarget $target = null): void
    {
        $this->relatedTarget = $target;
    }

    public function retarget(): void
    {
        // TODO
    }
}
