<?php
namespace phpjs;

/**
 * @see https://dom.spec.whatwg.org/#interface-domimplementation
 * @see https://developer.mozilla.org/en-US/docs/Web/API/DOMImplementation
 */
final class DOMImplementation
{
    protected $mDocument;

    public function __construct(Document $aDocument)
    {
        $this->mDocument = $aDocument;
    }

    public function __destruct()
    {
        $this->mDocument = null;
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-domimplementation-createdocument
     *
     * @param string $aNamespace The namespace of the element to be created,
     *     which becomes the document's document element.
     *
     * @param string|null $aQualifiedName The local name of the element that is
     *     to become the document's document element.
     *
     * @param DocumentType|null $aDoctype Optional. A DocumentType object to be
     *     appended to the document.
     *
     * @return XMLDocument
     */
    public function createDocument(
        $aNamespace,
        $aQualifiedName,
        DocumentType $aDoctype = null
    ) {
        $doc = new XMLDocument();
        $element = null;

        if (!empty($aQualifiedName)) {
            try {
                $element = $doc->createElementNS($aNameSpace, $aQualifiedName);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        if ($aDoctype) {
            $doc->appendChild($aDoctype);
        }

        if ($element) {
            $doc->appendChild($element);
        }

        // TODO: document’s origin is an alias to the origin of the context
        // object’s associated document, and document’s effective script origin
        // is an alias to the effective script origin of the context object’s
        // associated document.

        return $doc;
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-domimplementation-createdocumenttype
     *
     * @param string $aQualifiedName The document type's name.
     *
     * @param string $aPublicId The document type's public identifier.
     *
     * @param string $aSystemId The document type's system identifier.
     *
     * @return DocumentType
     *
     * @throws InvalidCharacterError If the qualified name does not match the
     *     Name production.
     *
     * @throws NamespaceError If the qualified name does not match the QName
     *     production.
     */
    public function createDocumentType($aQualifiedName, $aPublicId, $aSystemId)
    {
        try {
            Namespaces::validate($aQualifiedName);
        } catch (\Exception $e) {
            throw $e;
        }

        $docType = new DocumentType($aQualifiedName, $aPublicId, $aSystemId);
        $docType->setOwnerDocument($this->mDocument);

        return $docType;
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-domimplementation-createhtmldocument
     *
     * @param  string|null   $aTitle Optional. The title of the document.
     *
     * @return HTMLDocument
     */
    public function createHTMLDocument($aTitle = null)
    {
        $doc = new HTMLDocument();
        $docType = new DocumentType('html', '', '');
        $docType->setOwnerDocument($doc);
        $doc->appendChild($docType);

        $html = $doc->createElementNS(Namespaces::HTML, 'html');
        $head = $doc->createElementNS(Namespaces::HTML, 'head');
        $doc->appendChild($html)->appendChild($head);

        if (is_string($aTitle)) {
            $title = $doc->createElementNS(Namespaces::HTML, 'title');
            $head->appendChild($title);
            $title->appendChild(new Text($aTitle));
        }

        $html->appendChild($doc->createElementNS(Namespaces::HTML, 'body'));

        // TODO: doc’s origin is an alias to the origin of the context object’s
        // associated document, and doc’s effective script origin is an alias to
        // the effective script origin of the context object’s associated
        // document.

        return $doc;
    }

}
