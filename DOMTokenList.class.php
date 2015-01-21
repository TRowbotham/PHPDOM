<?php
class DOMTokenList implements SplSubject {
	private $mObservers;
	private $mTokens;

	public function __construct() {
		$this->mObservers = new SplObjectStorage();
		$this->mTokens = [];
	}

	public function add( $aToken ) {
		$this->checkToken($aToken);

		$tokens = explode(' ', $aToken);

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

	public function contains( $aToken ) {
		$this->checkToken($aToken);

		return in_array($aToken, $this->mTokens);
	}

	public function detach(SplObserver $aObserver) {
		$this->mObservers->detach($aObserver);
	}

	public function item( $aIndex ) {
		$rv = null;

		if ($aIndex >= 0 && $aIndex < count($this->mTokens)) {
			$rv = $this->mTokens[$aIndex];
		}

		return $rv;
	}

	public function remove( $aToken ) {
		$this->checkToken($aToken);

		$tokens = explode(' ', $aToken);

		foreach ($tokens as $key => $token) {
			array_splice($this->mTokens, $key, 1);
		}

		$this->notify();
	}

	public function toggle( $aToken, $aForce ) {
		$contains = $this->contains($aToken);

		if ($contains) {
			if ($contains) {
				if ($aForce !== true) {
					$this->remove($aToken);
				}
			} else {
				if ($aForce !== false) {
					$this->add($aToken);
				}
			}
		}

		$this->notify();

		return !$contains;
	}

	public function __toString() {
		return implode(' ', $this->mTokens);
	}

	private function checkToken( $aToken ) {
		if (empty($aToken)) {
			throw new DOMException('SyntaxError: The string did not match the expected pattern.');
		}

		if (preg_match('/^\s+|\s{2,}|\s+$/', $aToken)) {
			throw new DOMException('InvalidCharacterError: 	The string contains invalid characters. ');
		}
	}

	public function notify() {
		foreach($this->mObservers as $observer) {
			$observer->update($this);
		}
	}
}