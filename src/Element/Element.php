<?php
namespace Rowbot\DOM\Element;

use Rowbot\DOM\{
    Attr,
    AttributeChangeObserver,
    AttributeList,
    ChildNode,
    Document,
    DocumentFragment,
    DOMTokenList,
    Exception\DOMException,
    Exception\InUseAttributeError,
    Exception\InvalidCharacterError,
    Exception\NoModificationAllowedError,
    Exception\NotFoundError,
    Exception\SyntaxError,
    GetElementsBy,
    HTMLDocument,
    NamedNodeMap,
    Namespaces,
    Node,
    NodeFilter,
    NonDocumentTypeChildNode,
    ParentNode,
    Parser\MarkupFactory,
    Parser\ParserFactory,
    Text,
    TreeWalker,
    URL\URLParser,
    Utils
};
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;

use function count;
use function preg_match;
use function strtoupper;

/**
 * @see https://dom.spec.whatwg.org/#element
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Element
 *
 * @property-read ?string                  $namespaceURI
 * @property-read ?string                  $prefix
 * @property-read string                   $localName
 * @property-read string                   $tagName
 * @property      string                   $id
 * @property      string                   $className
 * @property-read \Rowbot\DOM\DOMTokenList $classList
 * @property-read \Rowbot\DOM\NamedNodeMap $attributes
 * @property      string                   $innerHTML
 * @property      string                   $outerHTML
 */
class Element extends Node implements AttributeChangeObserver
{
    use ChildNode, ParentNode {
        ChildNode::convertNodesToNode insteadof ParentNode;
    }
    use GetElementsBy;
    use NonDocumentTypeChildNode;

    /**
     * @var \Rowbot\DOM\NamedNodeMap
     */
    protected $namedNodeMap;

    /**
     * @var \Rowbot\DOM\AttributeList
     */
    protected $attributeList;

    /**
     * @var \Rowbot\DOM\DOMTokenList
     */
    protected $classList;

    /**
     * @var string
     */
    protected $localName;

    /**
     * @var ?string
     */
    protected $namespaceURI;

    /**
     * @var ?string
     */
    protected $prefix;

    /**
     * Constructor.
     *
     * @return void
     */
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

    /**
     * {@inheritDoc}
     */
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
                $fakeNode = self::create(
                    $this->ownerDocument,
                    'fake',
                    Namespaces::HTML
                );
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

    /**
     * {@inheritDoc}
     */
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
     * Creates a new instance of an the specified element interface and
     * intializes that elements local name, namespace, and namespace prefix.
     *
     * @internal
     *
     * @param \Rowbot\DOM\Document|null $document  The element's owner document.
     * @param string                    $localName The element's local name that you are creating.
     * @param ?string                   $namespace The namespace that the element belongs to.
     * @param ?string                   $prefix    (optional) The namespace prefix of the element.
     *
     * @return self
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
     * Returns true if there are attributes present in the Element's attribute
     * list, otherwise false.
     *
     * @return bool
     */
    public function hasAttributes(): bool
    {
        return !$this->attributeList->isEmpty();
    }

    /**
     * Returns a list of all attribute names in order.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-getattributenames
     *
     * @return string[]
     */
    public function getAttributeNames(): array
    {
        $list = [];

        foreach ($this->attributeList as $attr) {
            $list[] = $attr->name;
        }

        return $list;
    }

    /**
     * Retrieves the value of the attribute with the given qualifiedName, if any.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-getattribute
     *
     * @param string $qualifiedName The qualifiedName of the attribute whose value is to be retrieved.
     *
     * @return ?string
     */
    public function getAttribute(string $qualifiedName): ?string
    {
        $attr = $this->attributeList->getAttrByName($qualifiedName);

        if ($attr === null) {
            return null;
        }

        return $attr->value;
    }

