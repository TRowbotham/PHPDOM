<?php
namespace Rowbot\DOM\Element;

use Rowbot\DOM\Attr;
use Rowbot\DOM\AttributeChangeObserver;
use Rowbot\DOM\AttributeList;
use Rowbot\DOM\ChildNode;
use Rowbot\DOM\Document;
use Rowbot\DOM\DOMTokenList;
use Rowbot\DOM\Exception\DOMException;
use Rowbot\DOM\Exception\InUseAttributeError;
use Rowbot\DOM\Exception\NoModificationAllowedError;
use Rowbot\DOM\Exception\NotFoundError;
use Rowbot\DOM\GetElementsBy;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\NamedNodeMap;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\NodeFilter;
use Rowbot\DOM\NonDocumentTypeChildNode;
use Rowbot\DOM\ParentNode;
use Rowbot\DOM\Parser\MarkupFactory;
use Rowbot\DOM\Parser\ParserFactory;
use Rowbot\DOM\Text;
use Rowbot\DOM\TreeWalker;
use Rowbot\DOM\URL\URLParser;
use Rowbot\DOM\Utils;

/**
 * @see https://dom.spec.whatwg.org/#element
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Element
 */
class Element extends Node implements AttributeChangeObserver
{
    use ChildNode, ParentNode {
        ChildNode::convertNodesToNode insteadof ParentNode;
    }
    use GetElementsBy;
    use NonDocumentTypeChildNode;

    protected $namedNodeMap;
    protected $attributeList;
    protected $classList; // ClassList
    protected $localName;
    protected $namespaceURI;
    protected $prefix;

    protected function __construct()
    {
        parent::__construct();

        $this->attributeList = new AttributeList($this);
        $this->classList = new DOMTokenList($this, 'class');
        $this->localName = '';
        $this->namedNodeMap = new NamedNodeMap($this);
        $this->namespaceURI = null;
        $this->nodeType = self::ELEMENT_NODE;
        $this->prefix = null;
        $this->attributeList->observe($this);
    }

