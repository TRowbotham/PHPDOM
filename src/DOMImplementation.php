<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Exception\DOMException;

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
        $namespace = Utils::DOMString($aNamespace, false, true);
        $qualifiedName = Utils::DOMString($aQualifiedName, true);
        $doc = new XMLDocument();
        $element = null;

        if ($qualifiedName !== '') {
            try {
                $element = ElementFactory::createNS(
                    $doc,
                    $namespace,
                    $qualifiedName
                );
            } catch (DOMException $e) {
                throw $e;
            }
        }

        if ($aDoctype) {
            $doc->appendChild($aDoctype);
        }

        if ($element) {
            $doc->appendChild($element);
        }

        // TODO: document's origin is the origin of the context object's
        // associated document.

        switch ($namespace) {
            case Namespaces::HTML:
                $doc->_setContentType('application/xhtml+xml');

                break;

            case Namespaces::SVG:
                $doc->_setContentType('image/svg+xml');

                break;

            default:
                $doc->_setContentType('application/xml');
        }

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
        $qualifiedName = Utils::DOMString($aQualifiedName);
        $publicId = Utils::DOMString($aPublicId);
        $systemId = Utils::DOMString($aSystemId);

        try {
            Namespaces::validate($aQualifiedName);
        } catch (DOMException $e) {
            throw $e;
        }

        $docType = new DocumentType($aQualifiedName, $aPublicId, $aSystemId);
        $docType->setNodeDocument($this->mDocument);

        return $docType;
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-domimplementation-createhtmldocument
     *
     * @param string $aTitle Optional. The title of the document.
     *
     * @return HTMLDocument
     */
    public function createHTMLDocument($aTitle = null)
    {
        $doc = new HTMLDocument();
        $doc->_setContentType('text/html');
        $docType = new DocumentType('html', '', '');
        $docType->setNodeDocument($doc);
        $doc->appendChild($docType);

        $html = ElementFactory::create($doc, 'html', Namespaces::HTML);
        $head = ElementFactory::create($doc, 'head', Namespaces::HTML);
        $doc->appendChild($html)->appendChild($head);

        // Only create a HTMLTitleElement if the user actually provided us with
        // a title to use.
        if (func_num_args() > 0) {
            $title = ElementFactory::create($doc, 'title', Namespaces::HTML);
            $head->appendChild($title);
            $text = new Text(Utils::DOMString($aTitle));
            $text->setNodeDocument($doc);
            $title->appendChild($text);
        }

        $html->appendChild(ElementFactory::create(
            $doc,
            'body',
            Namespaces::HTML
        ));

        // TODO: doc's origin is the origin of the context object's associated
        // document

        return $doc;
    }

}
