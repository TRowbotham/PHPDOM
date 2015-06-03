<?php
// https://html.spec.whatwg.org/multipage/semantics.html#the-a-element

require_once 'HTMLElement.class.php';
require_once 'DOMSettableTokenList.class.php';
require_once 'URLUtils.class.php';

class HTMLAnchorElement extends HTMLElement {
	use URLUtils;

	private $mDownload;
	private $mHrefLang;
	private $mInvalidateRelList;
	private $mPing;
	private $mRel;
	private $mRelList;
	private $mTarget;
	private $mType;

	public function __construct($aTagName) {
		parent::__construct($aTagName);
		$this->initURLUtils();

		$this->mDownload = '';
		$this->mHrefLang = '';
		$this->mInvalidateRelList = false;
		$this->mPing = new DOMSettableTokenList($this, 'ping');
		$this->mRel = '';
		$this->mRelList = null;
		$this->mTarget = '';
		$this->mType = '';
	}

	public function __get($aName) {
		switch ($aName) {
			case 'download':
				return $this->mDownload;
			case 'hrefLang':
				return $this->mHrefLang;
			case 'ping':
				return $this->mPing->value;
			case 'rel':
				return $this->mRel;
			case 'relList':
				return $this->getRelList();
			case 'target':
				return $this->mTarget;
			case 'type':
				return $this->mType;
			default:
				$rv = $this->URLUtilsGetter($aName);

				if ($rv !== false) {
					return $rv;
				}

				return parent::__get($aName);
		}
	}

	public function __set($aName, $aValue) {
		switch ($aName) {
			case 'download':
				$this->mDownload = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'hrefLang':
				$this->mHrefLang = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'ping':
				$this->mPing->value = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'rel':
				$this->mRel = $aValue;
				$this->mInvalidateRelList = true;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'target':
				$this->mTarget = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			case 'type':
				$this->mType = $aValue;
				$this->_updateAttributeOnPropertyChange($aName, $aValue);

				break;

			default:
				$this->URLUtilsSetter($aName, $aValue);
				parent::__set($aName, $aValue);
		}
	}

	public function update(SplSubject $aObject) {
		if ($aObject instanceof URLSearchParams) {
			$this->mUrl->mQuery = $aObject->toString();
			$this->preupdate();
		} else {
			parent::update($aObject);
		}
	}

	protected function _onAttributeChange(Event $aEvent) {
		switch ($aEvent->detail['attr']->name) {
			case 'download':
				$this->mDownload = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

				break;

			case 'hrefLang':
				$this->mHrefLang = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

				break;

			case 'ping':
				$this->mPing->value = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

				break;

			case 'rel':
				$this->mRel = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';
				$this->mInvalidateRelList = true;

				break;

			case 'target':
				$this->mTarget = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

				break;

			case 'type':
				$this->mType = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

				break;

			default:
				parent::_onAttributeChange($aEvent);
		}
	}

	private function getBaseURL() {
		return URLParser::basicURLParser($this->mOwnerDocument->baseURI);
	}

	private function getRelList() {
		if (!$this->mRelList || $this->mInvalidateRelList) {
			$this->mInvalidateRelList = false;
			$this->mRelList = new DOMTokenList($this, 'rel');

			if (!empty($this->mRel)) {
				call_user_func_array(array($this->mRelList, 'add'), explode(' ', $this->mRel));
			}
		}

		return $this->mRelList;
	}

	private function updateURL($aValue) {
		$this->_updateAttributeOnPropertyChange('href', $aValue);
	}
}