    public function __get($name)
    {
        switch ($name) {
            case 'attributes':
                return $this->namedNodeMap;

            case 'childElementCount':
                return $this->getChildElementCount();

            case 'children':
                return $this->getChildren();

            case 'classList':
                return $this->classList;

            case 'className':
                return $this->attributeList->getAttrValue('class');

            case 'firstElementChild':
                return $this->getFirstElementChild();

            case 'id':
                return $this->attributeList->getAttrValue($name);

            case 'innerHTML':
                // On getting, return the result of invoking the fragment
                // serializing algorithm on the context object providing true
                // for the require well-formed flag (this might throw an
                // exception instead of returning a string).
                return MarkupFactory::serializeFragment($this, true);

            case 'lastElementChild':
                return $this->getLastElementChild();

            case 'localName':
                return $this->localName;

            case 'outerHTML':
                // On getting, return the result of invoking the fragment
                // serializing algorithm on a fictional node whose only child is
                // the context object providing true for the require well-formed
                // flag (this might throw an exception instead of returning a
                // string).
                $fakeNode = self::create($this, 'fake', Namespaces::HTML);
                $fakeNode->ownerDocument = $this->ownerDocument;
                $fakeNode->childNodes->append($this);

                return MarkupFactory::serializeFragment($fakeNode, true);

            case 'namespaceURI':
                return $this->namespaceURI;

            case 'nextElementSibling':
                return $this->getNextElementSibling();

            case 'prefix':
                return $this->prefix;

            case 'previousElementSibling':
                return $this->getPreviousElementSibling();

            case 'tagName':
                return $this->getTagName();

            default:
                return parent::__get($name);
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'classList':
                $this->classList->value = $value;

                break;

            case 'className':
                $this->attributeList->setAttrValue(
                    'class',
                    Utils::DOMString($value)
                );

                break;

            case 'id':
                $this->attributeList->setAttrValue(
                    $name,
                    Utils::DOMString($value)
                );

                break;

            case 'innerHTML':
                // Let fragment be the result of invoking the fragment parsing
                // algorithm with the new value as markup, and the context
                // object as the context element.
                $fragment = ParserFactory::parseFragment(
                    Utils::DOMString($value, true),
                    $this
                );

                // If the context object is a template element, then let context
                // object be the template's template contents (a
                // DocumentFragment).
                $context = $this instanceof HTMLTemplateElement
                    ? $this->content
                    : $this;

                // NOTE: Setting innerHTML on a template element will replace
                // all the nodes in its template contents (template.content)
                // rather than its children.

                // Replace all with fragment within the context object.
                $this->replaceAllNodes($fragment);

                break;

            case 'outerHTML':
                // Let parent be the context object's parent.
                $parent = $this->parentNode;

                // If parent is null, terminate these steps. There would be no
                // way to obtain a reference to the nodes created even if the
                // remaining steps were run.
                if (!$parent) {
                    return;
                }

                // If parent is a Document, throw a
                // "NoModificationAllowedError" DOMException.
                if ($parent instanceof Document) {
                    throw new NoModificationAllowedError();
                }

                // If parent is a DocumentFragment, let parent be a new Element
                // with body as its local name, the HTML namespace as its
                // namespace, and the context object's node document as its node
                // document.
                if ($parent instanceof DocumentFragment) {
                    $parent = self::create(
                        $this->ownerDocument,
                        'body',
                        Namespaces::HTML
                    );
                }

                // Let fragment be the result of invoking the fragment parsing
                // algorithm with the new value as markup, and parent as the
                // context element.
                $fragment = ParserFactory::parseFragment(
                    Utils::DOMString($value, true),
                    $parent
                );

                // Replace the context object with fragment within the context
                // object's parent.
                $this->parentNode->replaceNode($fragment, $this);

                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function cloneNodeInternal(
        Document $document = null,
        bool $cloneChildren = false
    ) {
        $document = $document ?: $this->getNodeDocument();
        $copy = ElementFactory::create(
            $document,
            $this->localName,
            $this->namespaceURI,
            $this->prefix
        );

        foreach ($this->attributeList as $attr) {
            $copyAttribute = $attr->cloneNodeInternal();
            $copy->attributeList->append($copyAttribute);
        }

        $this->postCloneNode($copy, $document, $cloneChildren);

        return $copy;
    }

    public function closest($selectorRule)
    {
        // TODO
    }

    /**
     * Creates a new instance of an the specified element interface and
     * intializes that elements local name, namespace, and namespace prefix.
     *
     * @internal
     *
     * @param Document|null $document The element's owner document.
     *
     * @param string $localName The element's local name that you are creating.
     *
     * @param string $namespace The namespace that the element belongs to.
     *
     * @param string|null $prefix Optional. The namespace prefix of the
     *     element.
     *
     * @return Element
     */
    public static function create(
        $document,
        $localName,
        $namespace,
        $prefix = null
    ) {
        $element = new static();
        $element->localName = $localName;
        $element->namespaceURI = $namespace;
        $element->nodeDocument = $document;
        $element->prefix = $prefix;

        return $element;
    }

    /**
     * @see AttributeChangeObserver
     */
    public function onAttributeChanged(
        Element $element,
        $localName,
        $oldValue,
        $value,
        $namespace
    ) {
        // Do nothing.
    }

    /**
     * Retrieves the value of the attribute with the given name, if any.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getattribute
     *
     * @param string $name The name of the attribute whose value is to be
     *     retrieved.
     *
     * @return string|null
     */
    public function getAttribute($name)
    {
        $attr = $this->attributeList->getAttrByName(Utils::DOMString($name));

        return $attr ? $attr->value : null;
    }

    /**
     * Returns the element's internal attribute list.
     *
     * @internal
     *
     * @return AttributeList
     */
    public function getAttributeList()
    {
        return $this->attributeList;
    }

    /**
     * Returns a list of all attribute names in order.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-getattributenames
     *
     * @return string[]
     */
    public function getAttributeNames()
    {
        $list = [];

        foreach ($this->attributeList as $attr) {
            $list[] = $attr->name;
        }

        return $list;
    }

    /**
     * Retrieves the attribute node with the given name, if any.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getattributenode
     *
     * @param string $name The name of the attribute that is to be retrieved.
     *
     * @return Attr|null
     */
    public function getAttributeNode($name)
    {
        return $this->attributeList->getAttrByName(Utils::DOMString($name));
    }

    /**
     * Retrieves the attribute node with the given namespace and local name, if
     * any.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getattributenodens
     *
     * @param string $namespace The namespaceURI of the attribute node to be
     *     retrieved.
     *
     * @param string $localName The localName of the attribute node to be
     *     retrieved.
     *
     * @return Attr|null
     */
    public function getAttributeNodeNS($namespace, $localName)
    {
        return $this->attributeList->getAttrByNamespaceAndLocalName(
            Utils::DOMString($namespace, false, true),
            Utils::DOMString($localName)
        );
    }

    /**
     * Retrieves the value of the attribute with the given namespace and local name, if any.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getattributens
     *
     * @param  string       $namespace The namespaceURI of the attribute whose value is to be retrieved.
     * @param  string       $localName The localName of the attribute whose value is to be retrieved.
     * @return string|null
     */
    public function getAttributeNS($namespace, $localName)
    {
        return $this->attributeList->getAttrValue(
            Utils::DOMString($localName),
            Utils::DOMString($namespace, false, true)
        );
    }

    /**
     * Returns true if the attribtue with the given name is present in the
     * Element's attribute list, otherwise false.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-hasattribute
     *
     * @param string $qualifiedName The name of the attribute to find.
     *
     * @return bool
     */
    public function hasAttribute($qualifiedName)
    {
        $qualifiedName = Utils::DOMString($qualifiedName);

        if ($this->namespaceURI === Namespaces::HTML
            && $this->nodeDocument instanceof HTMLDocument
        ) {
            $qualifiedName = Utils::toASCIILowercase($qualifiedName);
        }

        return (bool) $this->attributeList->getAttrByName($qualifiedName);
    }

    /**
     * Returns true if the attribute with the given namespace and localName
     * is present in the Element's attribute list, otherwise false.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-hasattributens
     *
     * @param string $namespace The namespace of the attribute to find.
     *
     * @param string $localName The localName of the attribute to find.
     *
     * @return bool
     */
    public function hasAttributeNS($namespace, $localName)
    {
        return (bool) $this->attributeList->getAttrByNamespaceAndLocalName(
            Utils::DOMString($namespace, false, true),
            Utils::DOMString($localName)
        );
    }

    /**
     * Returns true if there are attributes present in the Element's attribute
     * list, otherwise false.
     *
     * @return bool
     */
    public function hasAttributes()
    {
        return !$this->attributeList->isEmpty();
    }

    /**
     * Inserts an element adjacent to the current element.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-insertadjacentelement
     *
     * @param string $where The position relative to this node. Possible
     *     values are:
     *         - beforebegin - Inserts an element as this element's previous
     *             sibling.
     *         - afterend - Inserts an element as this element's next sibling.
     *         - afterbegin - Inserts an element as this element's first child.
     *         - beforeend - Inserts an element as this element's last child.
     *
     * @param Element $element The element to be inserted.
     *
     * @return null
     */
    public function insertAdjacentElement($where, Element $element)
    {
        try {
            return self::insertAdjacent(
                $this,
                Utils::DOMString($where),
                $element
            );
        } catch (DOMException $e) {
            throw $e;
        }
    }

    /**
     * Parses the given string text as HTML or XML and inserts the resulting
     * nodes into the tree in the position given by the position argument, as
     * follows:
     *     - "beforebegin": Before the element itself.
     *     - "afterbegin": Just inside the element, before its first child.
     *     - "beforeend": Just inside the element, after its last child.
     *     - "afterend": After the element itself.
     *
     * NOTE: No special handling for template elements is included in the below
     * "afterbegin" and "beforeend" cases. As with other direct
     * Node-manipulation APIs (and unlike innerHTML), insertAdjacentHTML does
     * not include any special handling for template elements. In most cases you
     * will wish to use template.content.insertAdjacentHTML instead of directly
     * manipulating the child nodes of a template element.
     *
     * @see https://w3c.github.io/DOM-Parsing/#dom-element-insertadjacenthtml
     *
     * @param string $position One of the types listed above.
     *
     * @param string $text The markup text to parse and subsequently, insert
     *     relative to this element.
     *
     * @throws SyntaxError If the arguments contain invalid values, such as an
     *     XML string that is not well-formed, when in an XML Document.
     *
     * @throws NoModificationError If the given position for insertion isn't
     *     possible, such as trying to insert elements after the document.
     */
    public function insertAdjacentHTML($position, $text)
    {
        $position = \mb_strtolower(Utils::DOMString($position));

        if ($position === 'beforebegin' || $position === 'afterend') {
            // Let context be the context object's parent.
            $context = $this->parentNode;

            // If context is null or a Document, throw a
            // "NoModificationAllowedError" DOMException.
            if (!$context || $context instanceof Document) {
                throw new NoModificationAllowedError();
            }
        } elseif ($position === 'afterbegin' || $position === 'beforeend') {
            // Let context be the context object.
            $context = $this;
        } else {
            // Throw a "SyntaxError" DOMException.
            throw new SyntaxError();
        }

        // If the context is not an Element or the context's node document is an
        // HTML document, context's local name is "html", and context's
        // namespace is the HTML namespace. Let context be a new Element with
        // "body" as its local name, the HTML namespace as its namespace, and
        // the context object's node document as its node document.
        if (!($context instanceof Element)
            || ($context->ownerDocument instanceof HTMLDocument
                && $context->localName === 'html'
                && $context->namespaceURI === Namespaces::HTML)
        ) {
            $context = self::create(
                $this->ownerDocument,
                'body',
                Namespaces::HTML
            );
        }

        // Let fragment be the result of invoking the fragment parsing algorithm
        // with text as markup, and context as the context element.
        $fragment = ParserFactory::parseFragment(
            Utils::DOMString($text),
            $context
        );

        if ($position === 'beforebegin') {
            // Insert fragment into the context object's parent before the
            // context object.
            $this->parentNode->insertNode($fragment, $this);
        } elseif ($position === 'afterbegin') {
            // Insert fragment into the context object before its first child.
            $this->insertNode($fragment, $this->firstChild);
        } elseif ($position === 'beforeend') {
            // Append fragment to the context object.
            $this->appendChild($fragment);
        } elseif ($position === 'afterend') {
            // Insert fragment into the context object's parent before the
            // context object's next sibling.
            $this->parentNode->insertNode($fragment, $this->nextSibling);
        }
    }

    /**
     * Inserts plain text adjacent to the current element.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-insertadjacenttext
     *
     * @param string $where The position relative to this node. Possible
     *     values are:
     *         - beforebegin - Inserts an element as this element's previous
     *             sibling.
     *         - afterend - Inserts an element as this element's next sibling.
     *         - afterbegin - Inserts an element as this element's first child.
     *         - beforeend - Inserts an element as this element's last child.
     *
     * @param string $data The text to be inserted.
     */
    public function insertAdjacentText($where, $data)
    {
        $text = new Text($data);
        $text->nodeDocument = $this->nodeDocument;

        try {
            self::insertAdjacent(
                $this,
                Utils::DOMString($where),
                $text
            );
        } catch (DOMException $e) {
            throw $e;
        }
    }

    public function matches($selectorRule)
    {
        // TODO
    }

    /**
     * Removes an attribute with the specified name from the Element's attribute
     * list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-removeattribute
     *
     * @param string $name The attributes name.
     */
    public function removeAttribute($name)
    {
        $this->attributeList->removeAttrByName($name);
    }

    /**
     * Removes the given attribute from the Element's attribute list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-removeattributenode
     *
     * @param Attr $attr The attribute to be removed.
     *
     * @return Attr The Attr node that was removed.
     *
     * @throws NotFoundError
     */
    public function removeAttributeNode(Attr $attr)
    {
        if (!$this->attributeList->contains($attr)) {
            throw new NotFoundError();
        }

        $this->attributeList->remove($attr);

        return $attr;
    }

    /**
     * Removes the attribute with the given namespace and local name from the
     * Element's attribute list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-hasattributens
     *
     * @param string $namespace The namespaceURI of the attribute to be
     *     removed.
     *
     * @param string $localName The localName of the attribute to be removed.
     */
    public function removeAttributeNS($namespace, $localName)
    {
        $this->attributeList->removeAttrByNamespaceAndLocalName(
            $namespace,
            $localName
        );
    }

    /**
     * Either adds a new attribute to the Element's attribute list or it
     * modifies the value of an already existing attribute with the the same
     * name.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-setattribute
     *
     * @param string $qualifiedName  The name of the attribute.
     *
     * @param string $value The value of the attribute.
     */
    public function setAttribute($qualifiedName, $value)
    {
        $qualifiedName = Utils::DOMString($qualifiedName);

        // If qualifiedName does not match the Name production in XML,
        // throw an InvalidCharacterError exception.
        if (!\preg_match(Namespaces::NAME_PRODUCTION, $qualifiedName)) {
            throw new InvalidCharacterError();
        }

        if ($this->namespaceURI === Namespaces::HTML
            && $this->nodeDocument instanceof HTMLDocument
        ) {
            $qualifiedName = Utils::toASCIILowercase($qualifiedName);
        }

        $attribute = null;

        foreach ($this->attributeList as $attr) {
            if ($attr->name === $qualifiedName) {
                $attribute = $attr;
                break;
            }
        }

        if (!$attribute) {
            $attribute = new Attr($qualifiedName, Utils::DOMString($value));
            $attribute->setNodeDocument($this->nodeDocument);
            $this->attributeList->append($attribute);
            return;
        }

        $this->attributeList->change(
            $attr,
            Utils::DOMString($value)
        );
    }

    /**
     * Appends the given attribute to the Element's attribute list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-setattributenode
     *
     * @param Attr $attr The attribute to be appended.
     */
    public function setAttributeNode(Attr $attr)
    {
        try {
            return $this->attributeList->setAttr($attr);
        } catch (DOMException $e) {
            throw $e;
        }
    }

    /**
     * Appends the given namespaced attribute to the Element's attribute list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-setattributenodens
     *
     * @param Attr $attr The namespaced attribute to be appended.
     */
    public function setAttributeNodeNS(Attr $attr)
    {
        try {
            return $this->attributeList->setAttr($attr);
        } catch (DOMException $e) {
            throw $e;
        }
    }

    /**
     * Either appends a new attribute or modifies the value of an existing
     * attribute with the given namespace and name.
     *
     * @param string $namespace The namespaceURI of the attribute.
     *
     * @param string $name The name of the attribute.
     *
     * @param string $value The value of the attribute.
     */
    public function setAttributeNS($namespace, $name, $value)
    {
        try {
            list(
                $namespace,
                $prefix,
                $localName
            ) = Namespaces::validateAndExtract(
                Utils::DOMString($namespace, false, true),
                Utils::DOMString($name)
            );
        } catch (DOMException $e) {
            throw $e;
        }

        $this->attributeList->setAttrValue(
            $localName,
            $value,
            $prefix,
            $namespace
        );
    }

    /**
     * Returns the Node's length, which is the number of child nodes.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-node-length
     * @see Node::getLength()
     *
     * @return int
     */
    public function getLength()
    {
        return \count($this->childNodes);
    }

    /**
     * @see Node::getNodeName
     */
    protected function getNodeName()
    {
        return $this->getTagName();
    }

    /**
     * @see Node::getNodeValue
     */
    protected function getNodeValue()
    {
        return null;
    }

    /**
     * @see Node::getTextContent
     */
    protected function getTextContent()
    {
        $tw = new TreeWalker($this, NodeFilter::SHOW_TEXT);
        $data = '';

        while (($node = $tw->nextNode())) {
            $data .= $node->data;
        }

        return $data;
    }

    /**
     * Gets the element's tag name.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-element-tagname
     *
     * @return string
     */
    protected function getTagName()
    {
        $qualifiedName = $this->prefix === null
            ? $this->localName
            : $this->prefix . ':' . $this->localName;

        if ($this->namespaceURI === Namespaces::HTML
            && $this->nodeDocument instanceof HTMLDocument
        ) {
            return Utils::toASCIIUppercase($qualifiedName);
        }

        return $qualifiedName;
    }

    /**
     * Inserts a node adjacent to an element.
     *
     * @see https://dom.spec.whatwg.org/#insert-adjacent
     *
     * @param Element $aElement The context element.
     *
     * @param string $aWhere The position relative to this node. Possible
     *     values are:
     *         - beforebegin - Inserts an element as this element's previous
     *             sibling.
     *         - afterend - Inserts an element as this element's next sibling.
     *         - afterbegin - Inserts an element as this element's first child.
     *         - beforeend - Inserts an element as this element's last child.
     *
     * @param Node $aNode The node to be inserted adjacent to the element.
     *
     * @return null
     *
     * @throws SyntaxError If an invalid value for "where" is given.
     */
    protected static function insertAdjacent(
        Element $element,
        $where,
        Node $node
    ) {
        switch (\strtolower($where)) {
            case 'beforebegin':
                if (!$element->parentNode) {
                    return null;
                }

                try {
                    $element->parentNode->preinsertNode(
                        $node,
                        $element
                    );
                } catch (DOMException $e) {
                    throw $e;
                }

                break;

            case 'afterbegin':
                $element->preinsertNode(
                    $node,
                    $element->childNodes->first()
                );

                break;

            case 'beforeend':
                $element->preinsertNode($node, null);

                break;

            case 'afterend':
                if (!$element->parentNode) {
                    return null;
                }

                try {
                    $element->parentNode->preinsertNode(
                        $node,
                        $element->nextSibling
                    );
                } catch (DOMException $e) {
                    throw $e;
                }

                break;

            default:
                throw new SyntaxError();
        }
    }

    /**
     * Resolves a URL to the absolute URL that it implies.
     *
     * @see https://html.spec.whatwg.org/multipage/infrastructure.html#parse-a-url
     *
     * @internal
     *
     * @param string $url A URL string to be resolved.
     *
     * @param string $documentOrEnvironmentSetting Either a document or
     *     environment settings object that contain a base URL.
     *
     * @return mixed[]|bool An array containing the serialized absolute URL as
     *     well as the parsed URL or false on failure.
     */
    protected function parseURL($url, $documentOrEnvironmentSetting)
    {
        if ($documentOrEnvironmentSetting instanceof Document) {
            $encoding = $documentOrEnvironmentSetting->characterSet;
            $baseURL = $documentOrEnvironmentSetting->getBaseURL();
        } else {
            // TODO: Let encoding be the environment settings object'a API URL
            // character encoding.  Let baseURL be the environment settings
            // object's API base URL.
        }

        $urlRecord = URLParser::parseUrl(
            $url,
            $baseURL,
            $encoding
        );

        if ($urlRecord === false) {
            return false;
        }

        return [
            'urlRecord' => $urlRecord,
            'urlString' => $urlRecord->serializeURL()
        ];
    }

    protected function reflectURLAttributeValue(
        $name,
        $missingValueDefault = null
    ) {
        $attr = $this->attributeList->getAttrByNamespaceAndLocalName(
            null,
            $name
        );

        if ($attr) {
            $url = $this->parseURL(
                $attr->value,
                $this->nodeDocument
            );

            if ($url !== false) {
                return $url['urlString'];
            }
        } elseif ($missingValueDefault !== null) {
            return $missingValueDefault;
        }

        return '';
    }

    /**
     * Gets the value of an attribute that is to be reflected as an object
     * property.
     *
     * @link https://dom.spec.whatwg.org/#concept-reflect
     *
     * @param string $name The name of the attribute that is to be reflected.
     *
     * @return string
     */
    protected function reflectStringAttributeValue($name)
    {
        return $this->attributeList->getAttrValue($name);
    }

    /**
     * @see Node::setNodeValue
     */
    protected function setNodeValue($newValue)
    {
        // Do nothing.
    }

    /**
     * @see Node::setTextContent
     */
    protected function setTextContent($newValue)
    {
        $node = null;
        $newValue = Utils::DOMString($newValue, true);

        if ($newValue !== '') {
            $node = new Text($newValue);
            $node->nodeDocument = $this->nodeDocument;
        }

        $this->replaceAllNodes($node);
    }

    /**
     * Returns an array of Elements with the specified tagName that are
     * immediate children of the parent.
     *
     * @param string $tagName The tagName to search for.
     *
     * @return Element[] A list of Elements with the specified tagName.
     */
    protected function shallowGetElementsByTagName($tagName)
    {
        $collection = array();
        $node = $this->childNodes->first();
        $tagName = \strtoupper($tagName);

        while ($node) {
            if ($node->tagName === $tagName) {
                $collection[] = $node;
            }

            $node = $node->nextElementSibling;
        }

        return $collection;
    }
}
