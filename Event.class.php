<?php
/**
 * Represents an event which can be dispatched to different objects to signal the occurance of an event.
 *
 * @link https://dom.spec.whatwg.org/#event
 * @link https://developer.mozilla.org/en-US/docs/Web/API/Event
 *
 * @property-read bool          $bubbles            Returns true if the event will traverse through its ancestors in reverse
 *                                                  tree order, otherwise false.
 *
 * @property-read bool          $cancelable         Returns true if the event's default action can be prevented, otherwise false.
 *
 * @property-read Node|object   $currentTarget      Returns the current object or Node whose event listeners are currently being invoked.
 *
 * @property-read bool          $defaultPrevented   Returns true if the event's preventDefault() method is invoked and the event's cancelable
 *                                                  attribute is true, otherwise false.
 *
 * @property-read int           $eventPhase         Returns the current phase that the event is in.  One of the following possibilities:
 *                                                  NONE: Events that are not currently being dispatched.
 *                                                  CAPTURING_PHASE: Events that are currently invoking event listeners in tree order.
 *                                                  AT_TARGET: Events that are currently invoking event listeners on the target Node or object.
 *                                                  BUBBLING_PHASE: Events that are currently invoking event listeners in reverse tree order,
 *                                                                  assuming that the event's bubbling property is true.
 *
 * @property-read bool          $isTrusted          Returns true if the event was dispatched by the browser, otherwise false.
 *
 * @property-read Node|object   $target             Returns the Node or object that dispatched the event.
 *
 * @property-read int           $timeStamp          Returns the creation time of the even in milliseconds.
 *
 * @property-read string        $type               Returns the type of event that was created.
 */
class Event {
    const NONE = 0;
    const CAPTURING_PHASE = 1;
    const AT_TARGET = 2;
    const BUBBLING_PHASE = 3;

    const EVENT_STOP_PROPAGATION = 1;
    const EVENT_STOP_IMMEDIATE_PROPATATION = 2;
    const EVENT_CANCELED = 4;
    const EVENT_INITIALIZED = 8;
    const EVENT_DISPATCHED = 16;

    protected $mBubbles;
    protected $mCancelable;
    protected $mCurrentTarget;
    protected $mEventPhase;
    protected $mFlags;
    protected $mIsTrusted;
    protected $mTarget;
    protected $mTimeStamp;
    protected $mType;

    public function __construct($aType, EventInit $aEventInitDict = null) {
        $this->mBubbles = $aEventInitDict ? $aEventInitDict->bubbles : false;
        $this->mCancelable = $aEventInitDict ? $aEventInitDict->cancelable : false;
        $this->mCurrentTarget = null;
        $this->mEventPhase = self::NONE;
        $this->mFlags |= self::EVENT_INITIALIZED;
        $this->mIsTrusted = false;
        $this->mTimeStamp = microtime();
        $this->mType = $aType;
    }

    public function __get($aName) {
        switch ($aName) {
            case 'bubbles':
                return $this->mBubbles;
            case 'cancelable':
                return $this->mCancelable;
            case 'currentTarget':
                return $this->mCurrentTarget;
            case 'defaultPrevented':
                return $this->mFlags & self::EVENT_CANCELED;
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
     * @param  string  $aType       The type of event to be created.
     *
     * @param  boolean $aBubbles    Optional. Whether or not the event will bubble up the tree, if the event is dispatched
     *                              on an object that participates in a tree.  Defaults to false.
     *
     * @param  boolean $aCancelable Optional. Whether or not the event's default action can be prevented.  Defaults to false.
     */
    public function initEvent($aType, $aBubbles = false, $aCancelable = false) {
        if ($this->mFlags & self::EVENT_DISPATCHED) {
            return;
        }

        $this->mBubbles = $aBubbles;
        $this->mCancelable = $aCancelable;
        $this->mFlags |= self::EVENT_INITIALIZED;
        $this->mFlags &= ~self::EVENT_STOP_PROPAGATION & ~self::EVENT_STOP_IMMEDIATE_PROPATATION & ~self::EVENT_CANCELED;
        $this->mIsTrusted = false;
        $this->mTarget = null;
        $this->mType = $aType;
    }

    /**
     * If the even'ts cancelable property is true, it signals that the operation that caused the event needs to be canceled.
     */
    public function preventDefault() {
        if ($this->mCancelable) {
            $this->mFlags |= self::EVENT_CANCELED;
        }
    }

    /**
     * If the event's target participates in a tree, this method will prevent the event from reaching any objects that
     * follow the current object.
     */
    public function stopPropagation() {
        $this->mFlags |= self::EVENT_STOP_PROPAGATION;
    }

    /**
     * If the event's target participates in a tree, this method will prevent the event from reaching any objects that
     * follow the current object as well as preventing any following event listeners from being invoked.
     */
    public function stopImmediatePropagation() {
        $this->mFlags |= self::EVENT_STOP_PROPAGATION | self::EVENT_STOP_IMMEDIATE_PROPATATION;
    }

    public function _isPropagationStopped() {
        return $this->mFlags & self::EVENT_STOP_PROPAGATION;
    }

    public function _isImmediatePropagationStopped() {
        return $this->mFlags & self::EVENT_STOP_IMMEDIATE_PROPATATION;
    }

    public function _setCurrentTarget(Node $aTarget) {
        $this->mCurrentTarget = $aTarget;
    }

    public function _setDispatched() {
        $this->mFlags |= self::EVENT_DISPATCHED;
    }

    public function _setTarget(Node $aTarget) {
        $this->mTarget = $aTarget;
    }

    public function _updateEventPhase($aPhase) {
        $this->mEventPhase = $aPhase;
    }
}

/**
 * Represents a custom event defined by the user which they can use to signal that an event has occured
 * in their code.
 *
 * @link https://dom.spec.whatwg.org/#customevent
 * @link https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent
 *
 * @property mixed $detail A proprerty that the user may use to attach additional useful information to the
 *                         event.
 */
class CustomEvent extends Event {
    private $mDetail;

    public function __construct($aType, CustomEventInit $aEventInitDict = null) {
        parent::__construct($aType);

        $initDict = $aEventInitDict ? $aEventInitDict : new CustomEventInit();
        $this->mBubbles = $initDict->bubbles;
        $this->mCancelable = $initDict->cancelable;
        $this->mDetail =& $initDict->detail;
    }

    public function __get($aName) {
        switch ($aName) {
            case 'detail':
                return $this->mDetail;
            default:
                return parent::__get($aName);
        }
    }

    /**
     * Initializes or reinitializes a CustomEvent.
     *
     * @param  string  $aType       The type of event to be created.
     *
     * @param  boolean $aBubbles    Optional.  Whether or not the event will bubble up the tree, if the event is dispatched
     *                              on an object that participates in a tree.  Defaults to false.
     *
     * @param  boolean $aCancelable Optional.  Whether or not the event's default action can be prevented.  Defaults to false.
     *
     * @param  mixed   &$aDetail    Optional.  Additional data to be sent along with the event.
     */
    public function initCustomEvent($aType, $aBubbles = false, $aCancelable = false, &$aDetail = null) {
        if ($this->mFlags & self::EVENT_DISPATCHED) {
            return;
        }

        $this->initEvent($aType, $aBubbles, $aCancelable);
        $this->mDetail =& $aDetail;
    }
}

class EventInit {
    public $bubbles;
    public $cancelable;

    public function __construct() {
        $this->bubbles = false;
        $this->cancelable = false;
    }
}

class CustomEventInit extends EventInit {
    public $detail;

    public function __construct() {
        parent::__construct();

        $this->detail = null;
    }
}
