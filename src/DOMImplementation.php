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
    protected $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-domimplementation-createdocument
     *
     * @param string $namespace The namespace of the element to be created,
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
        $namespace,
        $qualifiedName,
        DocumentType $doctype = null
    ) {
        $namespace = Utils::DOMString($namespace, false, true);
        $qualifiedName = Utils::DOMString($qualifiedName, true);
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

        if ($doctype) {
            $doc->appendChild($doctype);
        }

        if ($element) {
            $doc->appendChild($element);
        }

        // TODO: document's origin is the origin of the context object's
        // associated document.

        switch ($namespace) {
            case Namespaces::HTML:
                $doc->setContentType('application/xhtml+xml');

                break;

            case Namespaces::SVG:
                $doc->setContentType('image/svg+xml');

                break;

            default:
                $doc->setContentType('application/xml');
        }

        return $doc;
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-domimplementation-createdocumenttype
     *
     * @param string $qualifiedName The document type's name.
     *
     * @param string $publicId The document type's public identifier.
     *
     * @param string $systemId The document type's system identifier.
     *
     * @return DocumentType
     *
     * @throws InvalidCharacterError If the qualified name does not match the
     *     Name production.
     *
     * @throws NamespaceError If the qualified name does not match the QName
     *     production.
     */
    public function createDocumentType($qualifiedName, $publicId, $systemId)
    {
        $qualifiedName = Utils::DOMString($qualifiedName);
        $publicId = Utils::DOMString($publicId);
        $systemId = Utils::DOMString($systemId);

        try {
            Namespaces::validate($qualifiedName);
        } catch (DOMException $e) {
            throw $e;
        }

        $docType = new DocumentType($qualifiedName, $publicId, $systemId);
        $docType->setNodeDocument($this->document);

        return $docType;
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-domimplementation-createhtmldocument
     *
     * @param string $title Optional. The title of the document.
     *
     * @return HTMLDocument
     */
    public function createHTMLDocument($title = null)
    {
        $doc = new HTMLDocument();
        $doc->setContentType('text/html');
        $docType = new DocumentType('html', '', '');
        $docType->setNodeDocument($doc);
        $doc->appendChild($docType);

        $html = ElementFactory::create($doc, 'html', Namespaces::HTML);
        $head = ElementFactory::create($doc, 'head', Namespaces::HTML);
        $doc->appendChild($html)->appendChild($head);

        // Only create a HTMLTitleElement if the user actually provided us with
        // a title to use.
        if (\func_num_args() > 0) {
            $titleNode = ElementFactory::create(
                $doc,
                'title',
                Namespaces::HTML
            );
            $head->appendChild($titleNode);
            $text = new Text(Utils::DOMString($title));
            $text->setNodeDocument($doc);
            $titleNode->appendChild($text);
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
