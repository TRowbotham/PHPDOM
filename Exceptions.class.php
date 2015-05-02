<?php
class IndexSizeError extends Exception {
	public function __construct() {
		$this->code = 1;
		$this->message = 'The index is not in the allowed range.';
	}
}

class HierarchyRequestError extends Exception {
	public function __construct() {
		$this->code = 3;
		$this->message = 'The operation would yield an incorrect node tree.';
	}
}

class WrongDocumentError extends Exception {
	public function __construct() {
		$this->code = 4;
		$this->message = 'The object is in the wrong document.';
	}
}

class InvalidCharacterError extends Exception {
	public function __construct() {
		$this->code = 5;
		$this->message = 'The string contains invalid characters.';
	}
}

class NotSupportedError extends Exception {
	public function __construct() {
		$this->code = 9;
		$this->message = 'The operation is not supported.';
	}
}

class SyntaxError extends Exception {
	public function __construct() {
		$this->code = 12;
		$this->message = 'The string did not match the expected pattern.';
	}
}