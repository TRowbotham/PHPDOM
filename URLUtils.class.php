<?php
require_once 'URLSearchParams.class.php';

trait URLUtils {
	private $mHash;
	private $mHost;
	private $mHostname;
	private $mHref;
	private $mOrigin;
	private $mPassword;
	private $mPathname;
	private $mPort;
	private $mProtocol;
	private $mSearch;
	private $mSearchParams;
	private $mUrl;
	private $mUsername;

	public function URLUtilsGet($aName) {
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
				return $this->getURLComponent('query');
			case 'searchParams':
				if (!$this->mSearchParams) {
					$this->mSearchParams = new URLSearchParams($this->getURLComponent('query'));
				}

				return $this->mSearchParams;
			case 'username':
				return $this->getURLComponent('user');
			default:
				return false;
		}
	}

	public function URLUtilsSet($aName, $aValue) {
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
				$this->mSearch = $aValue;

				break;

			case 'username':
				$this->mUsername = $aValue;

				break;
		}
	}

	private function getURLComponent($aComponent) {
		if ($this->mUrl !== false && !$this->mUrl) {
			$this->mUrl = parse_url($this->mHref);
		}

		return ($this->mUrl === false || !isset($this->mUrl[$aComponent])) ? '' : $this->mUrl[$aComponent];
	}
}