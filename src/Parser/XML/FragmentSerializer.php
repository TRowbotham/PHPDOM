<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\XML;

use Rowbot\DOM\Attr;
use Rowbot\DOM\Comment;
use Rowbot\DOM\Document;
use Rowbot\DOM\DocumentFragment;
use Rowbot\DOM\DocumentType;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\InvalidStateError;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Parser\Exception\ParserException;
use Rowbot\DOM\Parser\FragmentSerializerInterface;
use Rowbot\DOM\ProcessingInstruction;
use Rowbot\DOM\Text;
use Throwable;

use function htmlspecialchars;
use function mb_strpos;
use function mb_substr;
use function md5;
use function preg_match;
use function str_replace;
use function strcasecmp;

class FragmentSerializer implements FragmentSerializerInterface
{
    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-xml-serialization
     *
     * @throws \Rowbot\DOM\Exception\InvalidStateError
     */
    public function serializeFragment(Node $node, bool $requireWellFormed): string
    {
        $namespace = null;
        $prefixMap = new NamespacePrefixMap();
        $prefixMap->add(Namespaces::XML, 'xml');
        $prefixIndex = 1;

        try {
            return $this->serializeNode(
                $node,
                $namespace,
                $prefixMap,
                $prefixIndex,
                $requireWellFormed
            );
        } catch (Throwable $e) {
            throw new InvalidStateError('', $e);
        }
    }

