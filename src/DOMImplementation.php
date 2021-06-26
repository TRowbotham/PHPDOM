<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Exception\InvalidCharacterError;

use function func_num_args;
use function preg_match;

/**
 * @see https://dom.spec.whatwg.org/#interface-domimplementation
 * @see https://developer.mozilla.org/en-US/docs/Web/API/DOMImplementation
 */
final class DOMImplementation
{
    /**
     * @var \Rowbot\DOM\Document
     */
    private $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * Creates a XML Document.
     *
     * @see https://dom.spec.whatwg.org/#dom-domimplementation-createdocument
     *
     * @param string|null                   $namespace     The namespace of the element to be created, which becomes the
     *                                                     document's document element.
     * @param string|null                   $qualifiedName The local name of the element that is to become the
     *                                                     document's document element.
     * @param \Rowbot\DOM\DocumentType|null $doctype       (optional) A DocumentType object to be appended to the
     *                                                     document.
     *
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError
     * @throws \Rowbot\DOM\Exception\NamespaceError
     */
    public function createDocument(
        ?string $namespace,
        ?string $qualifiedName,
        DocumentType $doctype = null
    ): XMLDocument {
        $env = new Environment();
        $env->setScriptingEnabled($this->document->getEnvironment()->isScriptingEnabled());
        $document = new XMLDocument($env);
        $element = null;

        if ($qualifiedName === null) {
            $qualifiedName = '';
        }

        if ($qualifiedName !== '') {
            $element = ElementFactory::createNS($document, $namespace, $qualifiedName);
        }

        if ($doctype !== null) {
            $document->appendChild($doctype);
        }

        if ($element !== null) {
            $document->appendChild($element);
        }

        // TODO: document's origin is the origin of the context object's
        // associated document.

        switch ($namespace) {
            case Namespaces::HTML:
                $document->setContentType('application/xhtml+xml');

                break;

            case Namespaces::SVG:
                $document->setContentType('image/svg+xml');

                break;

            default:
                $document->setContentType('application/xml');
        }

        return $document;
    }

    /**
     * Creates a document type node.
     *
     * @see https://dom.spec.whatwg.org/#dom-domimplementation-createdocumenttype
     *
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError If the qualified name does not match the QName production.
     */
    public function createDocumentType(
        string $qualifiedName,
        string $publicId,
        string $systemId
    ): DocumentType {
        if (preg_match(Namespaces::QNAME, $qualifiedName) !== 1) {
            throw new InvalidCharacterError();
        }

        return new DocumentType($this->document, $qualifiedName, $publicId, $systemId);
    }

    /**
     * Creates a HTML document.
     *
     * @see https://dom.spec.whatwg.org/#dom-domimplementation-createhtmldocument
     */
    public function createHTMLDocument(string $title = ''): HTMLDocument
    {
        $env = new Environment(null, 'text/html');
        $env->setScriptingEnabled($this->document->getEnvironment()->isScriptingEnabled());
        $doc = new HTMLDocument($env);
        $docType = new DocumentType($doc, 'html', '', '');
        $doc->appendChild($docType);

        $html = ElementFactory::create($doc, 'html', Namespaces::HTML);
        $head = ElementFactory::create($doc, 'head', Namespaces::HTML);
        $doc->appendChild($html)->appendChild($head);

        // Only create a HTMLTitleElement if the user actually provided us with
        // a title to use.
        if (func_num_args() > 0) {
            $titleNode = ElementFactory::create($doc, 'title', Namespaces::HTML);
            $head->appendChild($titleNode);
            $titleNode->appendChild(new Text($doc, $title));
        }

        $html->appendChild(ElementFactory::create($doc, 'body', Namespaces::HTML));

        // TODO: doc's origin is the origin of the context object's associated
        // document

        return $doc;
    }
}
