<?php
namespace phpjs\parser\xml;

use Exception;
use phpjs\Attr;
use phpjs\Comment;
use phpjs\Document;
use phpjs\DocumentFragment;
use phpjs\DocumentType;
use phpjs\elements\Element;
use phpjs\exceptions\TypeError;
use phpjs\Namespaces;
use phpjs\Node;
use phpjs\parser\Parser;
use phpjs\parser\exception\ParserException;
use phpjs\ProcessingInstruction;
use phpjs\Text;

class XMLParser extends Parser
{
    private $document;

    public function __construct(Document $aDocument)
    {
        parent::__construct();

        $this->document = $aDocument;
    }

    public function abort()
    {
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/xhtml.html#xml-fragment-parsing-algorithm
     * @param string       $aInput          [description]
     * @param Element|null $aContextElement [description]
     */
    public static function parseXMLFragment(
        $aInput,
        Element $aContextElement = null
    ) {
        $document = new Document();
        $parser = new XMLParser($document);
        $parser->inputStream->append($aInput);

        try {
            $parser->run();
        } catch (Exception $e) {
            throw new SyntaxError('', 0, $e);
        }
    }

    public static function serializeAsXML($aNode, $aRequireWellFormed)
    {
        $namespace = null;
        $prefixMap = new NamespacePrefixMap();
        $prefixMap->add(Namespaces::XML, 'xml');
        $prefixIndex = 1;

        try {
            return self::serializeNode(
                $aNode,
                $namespace,
                $prefixMap,
                $prefixIndex,
                $aRequireWellFormed
            );
        } catch (Exception $e) {
            throw new InvalidStateError('', 0, $e);
        }
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-concept-xml-serialization-algorithm
     * @param  Node     $aNode              [description]
     * @param  string   $aNamespace         [description]
     * @param  string[] $aPrefixMap         [description]
     * @param  int      &$aPrefixIndex      [description]
     * @param  bool     $aRequireWellFormed [description]
     * @return string
     */
    public static function serializeNode(
        Node $aNode,
        $aNamespace,
        $aPrefixMap,
        &$aPrefixIndex,
        $aRequireWellFormed
    ) {
        if ($aNode instanceof Element) {
            return self::serializeElementNode(
                $aNode,
                $aNamespace,
                $aPrefixMap,
                $aPrefixIndex,
                $aRequireWellFormed
            );
        } elseif ($aNode instanceof Document) {
            return self::serializeDocumentNode(
                $aNode,
                $aNamespace,
                $aPrefixMap,
                $aPrefixIndex,
                $aRequireWellFormed
            );
        } elseif ($aNode instanceof Comment) {
            return self::serializeCommentNode(
                $aNode,
                $aNamespace,
                $aPrefixMap,
                $aPrefixIndex,
                $aRequireWellFormed
            );
        } elseif ($aNode instanceof Text) {
            return self::serializeTextNode(
                $aNode,
                $aNamespace,
                $aPrefixMap,
                $aPrefixIndex,
                $aRequireWellFormed
            );
        } elseif ($aNode instanceof DocumentFragment) {
            return self::serializeDocumentFragmentNode(
                $aNode,
                $aNamespace,
                $aPrefixMap,
                $aPrefixIndex,
                $aRequireWellFormed
            );
        } elseif ($aNode instanceof DocumentType) {
            return self::serializeDocumentTypeNode(
                $aNode,
                $aNamespace,
                $aPrefixMap,
                $aPrefixIndex,
                $aRequireWellFormed
            );
        } elseif ($aNode instanceof ProcessingInstruction) {
            return self::serializeProcessingInstructionNode(
                $aNode,
                $aNamespace,
                $aPrefixMap,
                $aPrefixIndex,
                $aRequireWellFormed
            );
        } elseif ($aNode instanceof Attr) {
            return '';
        }

        throw new TypeError();
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#xml-serializing-an-element-node
     * @param  Element            $aNode              [description]
     * @param  [type]             $aNamespace         [description]
     * @param  NamespacePrefixMap $aPrefixMap         [description]
     * @param  [type]             &$aPrefixIndex      [description]
     * @param  [type]             $aRequireWellFormed [description]
     * @return [type]                                 [description]
     */
    private static function serializeElementNode(
        Element $aNode,
        $aNamespace,
        NamespacePrefixMap $aPrefixMap,
        &$aPrefixIndex,
        $aRequireWellFormed
    ) {
        $localName = $aNode->localName;

        if ($aRequireWellFormed &&
            (mb_strpos($localName, ':') !== false ||
            !preg_match(Namespaces::NAME_PRODUCTION, $localName))
        ) {
            throw new ParserException();
        }

        $markup = '<';
        $qualifiedName = '';
        $skipEndTag = false;
        $ignoreNamespaceDefinitionAttribute = false;
        $map = clone $aPrefixMap;
        $localPrefixesMap = [];
        $localDefaultNamespace = self::recordNamespaceInformation(
            $aNode,
            $map,
            $localPrefixesMap
        );
        $inheritedNS = $aNamespace;
        $ns = $aNode->namespaceURI;

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
            $prefix = $aNode->prefix;
            // This may return null if no namespace key exists in the map.
            $candidatePrefix = $map->preferredPrefix($ns, $prefix);

            if ($prefix === 'xmlns') {
                // An Element with prefix "xmlns" will not legally round-trip
                if ($aRequireWellFormed) {
                    throw new ParserException();
                }

                $candidatePrefix = $prefix;
            }

            // "Found a suitable namespace prefix"
            if ($candidatePrefix !== null) {
                $qualifiedName .= $candidatePrefix . ':' . $localName;

                if ($localDefaultNamespace !== null &&
                    $localDefaultNamespace !== Namespaces::XML
                ) {
                    $inheritedNS = $localDefaultNamespace === ''
                        ? null
                        : $localDefaultNamespace;
                    $markup .= $qualifiedName;
                }
            } elseif ($prefix !== null) {
                if (isset($localPrefixesMap[$prefix])) {
                    $prefix = self::generatePrefix($map, $ns, $aPrefixIndex);
                }

                $map->add($ns, $prefix);
                $qualifiedName .= $prefix . ':' . $localName;
                $markup .= $qualifiedName;
                $markup .= ' ';
                $markup .= 'xmlns:';
                $markup .= $prefix;
                $markup .= '="';
                $markup .= self::serializeAttributeValue(
                    $ns,
                    $aRequireWellFormed
                );
                $markup .= '"';

                if ($localDefaultNamespace !== null) {
                    $inheritedNS = $localDefaultNamespace === ''
                        ? null
                        : $localDefaultNamespace;
                }
            } elseif ($localDefaultNamespace === null || (
                $localDefaultNamespace !== null &&
                $localDefaultNamespace !== $ns)
            ) {
                $ignoreNamespaceDefinitionAttribute = true;
                $qualifiedName .= $localName;
                $inheritedNS = $ns;
                $markup .= $qualifiedName;
                $markup .= ' ';
                $markup .= 'xmlns:';
                $markup .= '="';
                $markup .= self::serializeAttributeValue(
                    $ns,
                    $aRequireWellFormed
                );
                $markup .= '"';
            } else {
                $qualifiedName .= $localName;
                $inheritedNS = $ns;
                $markup .= $qualifiedName;
            }
        }

        $markup .= self::serializeAttributes(
            $aNode,
            $map,
            $aPrefixIndex,
            $localPrefixesMap,
            $ignoreNamespaceDefinitionAttribute,
            $aRequireWellFormed
        );

        if ($ns === Namespaces::HTML && !$aNode->hasChildNodes() &&
            preg_match(self::VOID_TAGS, $localName)
        ) {
            $markup .= ' /';
            $skipEndTag = true;
        } elseif ($ns !== Namespaces::HTML  && !$aNode->hasChildNodes()) {
            $markup .= '/';
            $skipEndTag = true;
        }

        $markup .= '>';

        if ($skipEndTag) {
            return $markup;
        }

        if ($ns === Namespaces::HTML && $localName === 'template') {
            $markup .= self::serializeDocumentFragmentNode(
                $aNode->content,
                $inheritedNS,
                $map,
                $aPrefixIndex,
                $aRequireWellFormed
            );
        } else {
            foreach ($aNode->childNodes as $child) {
                $markup .= self::serializeNode(
                    $child,
                    $inheritedNS,
                    $map,
                    $aPrefixIndex,
                    $aRequireWellFormed
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
     * @param  Element            $aElement           [description]
     * @param  NamespacePrefixMap $aMap               [description]
     * @param  [type]             &$aLocalPrefixesMap [description]
     * @return [type]                                 [description]
     */
    private static function recordNamespaceInformation(
        Element $aElement,
        NamespacePrefixMap $aMap,
        &$aLocalPrefixesMap
    ) {
        $defaultNamespaceAttrValue = null;

        foreach ($aElement->getAttributesList() as $attr) {
            $attirbuteNamespace = $attr->namespaceURI;
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

                if ($aMap->hasPrefix($namespaceDefinition, $prefixDefinition)) {
                    continue;
                }

                $aMap->add($namespaceDefinition, $prefixDefinition);
                $aLocalPrefixesMap[$prefixDefinition] = $namespaceDefinition;
            }
        }

        return $defaultNamespaceAttrValue;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-serializing-an-attribute-value
     * @param  [type] $aValue             [description]
     * @param  [type] $aRequireWellFormed [description]
     * @return [type]                     [description]
     */
    private static function serializeAttributeValue(
        $aValue,
        $aRequireWellFormed
    ) {
        if ($aRequireWellFormed &&
            preg_match('/^((?!' . Namespaces::CHAR . ').)*/u')
        ) {
            throw new ParserException();
        }

        if ($aValue === null) {
            return '';
        }

        return htmlspecialchars($aValue, 0);
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-xml-serialization-of-the-attributes
     * @param  Element            $aElement
     * @param  NamespacePrefixMap $aMap                                [description]
     * @param  [type]             &$aPrefixIndex                       [description]
     * @param  [type]             $aLocalPrefixesMap                   [description]
     * @param  [type]             $aIgnoreNamespaceDefinitionAttribute [description]
     * @param  [type]             $aRequireWellFormed                  [description]
     * @return [type]                                                  [description]
     */
    private static function serializeAttributes(
        Element $aElement,
        NamespacePrefixMap $aMap,
        &$aPrefixIndex,
        $aLocalPrefixesMap,
        $aIgnoreNamespaceDefinitionAttribute,
        $aRequireWellFormed
    ) {
        $result = '';
        $localNameSet = [];

        foreach ($aElement->getAttributesList() as $attr) {
            $attributeNamespace = $attr->namespaceURI;
            $localName = $attr->localName;
            $hash = md5((string) $attributeNamespace . $localName);

            if ($aRequireWellFormed && isset($localNameSet[$hash])) {
                throw new ParserException();
            }

            $tuple = [$attributeNamespace, $localName];
            $localNameSet[$hash] = $tuple;
            $candidatePrefix = null;
            $attributePrefix = $attr->prefix;
            $attributeValue = $attr->value;

            if ($attirbuteNamespace !== null) {
                $candidatePrefix = $aMap->preferredPrefix(
                    $attributeNamespace,
                    $attributePrefix
                );

                if ($attributeNamespace === Namespaces::XMLNS) {
                    if ($attributeValue === Namespaces::XML ||
                        ($attributePrefix === null &&
                            $aIgnoreNamespaceDefinitionAttribute)
                        ($attributePrefix !== null &&
                            (!isset($aLocalPrefixesMap[$localName]) ||
                                (isset($aLocalPrefixesMap[$localName]) &&
                                $aLocalPrefixesMap[$localName] !== $attributeValue)
                            )
                        ) &&
                        $aMap->hasPrefix($attributeValue, $localName)
                    ) {
                        continue;
                    }

                    if ($aRequireWellFormed &&
                        $attributeValue === Namespaces::XMLNS
                    ) {
                        throw new ParserException();
                    }

                    if ($aRequireWellFormed && $attributeValue === '') {
                        throw new ParserException();
                    }

                    if ($attributePrefix === 'xmlns') {
                        $candidatePrefix = 'xmlns';
                    }
                } else {
                    $candidatePrefix = self::generatePrefix(
                        $aMap,
                        $attributeNamespace,
                        $aPrefixIndex
                    );

                    $result .= ' ';
                    $result .= 'xmlns:';
                    $result .= $candidatePrefix;
                    $result .= '="';
                    $result .= self::serializeAttributeValue(
                        $attributeValue,
                        $aRequireWellFormed
                    );
                    $result .= '"';
                }
            }

            $result .= ' ';

            if ($candidatePrefix !== null) {
                $result .= $candidatePrefix . ':';
            }

            if ($aRequireWellFormed &&
                (mb_strpos($localName, ':') !== false ||
                $localName !== Namespaces::XMLNS ||
                ($localName === 'xmlns' && $attributeNamespace === null))
            ) {
                throw new ParserException();
            }

            $result .= $localName;
            $result .= '="';
            $result .= self::serializeAttributeValue(
                $attributeValue,
                $aRequireWellFormed
            );
            $result .= '"';
        }

        return $result;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#dfn-concept-generate-prefix
     *
     * @param NamespacePrefixMap $aMap [description]
     *
     * @param string             $aNewNamespace [description]
     *
     * @param int                &$aPrefixIndex [description]
     *
     * @return string
     */
    private static function generatePrefix(
        NamespacePrefixMap $aMap,
        $aNewNamespace,
        &$aPrefixIndex
    ) {
        $generatedPrefix = 'ns' . $aPrefixIndex;
        $aPrefixIndex++;
        $aMap->add($aNewNamespace, $generatedPrefix);

        return $generatedPrefix;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#xml-serializing-a-document-node
     * @param  Document           $aNode              [description]
     * @param  [type]             $aNamespace         [description]
     * @param  NamespacePrefixMap $aPrefixMap         [description]
     * @param  [type]             &$aPrefixIndex      [description]
     * @param  [type]             $aRequireWellFormed [description]
     * @return [type]                                 [description]
     */
    private static function serializeDocumentNode(
        Document $aNode,
        $aNamespace,
        NamespacePrefixMap $aPrefixMap,
        &$aPrefixIndex,
        $aRequireWellFormed
    ) {
        if ($aRequireWellFormed && $aNode->documentElement === null) {
            throw new ParserException();
        }

        $serializedDocument = '';

        foreach ($aNode->childNodes as $child) {
            $serializedDocument .= self::serializeNode(
                $child,
                $aNamespace,
                $aPrefixMap,
                $aPrefixIndex,
                $aRequireWellFormed
            );
        }

        return $serializedDocument;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#xml-serializing-a-comment-node
     * @param  Comment            $aNode              [description]
     * @param  [type]             $aNamespace         [description]
     * @param  NamespacePrefixMap $aPrefixMap         [description]
     * @param  [type]             &$aPrefixIndex      [description]
     * @param  [type]             $aRequireWellFormed [description]
     * @return [type]                                [description]
     */
    private static function serializeCommentNode(
        Comment $aNode,
        $aNamespace,
        NamespacePrefixMap $aPrefixMap,
        &$aPrefixIndex,
        $aRequireWellFormed
    ) {
        $data = $aNode->data;

        if ($aRequireWellFormed &&
            (preg_match('/^((?!' . Namespaces::CHAR . ').)*/u') ||
                mb_strpos($data, '--') !== false) ||
                mb_substr($data, -1, 1) === '-'
        ) {
            throw new ParserException();
        }

        return '<!--' . $data . '-->';
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#xml-serializing-a-text-node
     * @param  Text               $aNode              [description]
     * @param  [type]             $aNamespace         [description]
     * @param  NamespacePrefixMap $aPrefixMap         [description]
     * @param  [type]             &$aPrefixIndex      [description]
     * @param  [type]             $aRequireWellFormed [description]
     * @return [type]                                 [description]
     */
    private static function serializeTextNode(
        Text $aNode,
        $aNamespace,
        NamespacePrefixMap $aPrefixMap,
        &$aPrefixIndex,
        $aRequireWellFormed
    ) {
        $data = $aNode->data;

        if ($aRequireWellFormed &&
            preg_match('/^((?!' . Namespaces::CHAR . ').)*/u', $data)
        ) {
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
     * @param  DocumentFragment   $aNode              [description]
     * @param  [type]             $aNamespace         [description]
     * @param  NamespacePrefixMap $aPrefixMap         [description]
     * @param  [type]             &$aPrefixIndex      [description]
     * @param  [type]             $aRequireWellFormed [description]
     * @return [type]                                 [description]
     */
    private static function serializeDocumentFragmentNode(
        DocumentFragment $aNode,
        $aNamespace,
        NamespacePrefixMap $aPrefixMap,
        &$aPrefixIndex,
        $aRequireWellFormed
    ) {
        $markup = '';

        foreach ($aNode->childNodes as $child) {
            $markup .= self::serializeNode(
                $child,
                $aNamespace,
                $aPrefixMap,
                $aPrefixIndex,
                $aRequireWellFormed
            );
        }

        return $markup;
    }

    /**
     * @see https://w3c.github.io/DOM-Parsing/#xml-serializing-a-documenttype-node
     * @param  DocumentType       $aNode              [description]
     * @param  [type]             $aNamespace         [description]
     * @param  NamespacePrefixMap $aPrefixMap         [description]
     * @param  [type]             &$aPrefixIndex      [description]
     * @param  [type]             $aRequireWellFormed [description]
     * @return [type]                                [description]
     */
    private static function serializeDocumentTypeNode(
        DocumentType $aNode,
        $aNamespace,
        NamespacePrefixMap $aPrefixMap,
        &$aPrefixIndex,
        $aRequireWellFormed
    ) {
        $publicId = $aNode->publicId;
        $systemId = $aNode->systemId;

        if ($aRequireWellFormed) {
            if (preg_match(
                '/^((?!\x20|\x0D|\x0A|[a-zA-Z0-9]|[-\'()+,.\/:=?!*#@$_%]).)*/',
                $publicId
            )) {
                throw new ParserException();
            }

            if ((preg_match(
                '/^((?!' . Namespaces::CHAR . ').)*/u',
                $systemId
            )) ||
                mb_strpos('"') !== false && mb_strpos('\'') !== false
            ) {
                throw new ParserException();
            }
        }

        $markup = '';
        $markup .= '<!DOCTYPE';
        $markup .= ' ';
        $markup .= $aNode->name;

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
     * @param  ProcessingInstruction $aNode              [description]
     * @param  [type]                $aNamespace         [description]
     * @param  NamespacePrefixMap    $aPrefixMap         [description]
     * @param  [type]                &$aPrefixIndex      [description]
     * @param  [type]                $aRequireWellFormed [description]
     * @return [type]                                    [description]
     */
    private static function serializeProcessingInstructionNode(
        ProcessingInstruction $aNode,
        $aNamespace,
        NamespacePrefixMap $aPrefixMap,
        &$aPrefixIndex,
        $aRequireWellFormed
    ) {
        $target = $aNode->target;
        $data = $aNode->data;

        if ($aRequireWellFormed) {
            if (mb_strpos($target, ':') !== false ||
                strcasecmp($target, 'xml') === 0
            ) {
                throw new ParserException();
            }

            if (preg_match('/^((?!' . Namespaces::CHAR . ').)*/u', $data) ||
                mb_strpos($data, '?>')
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

    public function preprocessInputStream($aInput)
    {
        $this->inputStream->append($aInput);
    }
}
