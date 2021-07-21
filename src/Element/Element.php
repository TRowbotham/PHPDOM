<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element;

use Rowbot\DOM\Attr;
use Rowbot\DOM\AttributeChangeObserver;
use Rowbot\DOM\AttributeList;
use Rowbot\DOM\CDATASection;
use Rowbot\DOM\ChildNode;
use Rowbot\DOM\ChildNodeTrait;
use Rowbot\DOM\Document;
use Rowbot\DOM\DocumentFragment;
use Rowbot\DOM\DOMTokenList;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\Exception\NoModificationAllowedError;
use Rowbot\DOM\Exception\NotFoundError;
use Rowbot\DOM\Exception\SyntaxError;
use Rowbot\DOM\GetElementsBy;
use Rowbot\DOM\NamedNodeMap;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\NonDocumentTypeChildNode;
use Rowbot\DOM\ParentNode;
use Rowbot\DOM\ParentNodeTrait;
use Rowbot\DOM\Parser\MarkupFactory;
use Rowbot\DOM\Parser\ParserFactory;
use Rowbot\DOM\Text;
use Rowbot\DOM\URL\URLParser;
use Rowbot\DOM\Utils;

use function count;
use function func_num_args;
use function preg_match;
use function range;

/**
 * @see https://dom.spec.whatwg.org/#element
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Element
 *
 * @property string $className
 * @property string $id
 * @property string $innerHTML
 * @property string $outerHTML
 *
 * @property-read \Rowbot\DOM\DOMTokenList                                $classList
 * @property-read \Rowbot\DOM\NamedNodeMap                                $attributes
 * @property-read ?string                                                 $namespaceURI
 * @property-read ?string                                                 $prefix
 * @property-read string                                                  $localName
 * @property-read string                                                  $tagName
 * @property-read \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\Element> $children
 * @property-read \Rowbot\DOM\Element\Element|null                        $firstElementChild
 * @property-read \Rowbot\DOM\Element\Element|null                        $lastElementChild
 * @property-read int                                                     $childElementCount
 * @property-read \Rowbot\DOM\Element\Element|null                        $nextElementSibling
 * @property-read \Rowbot\DOM\Element\Element|null                        $previousElementSibling
 */
class Element extends Node implements AttributeChangeObserver, ChildNode, ParentNode
{
    use ChildNodeTrait {
        ChildNodeTrait::convertNodesToNode insteadof ParentNodeTrait;
    }
    use GetElementsBy;
    use NonDocumentTypeChildNode;
    use ParentNodeTrait;

    /**
     * @var \Rowbot\DOM\NamedNodeMap
     */
    protected $namedNodeMap;

    /**
     * @var \Rowbot\DOM\AttributeList
     */
    protected $attributeList;

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
     * @var \Rowbot\DOM\DOMTokenList|null
     */
    private $classList_;

    public function __construct(Document $document, string $localName, ?string $namespace, ?string $prefix = null)
    {
        parent::__construct($document);

        $this->attributeList = new AttributeList($this);
        $this->localName = $localName;
        $this->namedNodeMap = new NamedNodeMap($this);
        $this->namespaceURI = $namespace;
        $this->nodeType = self::ELEMENT_NODE;
        $this->prefix = $prefix;
        $this->attributeList->observe($this);
    }

