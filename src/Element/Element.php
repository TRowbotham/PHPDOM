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

    protected $mNamedNodeMap;
    protected $mAttributesList;
    protected $mClassList; // ClassList
    protected $mLocalName;
    protected $mNamespaceURI;
    protected $mPrefix;

    protected function __construct()
    {
        parent::__construct();

        $this->mAttributesList = new AttributeList($this);
        $this->mClassList = new DOMTokenList($this, 'class');
        $this->mLocalName = '';
        $this->mNamedNodeMap = new NamedNodeMap($this);
        $this->mNamespaceURI = null;
        $this->mNodeType = self::ELEMENT_NODE;
        $this->mPrefix = null;
        $this->mAttributesList->observe($this);
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'attributes':
                return $this->mNamedNodeMap;

            case 'childElementCount':
                return $this->getChildElementCount();

            case 'children':
                return $this->getChildren();

            case 'classList':
                return $this->mClassList;

            case 'className':
                return $this->mAttributesList->getAttrValue('class');

            case 'firstElementChild':
                return $this->getFirstElementChild();

            case 'id':
                return $this->mAttributesList->getAttrValue($aName);

            case 'innerHTML':
                // On getting, return the result of invoking the fragment
                // serializing algorithm on the context object providing true
                // for the require well-formed flag (this might throw an
                // exception instead of returning a string).
                return MarkupFactory::serializeFragment($this, true);

            case 'lastElementChild':
                return $this->getLastElementChild();

            case 'localName':
                return $this->mLocalName;

            case 'outerHTML':
                // On getting, return the result of invoking the fragment
                // serializing algorithm on a fictional node whose only child is
                // the context object providing true for the require well-formed
                // flag (this might throw an exception instead of returning a
                // string).
                $fakeNode = self::create($this, 'fake', Namespaces::HTML);
                $fakeNode->mOwnerDocument = $this->mOwnerDocument;
                $fakeNode->mChildNodes->append($this);

                return MarkupFactory::serializeFragment($fakeNode, true);

            case 'namespaceURI':
                return $this->mNamespaceURI;

            case 'nextElementSibling':
                return $this->getNextElementSibling();

            case 'prefix':
                return $this->mPrefix;

            case 'previousElementSibling':
                return $this->getPreviousElementSibling();

            case 'tagName':
                return $this->getTagName();

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'classList':
                $this->mClassList->value = $aValue;

                break;

            case 'className':
                $this->mAttributesList->setAttrValue(
                    'class',
                    Utils::DOMString($aValue)
                );

                break;

            case 'id':
                $this->mAttributesList->setAttrValue(
                    $aName,
                    Utils::DOMString($aValue)
                );

                break;

            case 'innerHTML':
                // Let fragment be the result of invoking the fragment parsing
                // algorithm with the new value as markup, and the context
                // object as the context element.
                $fragment = ParserFactory::parseFragment(
                    Utils::DOMString($aValue, true),
                    $this
                );

                // If the context object is a template element, then let context
                // object be the template's template contents (a
                // DocumentFragment).
                $context = $this instanceof HTMLTemplateElement ?
                    $this->mContent : $this;

                // NOTE: Setting innerHTML on a template element will replace
                // all the nodes in its template contents (template.content)
                // rather than its children.

                // Replace all with fragment within the context object.
                $this->_replaceAll($fragment);

                break;

            case 'outerHTML':
                // Let parent be the context object's parent.
                $parent = $this->mParentNode;

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
                        $this->mOwnerDocument,
                        'body',
                        Namespaces::HTML
                    );
                }

                // Let fragment be the result of invoking the fragment parsing
                // algorithm with the new value as markup, and parent as the
                // context element.
                $fragment = ParserFactory::parseFragment(
                    Utils::DOMString($aValue, true),
                    $parent
                );

                // Replace the context object with fragment within the context
                // object's parent.
                $this->mParentNode->replaceNode($fragment, $this);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    public function closest($aSelectorRule)
    {
        // TODO
    }

    /**
     * Creates a new instance of an the specified element interface and
     * intializes that elements local name, namespace, and namespace prefix.
     *
     * @internal
     *
     * @param Document|null $aDocument The element's owner document.
     *
     * @param string $aLocalName The element's local name that you are creating.
     *
     * @param string $aNamespace The namespace that the element belongs to.
     *
     * @param string|null $aPrefix Optional. The namespace prefix of the
     *     element.
     *
     * @return Element
     */
    public static function create(
        $aDocument,
        $aLocalName,
        $aNamespace,
        $aPrefix = null
    ) {
        $element = new static();
        $element->mLocalName = $aLocalName;
        $element->mNamespaceURI = $aNamespace;
        $element->nodeDocument = $aDocument;
        $element->mPrefix = $aPrefix;

        return $element;
    }

    /**
     * @see AttributeChangeObserver
     */
    public function onAttributeChanged(
        Element $aElement,
        $aLocalName,
        $aOldValue,
        $aValue,
        $aNamespace
    ) {
        // Do nothing.
    }

    /**
     * Retrieves the value of the attribute with the given name, if any.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getattribute
     *
     * @param string $aName The name of the attribute whose value is to be
     *     retrieved.
     *
     * @return string|null
     */
    public function getAttribute($aName)
    {
        $attr = $this->mAttributesList->getAttrByName(Utils::DOMString($aName));

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
        return $this->mAttributesList;
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

        foreach ($this->mAttributesList as $attr) {
            $list[] = $attr->name;
        }

        return $list;
    }

    /**
     * Retrieves the attribute node with the given name, if any.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getattributenode
     *
     * @param string $aName The name of the attribute that is to be retrieved.
     *
     * @return Attr|null
     */
    public function getAttributeNode($aName)
    {
        return $this->mAttributesList->getAttrByName(Utils::DOMString($aName));
    }

    /**
     * Retrieves the attribute node with the given namespace and local name, if
     * any.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getattributenodens
     *
     * @param string $aNamespace The namespaceURI of the attribute node to be
     *     retrieved.
     *
     * @param string $aLocalName The localName of the attribute node to be
     *     retrieved.
     *
     * @return Attr|null
     */
    public function getAttributeNodeNS($aNamespace, $aLocalName)
    {
        return $this->mAttributesList->getAttrByNamespaceAndLocalName(
            Utils::DOMString($aNamespace, false, true),
            Utils::DOMString($aLocalName)
        );
    }

    /**
     * Retrieves the value of the attribute with the given namespace and local name, if any.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getattributens
     *
     * @param  string       $aNamespace The namespaceURI of the attribute whose value is to be retrieved.
     * @param  string       $aLocalName The localName of the attribute whose value is to be retrieved.
     * @return string|null
     */
    public function getAttributeNS($aNamespace, $aLocalName)
    {
        return $this->mAttributesList->getAttrValue(
            Utils::DOMString($aLocalName),
            Utils::DOMString($aNamespace, false, true)
        );
    }

    /**
     * Returns true if the attribtue with the given name is present in the
     * Element's attribute list, otherwise false.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-hasattribute
     *
     * @param string $aQualifiedName The name of the attribute to find.
     *
     * @return bool
     */
    public function hasAttribute($aQualifiedName)
    {
        $qualifiedName = Utils::DOMString($aQualifiedName);

        if ($this->mNamespaceURI === Namespaces::HTML &&
            $this->nodeDocument instanceof HTMLDocument
        ) {
            $qualifiedName = Utils::toASCIILowercase($aQualifiedName);
        }

        return (bool) $this->mAttributesList->getAttrByName($aQualifiedName);
    }

    /**
     * Returns true if the attribute with the given namespace and localName
     * is present in the Element's attribute list, otherwise false.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-hasattributens
     *
     * @param string $aNamespace The namespace of the attribute to find.
     *
     * @param string $aLocalName The localName of the attribute to find.
     *
     * @return bool
     */
    public function hasAttributeNS($aNamespace, $aLocalName)
    {
        return (bool) $this->mAttributesList->getAttrByNamespaceAndLocalName(
            Utils::DOMString($aNamespace, false, true),
            Utils::DOMString($aLocalName)
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
        return !$this->mAttributesList->isEmpty();
    }

    /**
     * Inserts an element adjacent to the current element.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-insertadjacentelement
     *
     * @param string $aWhere The position relative to this node. Possible
     *     values are:
     *         - beforebegin - Inserts an element as this element's previous
     *             sibling.
     *         - afterend - Inserts an element as this element's next sibling.
     *         - afterbegin - Inserts an element as this element's first child.
     *         - beforeend - Inserts an element as this element's last child.
     *
     * @param Element $aElement The element to be inserted.
     *
     * @return null
     */
    public function insertAdjacentElement($aWhere, Element $aElement)
    {
        try {
            return self::insertAdjacent(
                $this,
                Utils::DOMString($aWhere),
                $aElement
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
     * @param string $aPosition One of the types listed above.
     *
     * @param string $aText The markup text to parse and subsequently, insert
     *     relative to this element.
     *
     * @throws SyntaxError If the arguments contain invalid values, such as an
     *     XML string that is not well-formed, when in an XML Document.
     *
     * @throws NoModificationError If the given position for insertion isn't
     *     possible, such as trying to insert elements after the document.
     */
    public function insertAdjacentHTML($aPosition, $aText)
    {
        $position = Utils::DOMString($aPosition);

        if (strcasecmp($position, 'beforebegin') === 0 ||
            strcasecmp($position, 'afterend') === 0
        ) {
            // Let context be the context object's parent.
            $context = $this->mParentNode;

            // If context is null or a Document, throw a
            // "NoModificationAllowedError" DOMException.
            if (!$context || $context instanceof Document) {
                throw new NoModificationAllowedError();
            }
        } elseif (strcasecmp($position, 'afterbegin') === 0 ||
            strcasecmp($position, 'beforeend') === 0
        ) {
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
        if (!($context instanceof Element) ||
            ($context->mOwnerDocument instanceof HTMLDocument &&
                $context->mLocalName === 'html' &&
                $context->mNamespaceURI === Namespaces::HTML)
        ) {
            $context = self::create(
                $this->mOwnerDocument,
                'body',
                Namespaces::HTML
            );
        }

        // Let fragment be the result of invoking the fragment parsing algorithm
        // with text as markup, and context as the context element.
        $fragment = ParserFactory::parseFragment(
            Utils::DOMString($aText),
            $context
        );

        if (strcasecmp($position, 'beforebegin') === 0) {
            // Insert fragment into the context object's parent before the
            // context object.
            $this->mParentNode->insertNode($fragment, $this);
        } elseif (strcasecmp($position, 'afterbegin') === 0) {
            // Insert fragment into the context object before its first child.
            $this->insertNode($fragment, $this->mFirstChild);
        } elseif (strcasecmp($position, 'beforeend') === 0) {
            // Append fragment to the context object.
            $this->appendChild($fragment);
        } elseif (strcasecmp($position, 'afterend') === 0) {
            // Insert fragment into the context object's parent before the
            // context object's next sibling.
            $this->mParentNode->insertNode($fragment, $this->mNextSibling);
        }
    }

    /**
     * Inserts plain text adjacent to the current element.
     *
     * @see https://dom.spec.whatwg.org/#dom-element-insertadjacenttext
     *
     * @param string $aWhere The position relative to this node. Possible
     *     values are:
     *         - beforebegin - Inserts an element as this element's previous
     *             sibling.
     *         - afterend - Inserts an element as this element's next sibling.
     *         - afterbegin - Inserts an element as this element's first child.
     *         - beforeend - Inserts an element as this element's last child.
     *
     * @param string $aData The text to be inserted.
     */
    public function insertAdjacentText($aWhere, $aData)
    {
        $text = new Text($aData);
        $text->nodeDocument = $this->nodeDocument;

        try {
            self::insertAdjacent(
                $this,
                Utils::DOMString($aWhere),
                $text
            );
        } catch (DOMException $e) {
            throw $e;
        }
    }

    public function matches($aSelectorRule)
    {
        // TODO
    }

    /**
     * Removes an attribute with the specified name from the Element's attribute
     * list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-removeattribute
     *
     * @param string $aName The attributes name.
     */
    public function removeAttribute($aName)
    {
        $this->mAttributesList->removeAttrByName($aName);
    }

    /**
     * Removes the given attribute from the Element's attribute list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-removeattributenode
     *
     * @param Attr $aAttr The attribute to be removed.
     *
     * @return Attr The Attr node that was removed.
     *
     * @throws NotFoundError
     */
    public function removeAttributeNode(Attr $aAttr)
    {
        if (!$this->mAttributesList->contains($aAttr)) {
            throw new NotFoundError();
        }

        $this->mAttributesList->remove($aAttr);

        return $aAttr;
    }

    /**
     * Removes the attribute with the given namespace and local name from the
     * Element's attribute list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-hasattributens
     *
     * @param string $aNamespace The namespaceURI of the attribute to be
     *     removed.
     *
     * @param string $aLocalName The localName of the attribute to be removed.
     */
    public function removeAttributeNS($aNamespace, $aLocalName)
    {
        $this->mAttributesList->removeAttrByNamespaceAndLocalName(
            $aNamespace,
            $aLocalName
        );
    }

    /**
     * Either adds a new attribute to the Element's attribute list or it
     * modifies the value of an already existing attribute with the the same
     * name.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-setattribute
     *
     * @param string $aQualifiedName  The name of the attribute.
     *
     * @param string $aValue The value of the attribute.
     */
    public function setAttribute($aQualifiedName, $aValue)
    {
        $qualifiedName = Utils::DOMString($aQualifiedName);

        // If qualifiedName does not match the Name production in XML,
        // throw an InvalidCharacterError exception.
        if (!preg_match(Namespaces::NAME_PRODUCTION, $qualifiedName)) {
            throw new InvalidCharacterError();
        }

        if ($this->mNamespaceURI === Namespaces::HTML &&
            $this->nodeDocument instanceof HTMLDocument
        ) {
            $qualifiedName = Utils::toASCIILowercase($qualifiedName);
        }

        $attribute = null;

        foreach ($this->mAttributesList as $attr) {
            if ($attr->name === $qualifiedName) {
                $attribute = $attr;
                break;
            }
        }

        if (!$attribute) {
            $attribute = new Attr($qualifiedName, Utils::DOMString($aValue));
            $attribute->setNodeDocument($this->nodeDocument);
            $this->mAttributesList->append($attribute);
            return;
        }

        $this->mAttributesList->change(
            $attr,
            Utils::DOMString($aValue)
        );
    }

    /**
     * Appends the given attribute to the Element's attribute list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-setattributenode
     *
     * @param Attr $aAttr The attribute to be appended.
     */
    public function setAttributeNode(Attr $aAttr)
    {
        try {
            return $this->mAttributesList->setAttr($aAttr);
        } catch (DOMException $e) {
            throw $e;
        }
    }

    /**
     * Appends the given namespaced attribute to the Element's attribute list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-setattributenodens
     *
     * @param Attr $aAttr The namespaced attribute to be appended.
     */
    public function setAttributeNodeNS(Attr $aAttr)
    {
        try {
            return $this->mAttributesList->setAttr($aAttr);
        } catch (DOMException $e) {
            throw $e;
        }
    }

    /**
     * Either appends a new attribute or modifies the value of an existing
     * attribute with the given namespace and name.
     *
     * @param string $aNamespace The namespaceURI of the attribute.
     *
     * @param string $aName The name of the attribute.
     *
     * @param string $aValue The value of the attribute.
     */
    public function setAttributeNS($aNamespace, $aName, $aValue)
    {
        try {
            list(
                $namespace,
                $prefix,
                $localName
            ) = Namespaces::validateAndExtract(
                Utils::DOMString($aNamespace, false, true),
                Utils::DOMString($aName)
            );
        } catch (DOMException $e) {
            throw $e;
        }

        $this->mAttributesList->setAttrValue(
            $localName,
            $aValue,
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
        return count($this->mChildNodes);
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
        $qualifiedName = $this->mPrefix === null ? $this->mLocalName :
            $this->mPrefix . ':' . $this->mLocalName;

        return $this->mNamespaceURI === Namespaces::HTML &&
            $this->nodeDocument instanceof HTMLDocument ?
            Utils::toASCIIUppercase($qualifiedName) : $qualifiedName;
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
        Element $aElement,
        $aWhere,
        Node $aNode
    ) {
        switch (strtolower($aWhere)) {
            case 'beforebegin':
                if (!$aElement->mParentNode) {
                    return null;
                }

                try {
                    $aElement->mParentNode->preinsertNode(
                        $aNode,
                        $aElement
                    );
                } catch (DOMException $e) {
                    throw $e;
                }

                break;

            case 'afterbegin':
                $aElement->preinsertNode(
                    $aNode,
                    $aElement->mChildNodes->first()
                );

                break;

            case 'beforeend':
                $aElement->preinsertNode($aNode, null);

                break;

            case 'afterend':
                if (!$aElement->mParentNode) {
                    return null;
                }

                try {
                    $aElement->mParentNode->preinsertNode(
                        $aNode,
                        $aElement->nextSibling
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
     * @param string $aUrl A URL string to be resolved.
     *
     * @param string $aDocumentOrEnvironmentSetting Either a document or
     *     environment settings object that contain a base URL.
     *
     * @return mixed[]|bool An array containing the serialized absolute URL as
     *     well as the parsed URL or false on failure.
     */
    protected function parseURL($aUrl, $aDocumentOrEnvironmentSetting)
    {
        if ($aDocumentOrEnvironmentSetting instanceof Document) {
            $encoding = $aDocumentOrEnvironmentSetting->characterSet;
            $baseURL = $aDocumentOrEnvironmentSetting->getBaseURL();
        } else {
            // TODO: Let encoding be the environment settings object'a API URL
            // character encoding.  Let baseURL be the environment settings
            // object's API base URL.
        }

        $urlRecord = URLParser::parseUrl(
            $aUrl,
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
        $aName,
        $aMissingValueDefault = null
    ) {
        $attr = $this->mAttributesList->getAttrByNamespaceAndLocalName(
            null,
            $aName
        );

        if ($attr) {
            $url = $this->parseURL(
                $attr->value,
                $this->nodeDocument
            );

            if ($url !== false) {
                return $url['urlString'];
            }
        } elseif ($aMissingValueDefault !== null) {
            return $aMissingValueDefault;
        }

        return '';
    }

    /**
     * Gets the value of an attribute that is to be reflected as an object
     * property.
     *
     * @link https://dom.spec.whatwg.org/#concept-reflect
     *
     * @param string $aName The name of the attribute that is to be reflected.
     *
     * @return string
     */
    protected function reflectStringAttributeValue($aName)
    {
        return $this->mAttributesList->getAttrValue($aName);
    }

    /**
     * @see Node::setNodeValue
     */
    protected function setNodeValue($aNewValue)
    {
        // Do nothing.
    }

    /**
     * @see Node::setTextContent
     */
    protected function setTextContent($aNewValue)
    {
        $node = null;
        $newValue = Utils::DOMString($aNewValue, true);

        if ($newValue !== '') {
            $node = new Text($newValue);
            $node->nodeDocument = $this->nodeDocument;
        }

        $this->_replaceAll($node);
    }

    /**
     * Returns an array of Elements with the specified tagName that are
     * immediate children of the parent.
     *
     * @param string $aTagName The tagName to search for.
     *
     * @return Element[] A list of Elements with the specified tagName.
     */
    protected function shallowGetElementsByTagName($aTagName)
    {
        $collection = array();
        $node = $this->mChildNodes->first();
        $tagName = strtoupper($aTagName);

        while ($node) {
            if (strcmp($node->tagName, $tagName) == 0) {
                $collection[] = $node;
            }

            $node = $node->nextElementSibling;
        }

        return $collection;
    }
}
