<?php
class URLUtils {
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
	private $mUsername;

	public function __construct() {

	}

	public function toString() {
		return $this->mHref;
	}
}