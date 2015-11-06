<?php
class Namespaces {
    const HTML = 'http://www.w3.org/1999/xhtml';
    const SVG = 'http://www.w3.org/2000/svg';
    const XML = 'http://www.w3.org/XML/1998/namespace';
    const XMLNS = 'http://www.w3.org/2000/xmlns/';
    const MATHML = 'http://www.w3.org/1998/Math/MathML';
    const XLINK = 'http://www.w3.org/1999/xlink';

    /**
     * Finds the namespace associated with the given prefix on the given node.
     *
     * @link https://dom.spec.whatwg.org/#locate-a-namespace
     *
     * @param  Node         $aNode   The node whose namespace is to be found.
     *
     * @param  string|null  $aPrefix The prefix of the namespace to be found.
     *
     * @return string|null
     */
    public static function locateNamespace(Node $aNode, $aPrefix) {
        switch (true) {
            case $aNode instanceof Element:
                if ($aNode->namespaceURI && $aNode->prefix === $aPrefix) {
                    return $aNode->namespaceURI;
                }

                foreach ($aNode->attributes as $attr) {
                    if (($attr->namespaceURI === self::XMLNS && $attr->prefix === 'xmlns' &&
                        $attr->localName === $aPrefix) || (!$aPrefix && $attr->namespaceURI === self::XMLNS &&
                        !$attr->prefix && strcmp($attr->localName, 'xmlns') === 0)) {
                        return $attr->value !== '' ? $attr->value : null;
                    }
                }

                if (!$aNode->parentElement) {
                    return null;
                }

                return self::locateNamespace($aNode->parentElement, $aPrefix);

            case $aNode instanceof Document:
                if (!$aNode->documentElement) {
                    return null;
                }

                return self::locateNamespace($aNode->documentElement, $aPrefix);

            case $aNode instanceof DocumentType:
            case $aNode instanceof DocumentFragment:
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
     * @link https://dom.spec.whatwg.org/#locate-a-namespace-prefix
     *
     * @param  Node         $aNode      Thoe node whose prefix is to be found.
     *
     * @param  string|null  $aNamespace The namespace of the prefix to be found.
     *
     * @return string|null
     */
    public static function locatePrefix(Node $aNode, $aNamespace) {
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

    public static function validate($aQualifiedName) {
        // TODO
    }

    public static function validateAndExtract($aNamespace, $aQualifiedName) {
        $namespace = $aNamespace === '' ? null : $aNamespace;
        self::validate($aQualifiedName);
        $prefix = null;
        $localName = $aQualifiedName;

        if (strpos($aQualifiedName, ':') !== false) {
            list($prefix, $localName) = explode(':', $aQualifiedName);
        }

        if ($prefix && !$namespace) {
            throw new NamespaceError;
        }

        if ($prefix === 'xml' && $namespace === self::XML) {
            throw new NamespaceError;
        }

        if ((strcmp($aQualifiedName, 'xmlns') === 0 || $prefix === 'xmlns') &&
            $namespace === self::XMLNS) {
            throw new NamespaceError;
        }

        if ($namespace === self::XMLNS && strcmp($aQualifiedName, 'xmlns') !== 0 &&
            $prefix === 'xmlns') {
            throw new NamespaceError;
        }

        return array(
                    'namespace' => $namespace,
                    'prefix' => $prefix,
                    'localName' => $localName,
                    'qualifiedName' => $aQualifiedName
                );
    }
}
