<?php
// https://developer.mozilla.org/en-US/docs/Web/API/DOMImplementation
// https://dom.spec.whatwg.org/#interface=domimplementation
namespace phpjs;

require_once 'HTMLDocument.class.php';
require_once 'DocumentType.class.php';

class iDOMImplementation {

    public function __construct() {

    }

    public function createDocument($aNameSpace, $aQualifiedName = '', $aDoctype = null) {
        return new Document();
    }

    public function createDocumentType($aQualifiedName, $aPublicId, $aSystemId) {
        return new DocumentType($aQualifiedName, $aPublicId, $aSystemId);
    }

    public function createHTMLDocument($aTitle = '') {
        $doc = new HTMLDocument();
        $doc->title = $aTitle;

        return $doc;
    }

}
