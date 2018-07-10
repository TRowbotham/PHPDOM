<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Exception\DOMException;

use function func_num_args;

/**
 * @see https://dom.spec.whatwg.org/#interface-domimplementation
 * @see https://developer.mozilla.org/en-US/docs/Web/API/DOMImplementation
 */
final class DOMImplementation
{
    /**
     * @var \Rowbot\DOM\Document
     */
    protected $document;

    /**
     * Constructor.
     *
     * @param \Rowbot\DOM\Document $document
     *
     * @return void
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * Creates a XML Document.
     *
     * @see https://dom.spec.whatwg.org/#dom-domimplementation-createdocument
     *
     * @param string                        $namespace     The namespace of the element to be created, which becomes the
     *                                                     document's document element.
     * @param string|null                   $qualifiedName The local name of the element that is to become the
     *                                                     document's document element.
     * @param \Rowbot\DOM\DocumentType|null $doctype       (optional) A DocumentType object to be appended to the
     *                                                     document.
     *
     * @return \Rowbot\DOM\XMLDocument
     *
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError
     * @throws \Rowbot\DOM\Exception\NamespaceError
     */
    public function createDocument(
        ?string $namespace,
        $qualifiedName,
        DocumentType $doctype = null
    ) {
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
     * Creates a document type node.
     *
     * @see https://dom.spec.whatwg.org/#dom-domimplementation-createdocumenttype
     *
     * @param string $qualifiedName The document type's name.
     * @param string $publicId      The document type's public identifier.
     * @param string $systemId      The document type's system identifier.
     *
     * @return \Rowbot\DOM\DocumentType
     *
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError If the qualified name does not match the Name production.
     * @throws \Rowbot\DOM\Exception\NamespaceError        If the qualified name does not match the QName production.
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
     * Creates a HTML document.
     *
     * @see https://dom.spec.whatwg.org/#dom-domimplementation-createhtmldocument
     *
     * @param string $title (optional) The title of the document.
     *
     * @return \Rowbot\DOM\HTMLDocument
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
        if (func_num_args() > 0) {
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
