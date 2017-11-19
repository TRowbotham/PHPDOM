<?php
namespace Rowbot\DOM\Event;

use Rowbot\DOM\Utils;

/**
 * Represents an event which can be dispatched to different objects to signal
 * the occurance of an event.
 *
 * @see https://dom.spec.whatwg.org/#event
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Event
 *
 * @property-read bool $bubbles Returns true if the event will traverse through
 *     its ancestors in reverse tree order, otherwise false.
 *
 * @property-read bool $cancelable Returns true if the event's default action
 *     can be prevented, otherwise false.
 *
 * @property-read bool $composed Returns true if the event crosses a shadow root
 *     boundary, false otherwise.
 *
 * @property-read EventTarget $currentTarget Returns the current EventTarget
 *     whose event listeners are currently being invoked.
 *
 * @property-read bool $defaultPrevented Returns true if the event's
 *     preventDefault() method is invoked and the event's cancelable attribute
 *     is true, otherwise false.
 *
 * @property-read int $eventPhase Returns the current phase that the event is
 *     in.  One of the following constants:
 *
 *         - NONE: Events that are not currently being dispatched.
 *         - CAPTURING_PHASE: Events that are currently invoking event listeners
 *             in tree order.
 *         - AT_TARGET: Events that are currently invoking event listeners on
 *             the target Node or object.
 *         - BUBBLING_PHASE: Events that are currently invoking event listeners
 *             in reverse tree order, assuming that the event's bubbling
 *             property is true.
 *
 * @property-read bool $isTrusted Returns true if the event was dispatched by
 *     the browser, otherwise false.
 *
 * @property-read EventTarget $target Returns the EventTarget that the event was
 *     dispatched to.
 *
 * @property-read int $timeStamp Returns the creation time of the even in
 *     milliseconds.
 *
 * @property-read string $type Returns the type of event that was created.
 */
class Event
{
    const NONE            = 0;
    const CAPTURING_PHASE = 1;
    const AT_TARGET       = 2;
    const BUBBLING_PHASE  = 3;

    protected $bubbles;
    protected $cancelable;
    protected $currentTarget;
    protected $eventPhase;
    protected $flags;
    protected $isTrusted;
    protected $path;
    protected $relatedTarget;
    protected $target;
    protected $timeStamp;
    protected $type;

    public function __construct($type, EventInit $eventInitDict = null)
    {
        $initDict = $eventInitDict ?: new EventInit();
        $this->bubbles = $initDict->bubbles;
        $this->cancelable =  $initDict->cancelable;
        $this->currentTarget = null;
        $this->eventPhase = self::NONE;
        $this->flags |= EventFlags::INITIALIZED;
        $this->isTrusted = false;
        $this->path = new \SplDoublyLinkedList();
        $this->target = null;
        $this->timeStamp = microtime();
        $this->type = $type;
    }

    public function __get($name)
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
     * @param string $type The type of event to be created.
     *
     * @param boolean $bubbles Optional. Whether or not the event will bubble
     *     up the tree, if the event is dispatched on an object that
     *     participates in a tree.  Defaults to false.
     *
     * @param boolean $cancelable Optional. Whether or not the event's default
     *     action can be prevented.  Defaults to false.
     */
    public function initEvent($type, $bubbles = false, $cancelable = false)
    {
        if ($this->flags & EventFlags::DISPATCH) {
            return;
        }

        $this->init(Utils::DOMString($type), $bubbles, $cancelable);
    }

    /**
     * Returns the path that the event will take to and from the target that the
     * event was dispated to.
     *
     * @see https://dom.spec.whatwg.org/#dom-event-composedpath
     *
     * @return EventTarget[]
     */
    public function composedPath()
    {
        $composedPath = [];
        $currentTarget = $this->currentTarget;

        // Clone event's path and set its iterator mode to the default as
        // the Event's path could have its iterator set in reverse if the Event
        // is currently in the capturing phase.
        $path = clone $this->path;
        $path->setIteratorMode(
            \SplDoublyLinkedList::IT_MODE_FIFO |
            \SplDoublyLinkedList::IT_MODE_KEEP
        );

        foreach ($path as $tuple) {
            // If $currentTarget is a node and $tuple’s item is not
            // closed-shadow-hidden from $currentTarget, or $currentTarget
            // is not a node, then append $tuple’s item to $composedPath.
            if (($this->currentTarget instanceof Node
                && !$tuple['item']->isClosedShadowHiddenFrom(
                    $this->currentTarget
                ))
                || !($this->currentTarget instanceof Node)
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
    public function preventDefault()
    {
        if ($this->cancelable
            && !($this->flags & EventFlags::IN_PASSIVE_LISTENER)
        ) {
            $this->flags |= EventFlags::CANCELED;
        }
    }

    /**
     * If the event's target participates in a tree, this method will prevent
     * the event from reaching any objects that follow the current object.
     */
    public function stopPropagation()
    {
        $this->flags |= EventFlags::STOP_PROPAGATION;
    }

    /**
     * If the event's target participates in a tree, this method will prevent
     * the event from reaching any objects that follow the current object as
     * well as preventing any following event listeners from being invoked.
     */
    public function stopImmediatePropagation()
    {
        $this->flags |= EventFlags::STOP_PROPAGATION |
            EventFlags::STOP_IMMEDIATE_PROPAGATION;
    }

    /**
     * Gets the flags set for the Event object.
     *
     * @internal
     *
     * @return int
     */
    public function getFlags()
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
    public function setCurrentTarget($target)
    {
        $this->currentTarget = $target;
    }

    /**
     * Sets the Event object's event phase.
     *
     * @internal
     *
     * @param int $phase An integer representing the current event phase.
     */
    public function setEventPhase($phase)
    {
        $this->eventPhase = $phase;
    }

    /**
     * Sets a bitwise flag.
     *
     * @internal
     *
     * @param int $flag A bitwise flag.
     */
    public function setFlag($flag)
    {
        $this->flags |= $flag;
    }

    /**
     * Gets the Event object's path.
     *
     * @internal
     *
     * @return SPLDoublyLinkedList
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Empties the Event object's path by creating a new one.
     *
     * @internal
     */
    public function emptyPath()
    {
        $this->path = new \SplDoublyLinkedList();
    }

    /**
     * Sets the Event object's trusted state.
     *
     * @internal
     *
     * @param bool $isTrusted The trusted state of the event.
     */
    public function setIsTrusted($isTrusted)
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
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * Sets the Event object's type.
     *
     * @internal
     *
     * @param string $type The event's type.
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Unsets a bitwise flag.
     *
     * @internal
     *
     * @param int $flag A bitwise flag.
     */
    public function unsetFlag($flag)
    {
        $this->flags &= ~$flag;
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-event-initialize
     *
     * @param string $type The event type.
     *
     * @param bool $bubbles Whether the event bubbles or not.
     *
     * @param bool $cancelable Whether the event is cancelable or not.
     */
    protected function init($type, $bubbles, $cancelable)
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

    public function getRelatedTarget()
    {
        return $this->relatedTarget;
    }

    public function setRelatedTarget(EventTarget $target = null)
    {
        $this->relatedTarget = $target;
    }

    public function retarget()
    {
        // TODO
    }
}
