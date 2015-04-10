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
			list($key, $value) = explode('=', $pair);
			$this->append($key, $value);
		}
	}

	public function append($aName, $aValue) {
		$this->mIndex[$this->mSequenceId] = $aName;
		$this->mParams[$aName][$this->mSequenceId++] = $aValue;
	}

	public function delete($aName) {
		unset($this->mParams[$aName]);
	}

	public function get($aName) {
		return $this->has($aName) ? reset($this->mParams[$aName]) : null;
	}

	public function getAll($aName) {
		return $this->has($aName) ? array_values($this->mParams[$aName]) : array();
	}

	public function has($aName) {
		return isset($this->mParams[$aName]);
	}

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

	public function toString() {
		$queryString = '';

		foreach ($this->mIndex as $sequenceId => $name) {
			$queryString .= '&' . $name . '=' . $this->mParams[$name][$sequenceId];
		}

		return $queryString ? substr($queryString, 1) : $queryString;
	}
}