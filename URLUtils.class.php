<?php
// https://developer.mozilla.org/en-US/docs/Web/API/URLUtils
// https://url.spec.whatwg.org/#urlutils
require_once 'URL.class.php';
require_once 'URLUtilsReadOnly.class.php';

trait URLUtils {
	use URLUtilsReadOnly;

	protected $mObservers;

	private function initURLUtils() {
		$this->initURLUtilsReadOnly();
		$this->mObservers = new SplObjectStorage();
	}

	private function URLUtilsSetter($aName, $aValue) {
		switch ($aName) {
			case 'hash':
				if (!$this->mUrl || $this->mUrl->mScheme == 'javascript') {
					return;
				}

				if ($aValue === '') {
					$this->mUrl->mFragment = null;
					$this->preupdate();
					return;
				}

				$input = $aValue[0] == '#' ? substr($aValue, 1) : $aValue;
				$this->mUrl->mFragment = '';
				URLParser::basicURLParser($input, null, null, $this->mUrl, URLParser::STATE_FRAGMENT);
				$this->preupdate();

				break;

			case 'host':
				if ($this->mUrl === null || !($this->mUrl->mFlags & URL::FLAG_RELATIVE)) {
					return;
				}

				URLParser::basicURLParser($aValue, null, null, $this->mUrl, URLParser::STATE_HOST);
				$this->preupdate();

				break;

			case 'hostname':
				if ($this->mUrl === null || !($this->mUrl->mFlags & URL::FLAG_RELATIVE)) {
					return;
				}

				URLParser::basicURLParser($aValue, null, null, $this->mUrl, URLParser::STATE_HOSTNAME);
				$this->preupdate();

				break;

			case 'href':
				$input = $aValue;

				if ($this instanceof URL) {
					$parsedUrl = URLParser::basicURLParser($input, $this->getBaseURL());

					if ($parsedUrl === false) {
						throw new TypeError('Failed to parse URL.');
					}

					$this->setURLInput('', $parsedUrl);
				} else {
					$this->setURLInput($input);
					$this->preupdate($input);
				}

				break;

			case 'password':
				if ($this->mUrl === null || !($this->mUrl->mFlags & URL::FLAG_RELATIVE)) {
					return;
				}

				if ($aValue === '') {
					$this->mUrl->mPassword = null;
				} else {
					$this->mUrl->mPassword = '';

					for ($i = 0; $i < strlen($aValue); $i++) {
						$this->mUrl->mPassword .= URLParser::utf8PercentEncode($aValue[$i]);
					}
				}

				break;

			case 'pathname':
				if ($this->mUrl === null || !($this->mUrl->mFlags & URL::FLAG_RELATIVE)) {
					return;
				}

				while (!$this->mUrl->mPath->isEmpty()) {
					$this->mUrl->mPath->pop();
				}

				URLParser::basicURLParser($aValue, null, null, $this->mUrl, URLParser::STATE_RELATIVE_PATH_START);
				$this->preupdate();

				break;

			case 'port':
				if ($this->mUrl === null || !($this->mUrl->mFlags & URL::FLAG_RELATIVE) || $this->mUrl->mScheme == 'file') {
					return;
				}

				URLParser::basicURLParser($aValue, null, null, $this->mUrl, URLParser::STATE_PORT);
				$this->preupdate();

				break;

			case 'protocol':
				if ($this->mUrl === null) {
					return;
				}

				URLParser::basicURLParser($aValue . ':', null, null, $this->mUrl, URLParser::STATE_SCHEME_START);
				$this->preupdate();

				break;

			case 'search':
				if ($this->mUrl === null) {
					return;
				}

				if ($aValue === '') {
					$this->mUrl->mQuery = null;
					$this->mSearchParams->_mutateList();
					$this->mSearchParams->notify();

					return;
				}

				$input = $aValue[0] == '?' ? substr($aValue, 1) : $aValue;
				$this->mUrl->mQuery = '';
				URLParser::basicURLParser($input, null, null, $this->mUrl, URLParser::STATE_QUERY, null);
				$pairs = URLParser::urlencodedStringParser($input);
				$this->mSearchParams->_mutateList($pairs);
				$this->mSearchParams->notify();

				break;

			case 'searchParams':
				$object = $aValue;
				$this->mSearchParams->detach($this);
				$object->attach($this);
				$this->mSearchParams = $object;
				$this->mUrl->mQuery = $this->mSearchParams->toString();
				$this->preupdate();

				break;

			case 'username':
				if ($this->mUrl === null || !($this->mUrl->mFlags & URL::FLAG_RELATIVE)) {
					return;
				}

				$this->mUsername = '';

				for ($i = 0; $i < strlen($aValue); $i++) {
					$this->mUrl->mUsername .= URLParser::utf8PercentEncode($aValue[$i], URLParser::ENCODE_SET_USERNAME);
				}

				$this->preupdate();

				break;

			default:
				if (property_exists($this, $aName)) {
					$this->$aName = $aValue;
				}
		}
	}

	private function preupdate($aValue = null) {
		$value = $aValue;

		if ($value === null) {
			$value = URLParser::serializeURL($this->mUrl);
		}

		$this->updateURL($value);
	}

	abstract protected function updateURL($aValue);
	abstract protected function getBaseURL();
}
