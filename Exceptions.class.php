<?php
// https://developer.mozilla.org/en-US/docs/Web/API/DOMException
// https://heycam.github.io/webidl/#idl-exceptions

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

class NotFoundError extends Exception {
	public function __construct() {
		$this->code = 8;
		$this->message = 'The object can not be found here.';
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

class InvalidNodeTypeError extends Exception {
	public function __construct() {
		$this->code = 24;
		$this->message = 'The supplied node is incorrect or has an incorrect ancestor for this operation.';
	}
}

class TypeError extends Exception {
	public function __construct($aMessage = '') {
		$this->message = $aMessage;
	}
}
