<?php
require_once 'HTMLDocument.class.php';
require_once 'DocumentType.class.php';

class iDOMImplementation {

	public function __construct() {

	}

	public function createDocument() {
		return new Document();
	}

	public function createDocumentType($aQualifiedName = null, $aPublicId = null, $aSystemId = null) {
		return new DocumentType($aQualifiedName, $aPublicId, $aSystemId);
	}

	public function createHTMLDocument( $aTitle = null ) {
		$doc = new HTMLDocument($aTitle);
		$dict = new CustomEventInit();
		$dict->bubbles = true;
		$dict->cancelable = true;
		$dict->detail = new DocumentType('html');

		$e = new CustomEvent('doctypeChange', $dict);
		$doc->addEventListener('doctypeChange', $doc);
		$doc->dispatchEvent($e);

		return $doc;
	}

}