    /**
     * {@inheritDoc}
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'attributes':
                return $this->namedNodeMap;

            case 'childElementCount':
                return $this->getChildElementCount();

            case 'children':
                return $this->getChildren();

            case 'classList':
                return $this->getClassList();

            case 'className':
                return $this->attributeList->getAttrValue('class');

            case 'firstElementChild':
                return $this->getFirstElementChild();

            case 'id':
                return $this->attributeList->getAttrValue($name);

            case 'innerHTML':
                // https://w3c.github.io/DOM-Parsing/#the-innerhtml-mixin
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
                $fakeNode = ElementFactory::create($this->nodeDocument, 'fake', Namespaces::HTML);
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
    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'classList':
                $this->getClassList()->value = (string) $value;

                break;

            case 'className':
                $this->attributeList->setAttrValue('class', (string) $value);

                break;

            case 'id':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'innerHTML':
                // https://w3c.github.io/DOM-Parsing/#the-innerhtml-mixin
                if ($value === null) {
                    $value = '';
                }

                // 2. Let fragment be the result of invoking the fragment parsing algorithm with the
                // new value as markup, and with context element.
                $fragment = ParserFactory::parseFragment((string) $value, $this);

                // 3. If the context object is a template element, then let context object be the
                // template's template contents (a DocumentFragment).
                $context = $this instanceof HTMLTemplateElement ? $this->content : $this;

                // NOTE: Setting innerHTML on a template element will replace all the nodes in its
                // template contents (template.content) rather than its children.

                // 4. Replace all with fragment within the context object.
                $context->replaceAllNodes($fragment);

                break;

            case 'outerHTML':
                if ($value === null) {
                    $value = '';
                }

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
                    $parent = ElementFactory::create($this->nodeDocument, 'body', Namespaces::HTML);
                }

                // Let fragment be the result of invoking the fragment parsing
                // algorithm with the new value as markup, and parent as the
                // context element.
                $fragment = ParserFactory::parseFragment((string) $value, $parent);

                // Replace the context object with fragment within the context
                // object's parent.
                $this->parentNode->replaceNode($fragment, $this);

                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Returns true if there are attributes present in the Element's attribute
     * list, otherwise false.
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
     * @return list<string>
     */
    public function getAttributeNames(): array
    {
        $list = [];

        foreach ($this->attributeList as $attr) {
            $list[] = $attr->getQualifiedName();
        }

        return $list;
    }

