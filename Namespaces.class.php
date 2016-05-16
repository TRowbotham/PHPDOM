<?php
namespace phpjs;

use phpjs\exceptions\DOMException;
use phpjs\exceptions\NamespaceError;

class Namespaces
{
    const HTML = 'http://www.w3.org/1999/xhtml';
    const SVG = 'http://www.w3.org/2000/svg';
    const XML = 'http://www.w3.org/XML/1998/namespace';
    const XMLNS = 'http://www.w3.org/2000/xmlns/';
    const MATHML = 'http://www.w3.org/1998/Math/MathML';
    const XLINK = 'http://www.w3.org/1999/xlink';

    /**
     * Finds the namespace associated with the given prefix on the given node.
     *
     * @see https://dom.spec.whatwg.org/#locate-a-namespace
     *
     * @param Node $aNode The node whose namespace is to be found.
     *
     * @param string|null $aPrefix The prefix of the namespace to be found.
     *
     * @return string|null
     */
    public static function locateNamespace(Node $aNode, $aPrefix)
    {
        switch ($aNode->nodeType) {
            case Node::ELEMENT_NODE:
                if ($aNode->namespaceURI && $aNode->prefix === $aPrefix) {
                    return $aNode->namespaceURI;
                }

                foreach ($aNode->attributes as $attr) {
                    if (
                        ($attr->namespaceURI === self::XMLNS &&
                            $attr->prefix === 'xmlns' &&
                            $attr->localName === $aPrefix) ||
                        (!$aPrefix &&
                            $attr->namespaceURI === self::XMLNS &&
                            !$attr->prefix &&
                            strcmp($attr->localName, 'xmlns') === 0)
                    ) {
                        return $attr->value !== '' ? $attr->value : null;
                    }
                }

                if (!$aNode->parentElement) {
                    return null;
                }

                return self::locateNamespace($aNode->parentElement, $aPrefix);

            case Node::DOCUMENT_NODE:
                if (!$aNode->documentElement) {
                    return null;
                }

                return self::locateNamespace($aNode->documentElement, $aPrefix);

            case Node::DOCUMENT_TYPE_NODE:
            case Node::DOCUMENT_FRAGMENT_NODE:
                return null;

            default:
                if (!$aNode->parentElement) {
                    return null;
                }

                return self::locateNamespace($aNode->parentElement, $aPrefix);
        }
    }

    /**
     * Finds the prefix associated with the given namespace on the given node.
     *
     * @see https://dom.spec.whatwg.org/#locate-a-namespace-prefix
     *
     * @param Node $aNode Those node whose prefix is to be found.
     *
     * @param string|null  $aNamespace The namespace of the prefix to be found.
     *
     * @return string|null
     */
    public static function locatePrefix(Node $aNode, $aNamespace)
    {
        if ($aNode->namespaceURI === $aNamespace && !$aNode->prefix) {
            return $aNode->prefix;
        }

        foreach ($aNode->attribtues as $attr) {
            if ($attr->prefix === 'xmlns' && $attr->value === $aNamespace) {
                return $attr->localName;
            }
        }

        if ($aNode->parentElement) {
            return self::locatePrefix($aNode->parentElement, $aNamespace);
        }

        return null;
    }

    public static function validate($aQualifiedName)
    {
        // TODO
    }

    /**
     * Validates that the given name is valid in the given namespace and returns
     * the input broken down into its individual parts.
     *
     * @see https://dom.spec.whatwg.org/#validate-and-extract
     *
     * @param string $aNamespace A namespace.
     *
     * @param string $aQualifiedName The qualified name to validate.
     *
     * @return string[] Returns the namespace, namespace prefix, and localName.
     *
     * @throws NamespaceError
     */
    public static function validateAndExtract($aNamespace, $aQualifiedName)
    {
        $namespace = $aNamespace === '' ? null : $aNamespace;

        try {
            self::validate($aQualifiedName);
        } catch (DOMException $e) {
            throw $e;
        }

        $prefix = null;
        $localName = $aQualifiedName;

        if (mb_strpos($aQualifiedName, ':') !== false) {
            list($prefix, $localName) = explode(':', $aQualifiedName, 2);
        }

        if ($prefix !== null && $namespace === null) {
            throw new NamespaceError();
        }

        if ($prefix === 'xml' && $namespace !== self::XML) {
            throw new NamespaceError();
        }

        if (($aQualifiedName === 'xmlns' || $prefix === 'xmlns') &&
            $namespace !== self::XMLNS) {
            throw new NamespaceError();
        }

        if ($namespace === self::XMLNS && $aQualifiedName !== 'xmlns' &&
            $prefix !== 'xmlns'
        ) {
            throw new NamespaceError();
        }

        return [
            $namespace,
            $prefix,
            $localName
        ];
    }
}
