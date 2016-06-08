<?php
namespace phpjs\events;

/**
 * Represents an event which can be dispatched to different objects to signal
 * the occurance of an event.
 *
 * @link https://dom.spec.whatwg.org/#event
 * @link https://developer.mozilla.org/en-US/docs/Web/API/Event
 *
 * @property-read bool $bubbles Returns true if the event will traverse through
 *     its ancestors in reverse tree order, otherwise false.
 *
 * @property-read bool $cancelable Returns true if the event's default action
 *     can be prevented, otherwise false.
 *
 * @property-read Node|object $currentTarget Returns the current object or Node
 *     whose event listeners are currently being invoked.
 *
 * @property-read bool $defaultPrevented Returns true if the event's
 *     preventDefault() method is invoked and the event's cancelable attribute
 *     is true, otherwise false.
 *
 * @property-read int $eventPhase Returns the current phase that the event is
 *     in.  One of the following possibilities:
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
 * @property-read Node|object $target Returns the Node or object that dispatched
 *     the event.
 *
 * @property-read int $timeStamp Returns the creation time of the even in
 *     milliseconds.
 *
 * @property-read string $type Returns the type of event that was created.
 */
class Event
{
    const NONE = 0;
    const CAPTURING_PHASE = 1;
    const AT_TARGET = 2;
    const BUBBLING_PHASE = 3;

    protected $mBubbles;
    protected $mCancelable;
    protected $mCurrentTarget;
    protected $mEventPhase;
    protected $mFlags;
    protected $mIsTrusted;
    protected $mTarget;
    protected $mTimeStamp;
    protected $mType;

    public function __construct($aType, EventInit $aEventInitDict = null)
    {
        $this->mBubbles = $aEventInitDict ? $aEventInitDict->bubbles : false;
        $this->mCancelable = $aEventInitDict ?
            $aEventInitDict->cancelable : false;
        $this->mCurrentTarget = null;
        $this->mEventPhase = self::NONE;
        $this->mFlags |= EventFlags::INITIALIZED;
        $this->mIsTrusted = false;
        $this->mTarget = null;
        $this->mTimeStamp = microtime();
        $this->mType = $aType;
    }

    public function __destruct()
    {
        $this->mCurrentTarget = null;
        $this->mTarget = null;
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'bubbles':
                return $this->mBubbles;
            case 'cancelable':
                return $this->mCancelable;
            case 'currentTarget':
                return $this->mCurrentTarget;
            case 'defaultPrevented':
                return $this->mFlags & EventFlags::CANCELED;
            case 'eventPhase':
                return $this->mEventPhase;
            case 'isTrusted':
                return $this->mIsTrusted;
            case 'target':
                return $this->mTarget;
            case 'timeStamp':
                return $this->mTimeStamp;
            case 'type':
                return $this->mType;
        }
    }

    /**
     * Initializes or reinitializes an event.
     *
     * @param string $aType The type of event to be created.
     *
     * @param boolean $aBubbles Optional. Whether or not the event will bubble
     *     up the tree, if the event is dispatched on an object that
     *     participates in a tree.  Defaults to false.
     *
     * @param boolean $aCancelable Optional. Whether or not the event's default
     *     action can be prevented.  Defaults to false.
     */
    public function initEvent($aType, $aBubbles = false, $aCancelable = false)
    {
        if ($this->mFlags & EventFlags::DISPATCH) {
            return;
        }

        $this->mBubbles = $aBubbles;
        $this->mCancelable = $aCancelable;
        $this->mFlags |= EventFlags::INITIALIZED;
        $this->mFlags &= ~EventFlags::STOP_PROPAGATION &
            ~EventFlags::STOP_IMMEDIATE_PROPAGATION &
            ~EventFlags::CANCELED;
        $this->mIsTrusted = false;
        $this->mTarget = null;
        $this->mType = $aType;
    }

    /**
     * If the even'ts cancelable property is true, it signals that the operation
     * that caused the event needs to be canceled.
     */
    public function preventDefault()
    {
        if ($this->mCancelable) {
            $this->mFlags |= EventFlags::CANCELED;
        }
    }

    /**
     * If the event's target participates in a tree, this method will prevent
     * the event from reaching any objects that follow the current object.
     */
    public function stopPropagation()
    {
        $this->mFlags |= EventFlags::STOP_PROPAGATION;
    }

    /**
     * If the event's target participates in a tree, this method will prevent
     * the event from reaching any objects that follow the current object as
     * well as preventing any following event listeners from being invoked.
     */
    public function stopImmediatePropagation()
    {
        $this->mFlags |= EventFlags::STOP_PROPAGATION |
            EventFlags::STOP_IMMEDIATE_PROPAGATION;
    }

    public function _getFlags()
    {
        return $this->mFlags;
    }

    public function _setCurrentTarget($aTarget)
    {
        $this->mCurrentTarget = $aTarget;
    }

    public function _setEventPhase($aPhase)
    {
        $this->mEventPhase = $aPhase;
    }

    public function _setFlag($aFlag)
    {
        $this->mFlags |= $aFlag;
    }

    public function _setIsTrusted($aIsTrusted)
    {
        $this->mIsTrusted = $aIsTrusted;
    }

    public function _setTarget($aTarget)
    {
        $this->mTarget = $aTarget;
    }

    public function _unsetFlag($aFlag)
    {
        $this->mFlags &= ~$aFlag;
    }
}