    /**
     * Retrieves the value of the attribute with the given namespace and local name, if any.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-getattributens
     *
     * @param ?string $namespace The namespaceURI of the attribute whose value is to be retrieved.
     * @param string  $localName The localName of the attribute whose value is to be retrieved.
     *
     * @return ?string
     */
    public function getAttributeNS(
        ?string $namespace,
        string $localName
    ): ?string {
        $attr = $this->attributeList->getAttrByNamespaceAndLocalName(
            $namespace,
            $localName
        );

        if ($attr === null) {
            return null;
        }

        return $attr->value;
    }

    /**
     * Either adds a new attribute to the Element's attribute list or it
     * modifies the value of an already existing attribute with the the same
     * name.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-setattribute
     *
     * @param string $qualifiedName The name of the attribute.
     * @param string $value         The value of the attribute.
     *
     * @return void
     */
    public function setAttribute(string $qualifiedName, string $value): void
    {
        // If qualifiedName does not match the Name production in XML,
        // throw an InvalidCharacterError exception.
        if (!preg_match(Namespaces::NAME_PRODUCTION, $qualifiedName)) {
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

        if ($attribute === null) {
            $attribute = new Attr($qualifiedName, $value);
            $attribute->setNodeDocument($this->nodeDocument);
            $this->attributeList->append($attribute);
            return;
        }

        $this->attributeList->change($attribute, $value);
    }

    /**
     * Either appends a new attribute or modifies the value of an existing
     * attribute with the given namespace and qualifiedName.
     *
     * @param ?string $namespace     The namespaceURI of the attribute.
     * @param string  $qualifiedName The qualifiedName of the attribute.
     * @param string  $value         The value of the attribute.
     *
     * @return void
     */
    public function setAttributeNS(
        ?string $namespace,
        string $qualifiedName,
        string $value
    ): void {
        list(
            $namespace,
            $prefix,
            $localName
        ) = Namespaces::validateAndExtract(
            $namespace,
            $qualifiedName
        );

        $this->attributeList->setAttrValue(
            $localName,
            $value,
            $prefix,
            $namespace
        );
    }

    /**
     * Removes an attribute with the specified qualifiedName from the Element's attribute
     * list.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-removeattribute
     *
     * @param string $qualifiedName The attributes qualifiedName.
     *
     * @return void
     */
    public function removeAttribute(string $qualifiedName): void
    {
        $this->attributeList->removeAttrByName($qualifiedName);
    }

    /**
     * Removes the attribute with the given namespace and local name from the
     * Element's attribute list.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-hasattributens
     *
     * @param ?string $namespace The namespaceURI of the attribute to be removed.
     * @param string  $localName The localName of the attribute to be removed.
     *
     * @return void
     */
    public function removeAttributeNS(
        ?string $namespace,
        string $localName
    ): void {
        $this->attributeList->removeAttrByNamespaceAndLocalName(
            $namespace,
            $localName
        );
    }

    /**
     * Toggles the presence of an attribute. If force is not given, it toggles the attribute with qualifiedName,
     * removing it if it is present and adding it if it is not present. If force is true, it adds an attribute with
     * qualifiedName. If force is false, it removes the attribute with qualifiedName.
     *
     * @param string $qualifiedName
     * @param bool   $force         (optional)
     *
     * @return bool
     *
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError
     */
    public function toggleAttribute(
        string $qualifiedName,
        bool $force = false
    ): bool {
        if (!preg_match(Namespaces::NAME_PRODUCTION, $qualifiedName)) {
            throw new InvalidCharacterError();
        }

        if ($this->namespaceURI === Namespaces::HTML
            && $this->nodeDocument instanceof HTMLDocument
        ) {
            $qualifiedName = Utils::toASCIILowercase($qualifiedName);
        }

        $attribute = null;
        $forceIsGiven = func_num_args() > 1;

        foreach ($this->attributeList as $attr) {
            if ($attr->name === $qualifiedName) {
                $attribute = $attr;
                break;
            }
        }

        if ($attribute === null) {
            if ($forceIsGiven === false || $force === true) {
                $attribute = new Attr($qualifiedName, '');
                $attribute->setNodeDocument($this->nodeDocument);
                $this->attributeList->append($attribute);

                return true;
            }

            return false;
        }

        if ($forceIsGiven === false || $force === false) {
            $this->attributeList->removeAttrByName($qualifiedName);

            return false;
        }

        return true;
    }

    /**
     * Returns true if the attribute with the given name is present in the
     * Element's attribute list, otherwise false.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-hasattribute
     *
     * @param string $qualifiedName The name of the attribute to find.
     *
     * @return bool
     */
    public function hasAttribute(string $qualifiedName): bool
    {
        if ($this->namespaceURI === Namespaces::HTML
            && $this->nodeDocument instanceof HTMLDocument
        ) {
            $qualifiedName = Utils::toASCIILowercase($qualifiedName);
        }

        return $this->attributeList->getAttrByName($qualifiedName) !== null;
    }

    /**
     * Returns true if the attribute with the given namespace and localName
     * is present in the Element's attribute list, otherwise false.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-hasattributens
     *
     * @param ?string $namespace The namespace of the attribute to find.
     * @param string  $localName The localName of the attribute to find.
     *
     * @return bool
     */
    public function hasAttributeNS(?string $namespace, string $localName): bool
    {
        return $this->attributeList->getAttrByNamespaceAndLocalName(
            $namespace,
            $localName
        ) !== null;
    }

    /**
     * Retrieves the attribute node with the given qualifiedName, if any.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-getattributenode
     *
     * @param string $qualifiedName The qualifiedName of the attribute that is to be retrieved.
     *
     * @return \Rowbot\DOM\Attr|null
     */
    public function getAttributeNode(string $qualifiedName): ?Attr
    {
        return $this->attributeList->getAttrByName($qualifiedName);
    }

    /**
     * Retrieves the attribute node with the given namespace and local name, if
     * any.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-getattributenodens
     *
     * @param ?string $namespace The namespaceURI of the attribute node to be retrieved.
     * @param string  $localName The localName of the attribute node to be retrieved.
     *
     * @return \Rowbot\DOM\Attr|null
     */
    public function getAttributeNodeNS(
        ?string $namespace,
        string $localName
    ): ?Attr {
        return $this->attributeList->getAttrByNamespaceAndLocalName(
            $namespace,
            $localName
        );
    }

    /**
     * Appends the given attribute to the Element's attribute list.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-setattributenode
     *
     * @param \Rowbot\DOM\Attr $attr The attribute to be appended.
     *
     * @return \Rowbot\DOM\Attr|null
     */
    public function setAttributeNode(Attr $attr): ?Attr
    {
        return $this->attributeList->setAttr($attr);
    }

    /**
     * Appends the given namespaced attribute to the Element's attribute list.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-setattributenodens
     *
     * @param \Rowbot\DOM\Attr $attr The namespaced attribute to be appended.
     *
     * @return \Rowbot\DOM\Attr|null
     */
    public function setAttributeNodeNS(Attr $attr): ?Attr
    {
        return $this->attributeList->setAttr($attr);
    }

    /**
     * Removes the given attribute from the Element's attribute list.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-removeattributenode
     *
     * @param \Rowbot\DOM\Attr $attr The attribute to be removed.
     *
     * @return \Rowbot\DOM\Attr The Attr node that was removed.
     *
     * @throws \Rowbot\DOM\Exception\NotFoundError
     */
    public function removeAttributeNode(Attr $attr): Attr
    {
        if (!$this->attributeList->contains($attr)) {
            throw new NotFoundError();
        }

        $this->attributeList->remove($attr);

        return $attr;
    }

    public function closest($selectorRule)
    {
        // TODO
    }

    public function matches($selectorRule)
    {
        // TODO
    }

    /**
     * Inserts an element adjacent to the current element.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-insertadjacentelement
     *
     * @param string $where  The position relative to this node. Possible values are:
     *                          - beforebegin - Inserts an element as this element's previous sibling.
     *                          - afterend - Inserts an element as this element's next sibling.
     *                          - afterbegin - Inserts an element as this element's first child.
     *                          - beforeend - Inserts an element as this element's last child.
     * @param self   $element The element to be inserted.
     *
     * @return ?self
     */
    public function insertAdjacentElement(string $where, self $element): ?self
    {
        return $this->insertAdjacent($this, $where, $element);
    }

    /**
     * Inserts plain text adjacent to the current element.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-insertadjacenttext
     *
     * @param string $where The position relative to this node. Possible values are:
     *                          - beforebegin - Inserts an element as this element's previous sibling.
     *                          - afterend - Inserts an element as this element's next sibling.
     *                          - afterbegin - Inserts an element as this element's first child.
     *                          - beforeend - Inserts an element as this element's last child.
     *
     * @param string $data  The text to be inserted.
     *
     * @return void
     */
    public function insertAdjacentText(string $where, string $data): void
    {
        $text = new Text($data);
        $text->setNodeDocument($this->nodeDocument);
        $this->insertAdjacent($this, $where, $text);
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
     * @see https://w3c.github.io/DOM-Parsing/#dfn-insertadjacenthtml
     *
     * @param string $position One of the types listed above.
     * @param string $text     The markup text to parse and subsequently, insert relative to this element.
     *
     * @return void
     *
     * @throws \Rowbot\DOM\Exception\SyntaxError                If the arguments contain invalid values, such as an XML
     *                                                          string that is not well-formed, when in an XML Document.
     * @throws \Rowbot\DOM\Exception\NoModificationAllowedError Error If the given position for insertion isn't
     *                                                          possible, such as trying to insert elements after the
     *                                                          document.
     */
    public function insertAdjacentHTML(string $position, string $text): void
    {
        $position = Utils::toASCIILowercase($position);

        if ($position === 'beforebegin' || $position === 'afterend') {
            // Let context be the context object's parent.
            $context = $this->parentNode;

            // If context is null or a Document, throw a
            // "NoModificationAllowedError" DOMException.
            if ($context === null || $context instanceof Document) {
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
        if (!$context instanceof self
            || ($context->getNodeDocument() instanceof HTMLDocument
                && $context->localName === 'html'
                && $context->namespaceURI === Namespaces::HTML)
        ) {
            $context = self::create(
                $this->nodeDocument,
                'body',
                Namespaces::HTML
            );
        }

        // Let fragment be the result of invoking the fragment parsing algorithm
        // with text as markup, and context as the context element.
        $fragment = ParserFactory::parseFragment($text, $context);

        if ($position === 'beforebegin') {
            // Insert fragment into the context object's parent before the
            // context object.
            $this->parentNode->preinsertNode($fragment, $this);
        } elseif ($position === 'afterbegin') {
            // Insert fragment into the context object before its first child.
            $this->preinsertNode($fragment, $this->firstChild);
        } elseif ($position === 'beforeend') {
            // Append fragment to the context object.
            $this->preinsertNode($fragment, null);
        } elseif ($position === 'afterend') {
            // Insert fragment into the context object's parent before the
            // context object's next sibling.
            $this->parentNode->preinsertNode($fragment, $this->nextSibling);
        }
    }

    /**
     * Returns the element's internal attribute list.
     *
     * @internal
     *
     * @return \Rowbot\DOM\AttributeList
     */
    public function getAttributeList()
    {
        return $this->attributeList;
    }

    /**
     * Gets the element's tag name.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#element-html-uppercased-qualified-name
     *
     * @return string
     */
    protected function getTagName(): string
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
     * @param self             $element The context element.
     * @param string           $where   The position relative to this node. Possible values are:
     *                                      - beforebegin - Inserts an element as this element's previous sibling.
     *                                      - afterend - Inserts an element as this element's next sibling.
     *                                      - afterbegin - Inserts an element as this element's first child.
     *                                      - beforeend - Inserts an element as this element's last child.
     * @param \Rowbot\DOM\Node $node    The node to be inserted adjacent to the element.
     *
     * @return \Rowbot\DOM\Node|null
     *
     * @throws \Rowbot\DOM\Exception\SyntaxError If an invalid value for "where" is given.
     */
    private function insertAdjacent(
        Element $element,
        string $where,
        Node $node
    ): ?Node {
        $where = Utils::toASCIILowercase($where);

        if ($where === 'beforebegin') {
            if ($element->parentNode === null) {
                return null;
            }

            return $element->parentNode->preinsertNode($node, $element);
        }

        if ($where === 'afterbegin') {
            return $element->preinsertNode(
                $node,
                $element->childNodes->first()
            );
        }

        if ($where === 'beforeend') {
            return $element->preinsertNode($node, null);
        }

        if ($where === 'afterend') {
            if ($element->parentNode === null) {
                return null;
            }

            return $element->parentNode->preinsertNode(
                $node,
                $element->nextSibling
            );
        }

        throw new SyntaxError();
    }

    /**
     * Returns an array of Elements with the specified tagName that are
     * immediate children of the parent.
     *
     * @param string $tagName The tagName to search for.
     *
     * @return \Rowbot\DOM\Element\Element[] A list of Elements with the specified tagName.
     */
    protected function shallowGetElementsByTagName($tagName)
    {
        $collection = [];
        $node = $this->childNodes->first();
        $tagName = strtoupper($tagName);

        while ($node) {
            if ($node->tagName === $tagName) {
                $collection[] = $node;
            }

            $node = $node->nextElementSibling;
        }

        return $collection;
    }

    /**
     * Resolves a URL to the absolute URL that it implies.
     *
     * @see https://html.spec.whatwg.org/multipage/infrastructure.html#parse-a-url
     *
     * @internal
     *
     * @param string $url                                        A URL string to be resolved.
     * @param \Rowbot\DOM\Document $documentOrEnvironmentSetting Either a document or environment settings object that
     *                                                           contain a base URL.
     *
     * @return array<string, mixed>|false An array containing the serialized absolute URL as well as the parsed URL or
     *                                    false on failure.
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

    /**
     * Gets the value of an attribute that is to be reflected as an object
     * property.
     *
     * @see https://dom.spec.whatwg.org/#concept-reflect
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
     * {@inheritDoc}
     */
    public function cloneNodeInternal(
        Document $document = null,
        bool $cloneChildren = false
    ): Node {
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

    /**
     * {@inheritDoc}
     */
    public function onAttributeChanged(
        Element $element,
        $localName,
        $oldValue,
        $value,
        $namespace
    ): void {
        // Do nothing.
    }

    /**
     * {@inheritDoc}
     */
    public function getLength(): int
    {
        return count($this->childNodes);
    }

    /**
     * {@inheritDoc}
     */
    protected function getNodeName(): string
    {
        return $this->getTagName();
    }

    /**
     * {@inheritDoc}
     */
    protected function getNodeValue(): ?string
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    protected function getTextContent(): string
    {
        $tw = new TreeWalker($this, NodeFilter::SHOW_TEXT);
        $data = '';

        while (($node = $tw->nextNode())) {
            $data .= $node->data;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    protected function setNodeValue($newValue): void
    {
        // Do nothing.
    }

    /**
     * {@inheritDoc}
     */
    protected function setTextContent($newValue): void
    {
        $node = null;
        $newValue = Utils::DOMString($newValue, true);

        if ($newValue !== '') {
            $node = new Text($newValue);
            $node->nodeDocument = $this->nodeDocument;
        }

        $this->replaceAllNodes($node);
    }
}
