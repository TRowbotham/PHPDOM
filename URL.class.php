<?php
// https://developer.mozilla.org/en-US/docs/Web/API/URL
// https://url.spec.whatwg.org/#api

require_once 'URLUtils.class.php';

class URL extends URLUtils {
	public function __construct($aUrlString, $aBaseUrl = null) {
		parent::__construct();
	}

	public static function createObjectURL($aBlob) {
		// TODO
	}

	public static function revokeObjectURL($aObjectURL) {
		// TODO
	}
}