    /**
     * Runs the steps to serialize a node.
     *
     * @see https://w3c.github.io/DOM-Parsing/#dfn-concept-xml-serialization-algorithm
     */
    private function serializeNode(
        Node $node,
        ?string $namespace,
        NamespacePrefixMap $prefixMap,
        int &$prefixIndex,
        bool $requireWellFormed
    ): string {
        if ($node instanceof Element) {
            return $this->serializeElementNode(
                $node,
                $namespace,
                $prefixMap,
                $prefixIndex,
                $requireWellFormed
            );
        } elseif ($node instanceof Document) {
            return $this->serializeDocumentNode(
                $node,
                $namespace,
                $prefixMap,
                $prefixIndex,
                $requireWellFormed
            );
        } elseif ($node instanceof Comment) {
            return $this->serializeCommentNode(
                $node,
                $namespace,
                $prefixMap,
                $prefixIndex,
                $requireWellFormed
            );
        } elseif ($node instanceof Text) {
            return $this->serializeTextNode(
                $node,
                $namespace,
                $prefixMap,
                $prefixIndex,
                $requireWellFormed
            );
        } elseif ($node instanceof DocumentFragment) {
            return $this->serializeDocumentFragmentNode(
                $node,
                $namespace,
                $prefixMap,
                $prefixIndex,
                $requireWellFormed
            );
        } elseif ($node instanceof DocumentType) {
            return $this->serializeDocumentTypeNode(
                $node,
                $namespace,
                $prefixMap,
                $prefixIndex,
                $requireWellFormed
            );
        } elseif ($node instanceof ProcessingInstruction) {
            return $this->serializeProcessingInstructionNode(
                $node,
                $namespace,
                $prefixMap,
                $prefixIndex,
                $requireWellFormed
            );
        } elseif ($node instanceof Attr) {
            return '';
        }

        throw new TypeError();
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#xml-serializing-an-element-node
     */
    private function serializeElementNode(
        Element $node,
        ?string $namespace,
        NamespacePrefixMap $prefixMap,
        int &$prefixIndex,
        bool $requireWellFormed
    ): string {
        $localName = $node->localName;

        if (
            $requireWellFormed
            && (
                mb_strpos($localName, ':', 0, 'utf-8') !== false
                || !preg_match(Namespaces::NAME_PRODUCTION, $localName)
            )
        ) {
            throw new ParserException();
        }

        $markup = '<';
        $qualifiedName = '';
        $skipEndTag = false;
        $ignoreNamespaceDefinitionAttribute = false;
        $map = clone $prefixMap;
        $localPrefixesMap = [];
        $localDefaultNamespace = $this->recordNamespaceInformation(
            $node,
            $map,
            $localPrefixesMap
        );
        $inheritedNS = $namespace;
        $ns = $node->namespaceURI;

        if ($inheritedNS === $ns) {
            if ($localDefaultNamespace !== null) {
                $ignoreNamespaceDefinitionAttribute = true;
            }

            if ($ns === Namespaces::XML) {
                $qualifiedName .= 'xml:';
            }

            $qualifiedName .= $localName;
            $markup .= $qualifiedName;
        } elseif ($inheritedNS !== $ns) {
            $prefix = $node->prefix;
            // This may return null if no namespace key exists in the map.
            $candidatePrefix = $map->preferredPrefix($ns, $prefix);

            if ($prefix === 'xmlns') {
                // An Element with prefix "xmlns" will not legally round-trip
                if ($requireWellFormed) {
                    throw new ParserException();
                }

                $candidatePrefix = $prefix;
            }

            // "Found a suitable namespace prefix"
            if ($candidatePrefix !== null) {
                $qualifiedName .= $candidatePrefix . ':' . $localName;

                if ($localDefaultNamespace !== null && $localDefaultNamespace !== Namespaces::XML) {
                    $inheritedNS = $localDefaultNamespace === ''
                        ? null
                        : $localDefaultNamespace;
                    $markup .= $qualifiedName;
                }
            } elseif ($prefix !== null) {
                if (isset($localPrefixesMap[$prefix])) {
                    $prefix = $this->generatePrefix($map, $ns, $prefixIndex);
                }

                $map->add($ns, $prefix);
                $qualifiedName .= $prefix . ':' . $localName;
                $markup .= $qualifiedName;
                $markup .= ' ';
                $markup .= 'xmlns:';
                $markup .= $prefix;
                $markup .= '="';
                $markup .= $this->serializeAttributeValue($ns, $requireWellFormed);
                $markup .= '"';

                if ($localDefaultNamespace !== null) {
                    $inheritedNS = $localDefaultNamespace === ''
                        ? null
                        : $localDefaultNamespace;
                }
            } elseif (
                $localDefaultNamespace === null
                || ($localDefaultNamespace !== null && $localDefaultNamespace !== $ns)
            ) {
                $ignoreNamespaceDefinitionAttribute = true;
                $qualifiedName .= $localName;
                $inheritedNS = $ns;
                $markup .= $qualifiedName;
                $markup .= ' ';
                $markup .= 'xmlns:';
                $markup .= '="';
                $markup .= $this->serializeAttributeValue(
                    $ns,
                    $requireWellFormed
                );
                $markup .= '"';
            } else {
                $qualifiedName .= $localName;
                $inheritedNS = $ns;
                $markup .= $qualifiedName;
            }
        }

        $markup .= $this->serializeAttributes(
            $node,
            $map,
            $prefixIndex,
            $localPrefixesMap,
            $ignoreNamespaceDefinitionAttribute,
            $requireWellFormed
        );

        if (
            $ns === Namespaces::HTML
            && !$node->hasChildNodes()
            && isset(self::VOID_ELEMENTS[$localName])
        ) {
            $markup .= ' /';
            $skipEndTag = true;
        } elseif ($ns !== Namespaces::HTML && !$node->hasChildNodes()) {
            $markup .= '/';
            $skipEndTag = true;
        }

        $markup .= '>';

        if ($skipEndTag) {
            return $markup;
        }

        if ($ns === Namespaces::HTML && $localName === 'template') {
            $markup .= $this->serializeDocumentFragmentNode(
                $node->content,
                $inheritedNS,
                $map,
                $prefixIndex,
                $requireWellFormed
            );
        } else {
            foreach ($node->childNodes as $child) {
                $markup .= $this->serializeNode(
                    $child,
                    $inheritedNS,
                    $map,
                    $prefixIndex,
                    $requireWellFormed
                );
            }
        }

        $markup .= '</';
        $markup .= $qualifiedName;
        $markup .= '>';

        return $markup;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-recording-the-namespace-information
     *
     * @param array<string, string> $localPrefixesMap
     */
    private function recordNamespaceInformation(
        Element $element,
        NamespacePrefixMap $map,
        array &$localPrefixesMap
    ): ?string {
        $defaultNamespaceAttrValue = null;

        foreach ($element->getAttributeList() as $attr) {
            $attributeNamespace = $attr->namespaceURI;
            $attributePrefix = $attr->prefix;

            if ($attributeNamespace === Namespaces::XMLNS) {
                if ($attributePrefix === null) {
                    $defaultNamespaceAttrValue = $attr->value;

                    continue;
                }

                $prefixDefinition = $attr->localName;
                $namespaceDefinition = $attr->value;

                if ($namespaceDefinition === Namespaces::XML) {
                    continue;
                }

                if ($namespaceDefinition === '') {
                    $namespaceDefinition = null;
                }

                if ($map->hasPrefix($namespaceDefinition, $prefixDefinition)) {
                    continue;
                }

                $map->add($namespaceDefinition, $prefixDefinition);
                $localPrefixesMap[$prefixDefinition] = $namespaceDefinition;
            }
        }

        return $defaultNamespaceAttrValue;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-serializing-an-attribute-value
     *
     * @throws \Rowbot\DOM\Parser\Exception\ParserException
     */
    private function serializeAttributeValue(?string $value, bool $requireWellFormed): string
    {
        if ($value === null) {
            return '';
        }

        if ($requireWellFormed && preg_match(Namespaces::CHAR, $value)) {
            throw new ParserException();
        }

        return htmlspecialchars($value, 0);
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-xml-serialization-of-the-attributes
     *
     * @param array<string, string>                     $localPrefixesMap
     *
     * @throws \Rowbot\DOM\Parser\Exception\ParserException
     */
    private function serializeAttributes(
        Element $element,
        NamespacePrefixMap $map,
        int &$prefixIndex,
        array $localPrefixesMap,
        bool $ignoreNamespaceDefinitionAttribute,
        bool $requireWellFormed
    ): string {
        $result = '';
        $localNameSet = [];

        foreach ($element->getAttributeList() as $attr) {
            $attributeNamespace = $attr->namespaceURI;
            $localName = $attr->localName;
            $hash = md5((string) $attributeNamespace . $localName);

            if ($requireWellFormed && isset($localNameSet[$hash])) {
                throw new ParserException();
            }

            $tuple = [$attributeNamespace, $localName];
            $localNameSet[$hash] = $tuple;
            $candidatePrefix = null;
            $attributePrefix = $attr->prefix;
            $attributeValue = $attr->value;

            if ($attributeNamespace !== null) {
                $candidatePrefix = $map->preferredPrefix(
                    $attributeNamespace,
                    $attributePrefix
                );

                if ($attributeNamespace === Namespaces::XMLNS) {
                    if (
                        $attributeValue === Namespaces::XML
                        || ($attributePrefix === null && $ignoreNamespaceDefinitionAttribute)
                        || (
                            $attributePrefix !== null
                            && (
                                !isset($localPrefixesMap[$localName])
                                || (
                                    isset($localPrefixesMap[$localName])
                                    && $localPrefixesMap[$localName] !== $attributeValue
                                )
                            )
                        )
                        && $map->hasPrefix($attributeValue, $localName)
                    ) {
                        continue;
                    }

                    if ($requireWellFormed && $attributeValue === Namespaces::XMLNS) {
                        throw new ParserException();
                    }

                    if ($requireWellFormed && $attributeValue === '') {
                        throw new ParserException();
                    }

                    if ($attributePrefix === 'xmlns') {
                        $candidatePrefix = 'xmlns';
                    }
                } else {
                    $candidatePrefix = $this->generatePrefix(
                        $map,
                        $attributeNamespace,
                        $prefixIndex
                    );

                    $result .= ' ';
                    $result .= 'xmlns:';
                    $result .= $candidatePrefix;
                    $result .= '="';
                    $result .= $this->serializeAttributeValue($attributeValue, $requireWellFormed);
                    $result .= '"';
                }
            }

            $result .= ' ';

            if ($candidatePrefix !== null) {
                $result .= $candidatePrefix . ':';
            }

            if (
                $requireWellFormed
                && (
                    mb_strpos($localName, ':', 0, 'utf-8') !== false
                    || preg_match(Namespaces::NAME_PRODUCTION, $localName) !== 1
                    || ($localName === 'xmlns' && $attributeNamespace === null)
                )
            ) {
                throw new ParserException();
            }

            $result .= $localName;
            $result .= '="';
            $result .= $this->serializeAttributeValue($attributeValue, $requireWellFormed);
            $result .= '"';
        }

        return $result;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-concept-generate-prefix
     */
    private function generatePrefix(
        NamespacePrefixMap $map,
        ?string $newNamespace,
        int &$prefixIndex
    ): string {
        $generatedPrefix = 'ns' . $prefixIndex;
        $prefixIndex++;
        $map->add($newNamespace, $generatedPrefix);

        return $generatedPrefix;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#xml-serializing-a-document-node
     *
     * @throws \Rowbot\DOM\Parser\Exception\ParserException
     */
    private function serializeDocumentNode(
        Document $node,
        ?string $namespace,
        NamespacePrefixMap $prefixMap,
        int &$prefixIndex,
        bool $requireWellFormed
    ): string {
        if ($requireWellFormed && $node->documentElement === null) {
            throw new ParserException();
        }

        $serializedDocument = '';

        foreach ($node->childNodes as $child) {
            $serializedDocument .= $this->serializeNode(
                $child,
                $namespace,
                $prefixMap,
                $prefixIndex,
                $requireWellFormed
            );
        }

        return $serializedDocument;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#xml-serializing-a-comment-node
     *
     * @throws \Rowbot\DOM\Parser\Exception\ParserException
     */
    private function serializeCommentNode(
        Comment $node,
        ?string $namespace,
        NamespacePrefixMap $prefixMap,
        int &$prefixIndex,
        bool $requireWellFormed
    ): string {
        $data = $node->data;

        if (
            $requireWellFormed
            && (
                preg_match(Namespaces::CHAR, $data)
                || mb_strpos($data, '--', 0, 'utf-8') !== false
            )
            || mb_substr($data, -1, 1, 'utf-8') === '-'
        ) {
            throw new ParserException();
        }

        return '<!--' . $data . '-->';
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#xml-serializing-a-text-node
     *
     * @throws \Rowbot\DOM\Parser\Exception\ParserException
     */
    private function serializeTextNode(
        Text $node,
        ?string $namespace,
        NamespacePrefixMap $prefixMap,
        int &$prefixIndex,
        bool $requireWellFormed
    ): string {
        $data = $node->data;

        if ($requireWellFormed && preg_match(Namespaces::CHAR, $data)) {
            throw new ParserException();
        }

        $markup = $data;
        $markup = str_replace(
            ['&', '<', '>'],
            ['&amp;', '&lt;', '&gt;'],
            $markup
        );

        return $markup;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#xml-serializing-a-documentfragment-node
     */
    private function serializeDocumentFragmentNode(
        DocumentFragment $node,
        ?string $namespace,
        NamespacePrefixMap $prefixMap,
        int &$prefixIndex,
        bool $requireWellFormed
    ): string {
        $markup = '';

        foreach ($node->childNodes as $child) {
            $markup .= $this->serializeNode(
                $child,
                $namespace,
                $prefixMap,
                $prefixIndex,
                $requireWellFormed
            );
        }

        return $markup;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#xml-serializing-a-documenttype-node
     *
     * @throws \Rowbot\DOM\Parser\Exception\ParserException
     */
    private function serializeDocumentTypeNode(
        DocumentType $node,
        ?string $namespace,
        NamespacePrefixMap $prefixMap,
        int &$prefixIndex,
        bool $requireWellFormed
    ): string {
        $publicId = $node->publicId;
        $systemId = $node->systemId;

        if ($requireWellFormed) {
            if (
                preg_match(
                    '/^((?!\x20|\x0D|\x0A|[a-zA-Z0-9]|[-\'()+,.\/:=?!*#@$_%]).)*/',
                    $publicId
                )
            ) {
                throw new ParserException();
            }

            if (
                preg_match(Namespaces::CHAR, $systemId)
                || (
                    mb_strpos($systemId, '"', 0, 'utf-8') !== false
                    && mb_strpos($systemId, '\'', 0, 'utf-8') !== false
                )
            ) {
                throw new ParserException();
            }
        }

        $markup = '';
        $markup .= '<!DOCTYPE';
        $markup .= ' ';
        $markup .= $node->name;

        if ($publicId !== '') {
            $markup .= ' ';
            $markup .= 'PUBLIC';
            $markup .= ' ';
            $markup .= '"';
            $markup .= $publicId;
            $markup .= '"';
        }

        if ($systemId !== '' && $publicId === '') {
            $markup .= ' ';
            $markup .= 'SYSTEM';
        }

        if ($systemId !== '') {
            $markup .= ' ';
            $markup .= '"';
            $markup .= $systemId;
            $markup .= '"';
        }

        $markup .= '>';

        return $markup;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#xml-serializing-a-processinginstruction-node
     */
    private function serializeProcessingInstructionNode(
        ProcessingInstruction $node,
        ?string $namespace,
        NamespacePrefixMap $prefixMap,
        int &$prefixIndex,
        bool $requireWellFormed
    ): string {
        $target = $node->target;
        $data = $node->data;

        if ($requireWellFormed) {
            if (mb_strpos($target, ':', 0, 'utf-8') !== false || strcasecmp($target, 'xml') === 0) {
                throw new ParserException();
            }

            if (
                preg_match(Namespaces::CHAR, $data)
                || mb_strpos($data, '?>', 0, 'utf-8')
            ) {
                throw new ParserException();
            }
        }

        $markup = '<?';
        $markup .= $target;
        $markup .= ' ';
        $markup .= $data;
        $markup .= '?>';

        return $markup;
    }
}
