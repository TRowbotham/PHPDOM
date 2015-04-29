<?php
require_once 'URLSearchParams.class.php';

class URLUtils implements SplSubject {
	private $mHash;
	private $mHost;
	private $mHostname;
	private $mHref;
	private $mObservers;
	private $mOrigin;
	private $mPassword;
	private $mPathname;
	private $mPort;
	private $mProtocol;
	private $mSearch;
	private $mSearchParams;
	private $mUrl;
	private $mUsername;

	public function __construct() {
		$this->mHash = '';
		$this->mHost = '';
		$this->mHostname = '';
		$this->mHref = '';
		$this->mObservers = new SplObjectStorage();
		$this->mOrigin = '';
		$this->mPassword = '';
		$this->mPathname = '';
		$this->mPort = '';
		$this->mProtocol = '';
		$this->mSearch = '';
		$this->mSearchParams = '';
		$this->mUrl = '';
		$this->mUsername = '';
	}

	public function __get($aName) {
		switch ($aName) {
			case 'hash':
				return $this->getURLComponent('fragment');
			case 'host':
				return $this->getURLComponent('host');
			case 'hostname':
				return $this->getURLComponent('host');
			case 'href':
				return $this->mHref;
			case 'origin':
				return $this->getURLComponent('scheme') . '://' . $this->getURLComponent('host');
			case 'password':
				return $this->getURLComponent('pass');
			case 'pathname':
				$component = $this->getURLComponent('path');

				return $component ? $component : '/';
			case 'port':
				return $this->getURLComponent('port');
			case 'protocol':
				return $this->getURLComponent('scheme') . ':';
			case 'search':
				return $this->getURLSearchParams()->toString();
			case 'searchParams':
				return $this->getURLSearchParams();
			case 'username':
				return $this->getURLComponent('user');
			default:
				return false;
		}
	}

	public function __set($aName, $aValue) {
		switch ($aName) {
			case 'hash':
				$this->mHash = $aValue;

				break;

			case 'host':
				$this->mHost = $aValue;

				break;

			case 'hostname':
				$this->mHostname = $aValue;

				break;

			case 'href':
				$this->mHref = $aValue;

				break;

			case 'origin':
				$this->mOrigin = $aValue;

				break;

			case 'password':
				$this->mPassword = $aValue;

				break;

			case 'pathname':
				$this->mPathname = $aValue;

				break;

			case 'port':
				$this->mPort = $aValue;

				break;

			case 'protocol':
				$this->mProtocol = $aValue;

				break;

			case 'search':
				$this->mSearchParams = new URLSearchParams($aValue);

				break;

			case 'username':
				$this->mUsername = $aValue;

				break;

			default:
				return false;
		}

		$this->notify();
	}

	public function attach(SplObserver $aObserver) {
		$this->mObservers->attach($aObserver);
	}

	public function detach(SplObserver $aObserver) {
		$this->mObservers->detach($aObserver);
	}

	private function getURLComponent($aComponent) {
		if ($this->mUrl !== false && !$this->mUrl) {
			$this->mUrl = parse_url($this->mHref);
		}

		return ($this->mUrl === false || !isset($this->mUrl[$aComponent])) ? '' : $this->mUrl[$aComponent];
	}

	private function getURLSearchParams() {
		if (!$this->mSearchParams) {
			$this->mSearchParams = new URLSearchParams($this->getURLComponent('query'));
		}

		return $this->mSearchParams;
	}

	public function notify() {
		foreach($this->mObservers as $observer) {
			$observer->update($this);
		}
	}
}