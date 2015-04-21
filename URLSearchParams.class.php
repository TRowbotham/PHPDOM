<?php
class URLSearchParams {
	private $mParams;
	private $mIndex;
	private $mSequenceId;

	public function __construct($aSearchParams = '') {
		$this->mIndex = array();
		$this->mParams = array();
		$this->mSequenceId = 0;

		$pairs = explode('&', $aSearchParams);

		foreach ($pairs as $pair) {
			if (strpos($pair, '=') === false) {
				$key = $pair;
				$value = '';
			} else {
				list($key, $value) = explode('=', $pair);
			}

			$this->append($key, $value);
		}
	}

	/**
	 * Appends a new key -> value pair to the end of the query string.
	 * @param  string $aName  The name of the key in the pair.
	 * @param  string $aValue The value assigned to the key.
	 */
	public function append($aName, $aValue) {
		$this->mIndex[$this->mSequenceId] = $aName;
		$this->mParams[$aName][$this->mSequenceId++] = $aValue;
	}

	/**
	 * Deletes all occurances of pairs with the specified key name.
	 * @param  string $aName The name of the key to delete.
	 */
	public function delete($aName) {
		foreach ($this->mParams[$aName] as $key) {
			unset($this->mIndex[$key]);
		}

		unset($this->mParams[$aName]);
	}

	/**
	 * Get the value of the first key -> value pair with the specified key name.
	 * @param  string $aName The name of the key whose value you want to retrive.
	 * @return string        The value of the specified key.
	 */
	public function get($aName) {
		return $this->has($aName) ? reset($this->mParams[$aName]) : null;
	}

	/**
	 * Gets all key -> value pairs that has the specified key name.
	 * @param  string $aName The name of the key whose values you want to retrieve.
	 * @return array         An array containing all the values of the specified key.
	 */
	public function getAll($aName) {
		return $this->has($aName) ? array_values($this->mParams[$aName]) : array();
	}

	/**
	 * Indicates whether or not a query string contains any keys with the specified key name.
	 * @param  boolean  $aName The key name you want to test if it exists.
	 * @return boolean         Returns true if the key exits, otherwise false.
	 */
	public function has($aName) {
		return isset($this->mParams[$aName]);
	}

	/**
	 * Sets the value of the specified key name.  If multiple pairs exist with the same key name
	 * it will set the value for the first occurance of the key in the query string and all other
	 * occurances will be removed from the query string.  If the key does not already exist in the
	 * query string, it will be added to the end of the query string.
	 * @param string $aName  The name of the key you want to modify the value of.
	 * @param string $aValue The value you want to associate with the key name.
	 */
	public function set($aName, $aValue) {
		if ($this->has($aName)) {
			for ($i = count($this->mParams[$aName]) - 1; $i > 0; $i--) {
				end($this->mParams[$aName]);
				unset($this->mIndex[key($this->mParams[$aName])]);
				array_pop($this->mParams[$aName]);
			}

			reset($this->mParams[$aName]);
			$this->mParams[$aName][key($this->mParams[$aName])] = $aValue;
		} else {
			$this->append($aName, $aValue);
		}
	}

	/**
	 * Returns all key -> value pairs stringified in the correct order.
	 * @return string The query string.
	 */
	public function toString() {
		$queryString = '';

		foreach ($this->mIndex as $sequenceId => $name) {
			$queryString .= '&' . $name . '=' . $this->mParams[$name][$sequenceId];
		}

		return $queryString ? substr($queryString, 1) : $queryString;
	}
}