<?php
// https://developer.mozilla.org/en-US/docs/Web/API/DOMTokenList
// https://dom.spec.whatwg.org/#interface-domtokenlist

class DOMTokenList implements ArrayAccess, SplSubject {
	private $mLength;
	private $mObservers;
	private $mTokens;

	public function __construct() {
		$this->mLength = 0;
		$this->mObservers = new SplObjectStorage();
		$this->mTokens = [];
	}

	public function __get($aName) {
		switch ($aName) {
			case 'length':
				return $this->mLength;
		}
	}

	/**
	 * Adds all arguments to the token list except for those that
	 * already exist in the list.  A SyntaxError will be thrown if
	 * one of the tokens is an empty string and a InvalidCharacterError
	 * will be thrown if one of the tokens contains ASCII whitespace.
	 * @param string $aTokens... One or more tokens to be added to the token
	 *                           list.
	 */
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

		$this->mLength = count($this->mTokens);
		$this->notify();
	}

	/**
	 * Attach an observer to this object to be notified when changes occur.
	 * @param  SplObserver $aObserver The object to be notified of changes.
	 */
	public function attach(SplObserver $aObserver) {
		$this->mObservers->attach($aObserver);
	}

	/**
	 * Returns true if the token is present, and false otherwise.
	 * A SyntaxError will be thrown if one of the tokens is an empty string
	 * and a InvalidCharacterError will be thrown if one of the tokens contains
	 * ASCII whitespace.
	 * @param  string $aToken A token to check against the token list
	 * @return bool           Returns true if the token is present, and false
	 *                        otherwise.
	 */
	public function contains($aToken) {
		$this->checkToken($aToken);

		return in_array($aToken, $this->mTokens);
	}

	/**
	 * Remove an observer from the list of observers so that it no longer
	 * receives notifications from this object.
	 * @param  SplObserver $aObserver The observer object to be removed.
	 */
	public function detach(SplObserver $aObserver) {
		$this->mObservers->detach($aObserver);
	}

	/**
	 * Returns the token at the specified index.
	 * @param  int    $aIndex An integer index.
	 * @return string         The token at the specified index or null if the
	 *                        index does not exist.
	 */
	public function item($aIndex) {
		return isset($this->mTokens[$aIndex]) ? $this->mTokens[$aOffset] : null;
	}

	/**
	 * Used with isset() to check if there is a token at the specified index
	 * location.
	 * @param  int  $aOffset An integer index.
	 * @return bool          Returns true if the index exists in the token list,
	 *                       otherwise false.
	 */
	public function offsetExists($aOffset) {
		return isset($this->mTokens[$aOffset]);
	}

	/**
	 * Returns the token at the specified index.
	 * @param  int 	  $aOffset An integer index.
	 * @return string          The token at the specified index or null if the
	 *                         index does not exist.
	 */
	public function offsetGet($aOffset) {
		return $this->item($aOffset);
	}

	/**
	 * Tokens cannont be created or their values modified using array notation.
	 */
	public function offsetSet($aOffset, $aValue) {}
	public function offsetUnset($aOffset) {}

	/**
	 * Removes all arguments if they are present in the token list.
	 * A SyntaxError will be thrown if one of the tokens is an empty string
	 * and a InvalidCharacterError will be thrown if one of the tokens contains
	 * ASCII whitespace.
	 * @param  string $aTokens... One or more tokens to be removed.
	 */
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

		$this->mLength = count($this->mTokens);
		$this->notify();
	}

	/**
	 * Adds or removes a token from the list based on whether or not it is
	 * presently in the list.  A SyntaxError will be thrown if one of the tokens
	 * is an empty string and a InvalidCharacterError will be thrown if one of
	 * the tokens contains ASCII whitespace.
	 * @param  string $aToken The token to be toggled.
	 * @param  bool   $aForce If the token is present and $aForce is null or
	 *                        false, the token is removed from the list.  If the
	 *                        token is not present and $aForce is null or true,
	 *                        the token is added to the list.
	 * @return bool           Returns true if the token is present in the list,
	 *                        otherwise false.
	 */
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

	/**
	 * Returns the list of tokens as a string with each token separated by a
	 * space.
	 * @return string Stringified token list.
	 */
	public function __toString() {
		return implode(' ', $this->mTokens);
	}

	/**
	 * Takes a single token and checks to make sure it is not an empty string
	 * and does not contain any ASCII whitespace characters.
	 * @param  string $aToken A token to validate.
	 */
	private function checkToken($aToken) {
		if (empty($aToken)) {
			throw new SyntaxError;
		}

		if (preg_match('/\s/', $aToken)) {
			throw new InvalidCharacterError;
		}
	}

	/**
	 * Notifies all observers when a change occurs.
	 */
	public function notify() {
		foreach($this->mObservers as $observer) {
			$observer->update($this);
		}
	}
}