    /**
     * Retrieves the value of the attribute with the given qualifiedName, if any.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-getattribute
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
     */
    public function getAttributeNS(?string $namespace, string $localName): ?string
    {
        $attr = $this->attributeList->getAttrByNamespaceAndLocalName($namespace, $localName);

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
     */
    public function setAttribute(string $qualifiedName, string $value): void
    {
        // If qualifiedName does not match the Name production in XML,
        // throw an InvalidCharacterError exception.
        if (!preg_match(Namespaces::NAME_PRODUCTION, $qualifiedName)) {
            throw new InvalidCharacterError();
        }

        if (
            $this->namespaceURI === Namespaces::HTML
            && $this->nodeDocument->isHTMLDocument()
        ) {
            $qualifiedName = Utils::toASCIILowercase($qualifiedName);
        }

        $attribute = null;

        foreach ($this->attributeList as $attr) {
            if ($attr->getQualifiedName() === $qualifiedName) {
                $attribute = $attr;

                break;
            }
        }

        if ($attribute === null) {
            $attribute = new Attr($this->nodeDocument, $qualifiedName, $value);
            $this->attributeList->append($attribute);

            return;
        }

        $this->attributeList->change($attribute, $value);
    }

    /**
     * Either appends a new attribute or modifies the value of an existing
     * attribute with the given namespace and qualifiedName.
     */
    public function setAttributeNS(?string $namespace, string $qualifiedName, string $value): void
    {
        [$namespace, $prefix, $localName] = Namespaces::validateAndExtract(
            $namespace,
            $qualifiedName
        );

        $this->attributeList->setAttrValue($localName, $value, $prefix, $namespace);
    }

    /**
     * Removes an attribute with the specified qualifiedName from the Element's attribute
     * list.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-removeattribute
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
     */
    public function removeAttributeNS(?string $namespace, string $localName): void
    {
        $this->attributeList->removeAttrByNamespaceAndLocalName($namespace, $localName);
    }

    /**
     * Toggles the presence of an attribute. If force is not given, it toggles the attribute with qualifiedName,
     * removing it if it is present and adding it if it is not present. If force is true, it adds an attribute with
     * qualifiedName. If force is false, it removes the attribute with qualifiedName.
     *
     * @throws \Rowbot\DOM\Exception\InvalidCharacterError
     */
    public function toggleAttribute(string $qualifiedName, bool $force = false): bool
    {
        if (!preg_match(Namespaces::NAME_PRODUCTION, $qualifiedName)) {
            throw new InvalidCharacterError();
        }

        if (
            $this->namespaceURI === Namespaces::HTML
            && $this->nodeDocument->isHTMLDocument()
        ) {
            $qualifiedName = Utils::toASCIILowercase($qualifiedName);
        }

        $attribute = null;
        $forceIsGiven = func_num_args() > 1;

        foreach ($this->attributeList as $attr) {
            if ($attr->getQualifiedName() === $qualifiedName) {
                $attribute = $attr;

                break;
            }
        }

        if ($attribute === null) {
            if ($forceIsGiven === false || $force === true) {
                $attribute = new Attr($this->nodeDocument, $qualifiedName, '');
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
     */
    public function hasAttribute(string $qualifiedName): bool
    {
        if (
            $this->namespaceURI === Namespaces::HTML
            && $this->nodeDocument->isHTMLDocument()
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
     */
    public function hasAttributeNS(?string $namespace, string $localName): bool
    {
        return $this->attributeList->getAttrByNamespaceAndLocalName(
            $namespace,
            $localName
        ) !== null;
    }

    public function isEqualNode(?Node $otherNode): bool
    {
        if (
            $otherNode === null
            || $otherNode->nodeType !== $this->nodeType
            || !$otherNode instanceof self
            || $otherNode->namespaceURI !== $this->namespaceURI
            || $otherNode->prefix !== $this->prefix
            || $otherNode->localName !== $this->localName
        ) {
            return false;
        }

        $numAttributes = $this->attributeList->count();

        if ($numAttributes !== $otherNode->attributeList->count()) {
            return false;
        }

        // If A is an element, each attribute in its attribute list has an attribute that equals an
        // attribute in B’s attribute list.
        $indexes = range(0, $numAttributes - 1);

        foreach ($this->attributeList as $attribute) {
            foreach ($indexes as $i) {
                if ($attribute->isEqualNode($otherNode->attributeList[$i])) {
                    unset($indexes[$i]);

                    continue 2;
                }
            }

            return false;
        }

        return $this->hasEqualChildNodes($otherNode);
    }

    /**
     * Retrieves the attribute node with the given qualifiedName, if any.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-getattributenode
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
     */
    public function getAttributeNodeNS(?string $namespace, string $localName): ?Attr
    {
        return $this->attributeList->getAttrByNamespaceAndLocalName($namespace, $localName);
    }

    /**
     * Appends the given attribute to the Element's attribute list.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-setattributenode
     */
    public function setAttributeNode(Attr $attr): ?Attr
    {
        return $this->attributeList->setAttr($attr);
    }

    /**
     * Appends the given namespaced attribute to the Element's attribute list.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-setattributenodens
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

    /**
     * Inserts an element adjacent to the current element.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-insertadjacentelement
     *
     * @param string $where The position relative to this node. Possible values are:
     *                          - beforebegin - Inserts an element as this element's previous sibling.
     *                          - afterend - Inserts an element as this element's next sibling.
     *                          - afterbegin - Inserts an element as this element's first child.
     *                          - beforeend - Inserts an element as this element's last child.
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
     */
    public function insertAdjacentText(string $where, string $data): void
    {
        $text = new Text($this->nodeDocument, $data);
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
        if (
            !$context instanceof self
            || ($context->getNodeDocument()->isHTMLDocument()
                && $context->localName === 'html'
                && $context->namespaceURI === Namespaces::HTML)
        ) {
            $context = ElementFactory::create($this->nodeDocument, 'body', Namespaces::HTML);
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
     */
    public function getAttributeList(): AttributeList
    {
        return $this->attributeList;
    }

    /**
     * @see https://dom.spec.whatwg.org/#concept-element-qualified-name
     *
     * @internal
     */
    public function getQualifiedName(): string
    {
        return $this->prefix === null
            ? $this->localName
            : $this->prefix . ':' . $this->localName;
    }

    /**
     * Gets the element's tag name.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#element-html-uppercased-qualified-name
     */
    protected function getTagName(): string
    {
        $qualifiedName = $this->getQualifiedName();

        if (
            $this->namespaceURI === Namespaces::HTML
            && $this->nodeDocument->isHTMLDocument()
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
     * @throws \Rowbot\DOM\Exception\SyntaxError If an invalid value for "where" is given.
     */
    private function insertAdjacent(Element $element, string $where, Node $node): ?Node
    {
        $where = Utils::toASCIILowercase($where);

        if ($where === 'beforebegin') {
            if ($element->parentNode === null) {
                return null;
            }

            return $element->parentNode->preinsertNode($node, $element);
        }

        if ($where === 'afterbegin') {
            return $element->preinsertNode($node, $element->childNodes->first());
        }

        if ($where === 'beforeend') {
            return $element->preinsertNode($node, null);
        }

        if ($where === 'afterend') {
            if ($element->parentNode === null) {
                return null;
            }

            return $element->parentNode->preinsertNode($node, $element->nextSibling);
        }

        throw new SyntaxError();
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
    protected function parseURL(string $url, $documentOrEnvironmentSetting)
    {
        if ($documentOrEnvironmentSetting instanceof Document) {
            $encoding = $documentOrEnvironmentSetting->characterSet;
            $baseURL = $documentOrEnvironmentSetting->getBaseURL();
        } else {
            // TODO: Let encoding be the environment settings object'a API URL
            // character encoding.  Let baseURL be the environment settings
            // object's API base URL.
        }

        $urlRecord = URLParser::parseUrl($url, $baseURL, $encoding);

        if ($urlRecord === false) {
            return false;
        }

        return ['urlRecord' => $urlRecord, 'urlString' => $urlRecord->serializeURL()];
    }

    /**
     * Gets the value of an attribute that is to be reflected as an object
     * property.
     *
     * @see https://dom.spec.whatwg.org/#concept-reflect
     */
    protected function reflectStringAttributeValue(string $name): string
    {
        return $this->attributeList->getAttrValue($name);
    }

    public function onAttributeChanged(
        Element $element,
        string $localName,
        ?string $oldValue,
        ?string $value,
        ?string $namespace
    ): void {
        // We currently don't do anything special with the element's ID.
        if (
            $localName === 'id'
            && $namespace === null
            && ($value === null || $value === '')
        ) {
            // Unset the element's ID.
        } elseif ($localName === 'id' && $namespace === null) {
            // Set the element's ID to $value.
        }
    }

    public function getLength(): int
    {
        return count($this->childNodes);
    }

    protected function getClassList(): DOMTokenList
    {
        if ($this->classList_ === null) {
            $this->classList_ = new DOMTokenList($this, 'class');
        }

        return $this->classList_;
    }

    protected function getNodeName(): string
    {
        return $this->getTagName();
    }

    protected function getNodeValue(): ?string
    {
        return null;
    }

    protected function getTextContent(): string
    {
        $node = $this->nextNode($this);
        $data = '';

        while ($node) {
            if ($node instanceof Text && !$node instanceof CDATASection) {
                $data .= $node->data;
            }

            $node = $node->nextNode($this);
        }

        return $data;
    }

    protected function setNodeValue(?string $value): void
    {
        // Do nothing.
    }

    protected function setTextContent(?string $value): void
    {
        if ($value === null) {
            $value = '';
        }

        $node = null;

        if ($value !== '') {
            $node = new Text($this->nodeDocument, $value);
        }

        $this->replaceAllNodes($node);
    }

    protected function __clone()
    {
        parent::__clone();
        $attributeList = new AttributeList($this);

        foreach ($this->attributeList as $attr) {
            $attributeList->append(clone $attr);
        }

        $this->attributeList = $attributeList;
        $this->classList_ = null;
        $this->namedNodeMap = new NamedNodeMap($this);
        $this->attributeList->observe($this);
    }
}
