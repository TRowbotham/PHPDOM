<?php
class URLSearchParams {
	private $mPairs;

	public function __construct($aQueryString = '') {
		$this->mPairs = array();

		$pairs = explode('&', $aQueryString);

		foreach ($pairs as $pair) {
			list($key, $value) = explode('=', $pair);
			$this->append($key, $value);
		}
	}

	public function append($aName, $aValue) {
		$this->mPairs[] = array($aName => $aValue);
	}

	public function delete($aName) {
		if ($this->has($aName)) {
			$arr = array_filter($this->mPairs, function($aKey) use ($aName) {
						return isset($this->mPairs[$aKey][$aName]);
					}, ARRAY_FILTER_USE_KEY);

			foreach ($arr as $key => $value) {
				unset($this->mPairs[$key]);
			}
		}
	}

	public function get($aName) {
		$rv = null;

		for ($i = 0; $i < count($this->mPairs); $i++) {
			if (isset($this->mPairs[$i][$aName])) {
				$rv = $this->mPairs[$i][$aName];
				break;
			}
		}

		return $rv;
	}

	public function getAll($aName) {
		return array_column(array_filter($this->mPairs, function($aKey) use ($aName) {
			return isset($this->mPairs[$aKey][$aName]);
		}, ARRAY_FILTER_USE_KEY), $aName);
	}

	public function has($aName) {
		$rv = false;

		for ($i = 0; $i < count($this->mPairs); $i++) {
			if (isset($this->mPairs[$i][$aName])) {
				$rv = true;
				break;
			}
		}

		return $rv;
	}

	public function set($aName, $aValue) {
		if ($this->has($aName)) {
			$arr = array_filter($this->mPairs, function($aKey) use ($aName) {
						return isset($this->mPairs[$aKey][$aName]);
					}, ARRAY_FILTER_USE_KEY);

			$index = 0;
			$firstRound = true;

			foreach ($arr as $key => $value) {
				if ($firstRound) {
					$index = $key;
					$firstRound = false;
				} else {
					unset($this->mPairs[$key]);
				}
			}

			$this->mPairs[$index][$aName] = $aValue;
		} else {
			$this->append($aName, $aValue);
		}
	}

	public function toString() {
		return $this->__toString();
	}

	public function __toString() {
		$queryString = '';

		foreach ($this->mPairs as $key => $values) {
			foreach ($this->mPairs[$key] as $name => $value) {
				$queryString .= '&' . $name . '=' . $value;
			}
		}

		return empty($this->mPairs) ? $queryString : substr($queryString, 1);
	}
}