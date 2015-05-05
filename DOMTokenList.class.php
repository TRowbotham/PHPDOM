<?php
// https://developer.mozilla.org/en-US/docs/Web/API/DOMTokenList
// https://dom.spec.whatwg.org/#interface-domtokenlist

class DOMTokenList implements SplSubject {
	private $mObservers;
	private $mTokens;

	public function __construct() {
		$this->mObservers = new SplObjectStorage();
		$this->mTokens = [];
	}

	public function add($aTokens) {
		if (!func_num_args()) {
			return;
		}

		$tokens = func_get_args();
		array_walk($tokens, array($this, 'checkToken'));

		foreach ($tokens as $token) {
			if (!$this->contains($token)) {
				$this->mTokens[] = $token;
			}
		}

		$this->notify();
	}

	public function attach(SplObserver $aObserver) {
		$this->mObservers->attach($aObserver);
	}

	public function contains($aToken) {
		$this->checkToken($aToken);

		return in_array($aToken, $this->mTokens);
	}

	public function detach(SplObserver $aObserver) {
		$this->mObservers->detach($aObserver);
	}

	public function item($aIndex) {
		$rv = null;

		if ($aIndex >= 0 && $aIndex < count($this->mTokens)) {
			$rv = $this->mTokens[$aIndex];
		}

		return $rv;
	}

	public function remove($aTokens) {
		if (!func_num_args()) {
			return;
		}

		$tokens = func_get_args();
		array_walk($tokens, array($this, 'checkToken'));

		foreach ($tokens as $token) {
			$key = array_search($token, $this->mTokens);
			array_splice($this->mTokens, $key, 1);
		}

		$this->notify();
	}

	public function toggle($aToken, $aForce = null) {
		$contains = $this->contains($aToken);

		if ($contains) {
			if (!$aForce) {
				$this->remove($aToken);
				$rv = false;
			} else {
				$rv = true;
			}
		} else {
			if ($aForce === false) {
				$rv = false;
			} else {
				$this->add($aToken);
				$rv = true;
			}
		}

		$this->notify();

		return $rv;
	}

	public function __toString() {
		return implode(' ', $this->mTokens);
	}

	private function checkToken($aToken) {
		if (empty($aToken)) {
			throw new SyntaxError;
		}

		if (preg_match('/^\s+|\s{2,}|\s+$/', $aToken)) {
			throw new InvalidCharacterError;
		}
	}

	public function notify() {
		foreach($this->mObservers as $observer) {
			$observer->update($this);
		}
	}
}