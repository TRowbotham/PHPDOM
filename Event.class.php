<?php
// https://developer.mozilla.org/en-US/docs/Web/API/Event
// https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent
// https://dom.spec.whatwg.org/#event
// https://dom.spec.whatwg.org/#customevent

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

    public function preventDefault() {
        if ($this->mCancelable) {
            $this->mFlags |= self::EVENT_CANCELED;
        }
    }

    public function stopPropagation() {
        $this->mFlags |= self::EVENT_STOP_PROPAGATION;
    }

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
