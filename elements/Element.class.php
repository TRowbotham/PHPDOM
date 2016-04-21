<?php
namespace phpjs\elements;

use phpjs\Attr;
use phpjs\AttributeList;
use phpjs\ChildNode;
use phpjs\DOMTokenList;
use phpjs\exceptions\InUseAttributeError;
use phpjs\exceptions\NotFoundError;
use phpjs\GetElementsBy;
use phpjs\HTMLDocument;
use phpjs\NamedNodeMap;
use phpjs\Namespaces;
use phpjs\Node;
use phpjs\NodeFilter;
use phpjs\NonDocumentTypeChildNode;
use phpjs\ParentNode;
use phpjs\Text;
use phpjs\TreeWalker;
use phpjs\urls\URLInternal;
use phpjs\Utils;

/**
 * @see https://dom.spec.whatwg.org/#element
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Element
 */
class Element extends Node implements \SplObserver
{
    use ChildNode;
    use GetElementsBy;
    use NonDocumentTypeChildNode;
    use ParentNode;

    protected $mNamedNodeMap;
    protected $mAttributesList;
    protected $mClassList; // ClassList
    protected $mLocalName;
    protected $mNamespaceURI;
    protected $mPrefix;

    protected function __construct()
    {
        parent::__construct();

        $this->mAttributesList = new AttributeList();
        $this->mClassList = new DOMTokenList($this, 'class');
        $this->mLocalName = '';
        $this->mNamedNodeMap = new NamedNodeMap($this, $this->mAttributesList);
        $this->mNamespaceURI = null;
        $this->mNodeType = self::ELEMENT_NODE;
        $this->mPrefix = null;
    }

    public function __get( $aName ) {
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
                return $this->mAttributesList->getAttrValue($this, 'class');

            case 'firstElementChild':
                return $this->getFirstElementChild();

            case 'id':
                return $this->mAttributesList->getAttrValue($this, $aName);

            case 'innerHTML':
                $rv = '';

                foreach ($this->mChildNodes as $child) {
                    $rv .= $child->toHTML();
                }

                return $rv;

            case 'lastElementChild':
                return $this->getLastElementChild();

            case 'localName':
                return $this->mLocalName;

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
                $this->mClassList->value = $this->mAttributesList->getAttrValue(
                    $this,
                    'class'
                );

                break;

            case 'className':
                $this->mAttributesList->setAttrValue($this, 'class', $aValue);

                break;

            case 'id':
                $this->mAttributesList->setAttrValue($this, $aName, $aValue);

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
     * @param string $aLocalName The element's local name that you are creating.
     *
     * @param string $aNamespace The namespace that the element belongs to.
     *
     * @param string|null $aPrefix Optional. The namespace prefix of the
     *     element.
     *
     * @return Element
     */
    public static function create($aLocalName, $aNamespace, $aPrefix = null)
    {
        $element = new static();
        $element->mLocalName = $aLocalName;
        $element->mNamespaceURI = $aNamespace;
        $element->mPrefix = $aPrefix;

        return $element;
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
        $attr = $this->mAttributesList->getAttrByName($aName, $this);

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
        return $this->mAttributesList->getAttrByName($aName);
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
            $aNamespace,
            $aLocalName,
            $this
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
            $this,
            $aLocalName,
            $aNamespace
        );
    }

    /**
     * Returns true if the attribtue with the given name is present in the
     * Element's attribute list, otherwise false.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-hasattribute
     *
     * @param string $aName The name of the attribute to find.
     *
     * @return bool
     */
    public function hasAttribute($aName)
    {
        return !!$this->mAttributesList->getAttrByName($aName, $this);
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
    public function hasAttribueNS($aNamespace, $aLocalName)
    {
        return !!$this->mAttributesList->getAttrByNamespaceAndLocalName(
            $aNamespace,
            $aLocalName,
            $this
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
        return $this->mAttributesList->count() !== 0;
    }

    public function insertAdjacentHTML($aHTML) {
        // TODO
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
            return self::insertAdjacent($this, $aWhere, $aElement);
        } catch (\Exception $e) {
            throw $e;
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
     *
     * @return null
     */
    public function insertAdjacentText($aWhere, $aData)
    {
        $text = new Text($aData);
        $text->mOwnerDocument = $this->mOwnerDocument;

        try {
            self::insertAdjacent($this, $aWhere, $text);
        } catch (\Exception $e) {
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
        $this->mAttributesList->removeAttrByName($aName, $this);
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
        if (!$this->mAttributesList->hasAttr($aAttr)) {
            throw new NotFoundError();
        }

        $this->mAttributesList->removeAttr($aAttr, $this);

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
            $aLocalName,
            $this
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
        // TODO: If qualifiedName does not match the Name production in XML,
        // throw an InvalidCharacterError exception.

        if (
            $this->mNamespaceURI === Namespaces::HTML &&
            $this->mOwnerDocument instanceof HTMLDocument
        ) {
            $qualifiedName = strtolower($aQualifiedName);
        } else {
            $qualifiedName = $aQualifiedName;
        }

        $attribute = null;

        foreach ($this->mAttributesList as $attr) {
            if ($attr->name === $qualifiedName) {
                $attribute = $attr;
                break;
            }
        }

        if (!$attribute) {
            $attribute = new Attr($qualifiedName, $aValue);
            $this->mAttributesList->appendAttr($attribute, $this);
            return;
        }

        $this->mAttributesList->changeAttr($attr, $this, $aValue);
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
            return $this->mAttributesList->setAttr($aAttr, $this);
        } catch (\Exception $e) {
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
            return $this->mAttributesList->setAttr($aAttr, $this);
        } catch (\Exception $e) {
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
            $parts = Namespaces::validateAndExtract($aNamespace, $aName);
        } catch (\Exception $e) {
            throw $e;
        }

        $this->mAttributesList->setAttrValue(
            $this,
            $parts['localName'],
            $aValue,
            $parts['prefix'],
            $parts['namespace']
        );
    }

    public function update(\SplSubject $aObject)
    {

    }

    public function attributeHookHandler($aHookType, Attr $aAttr)
    {
        switch ($aAttr->name) {
            case 'class':
                if ($aHookType & AttributeList::ATTR_SET) {
                    $value = $aAttr->value;

                    if (!empty($value)) {
                        $this->mClassList->appendTokens(
                            Utils::parseOrderedSet($value)
                        );
                    }
                } elseif ($aHookType & AttributeList::ATTR_REMOVED) {
                    $this->mClassList->emptyList();
                }
        }
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
            $this->mOwnerDocument instanceof HTMLDocument ?
            strtoupper($qualifiedName) : $qualifiedName;
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
                } catch (\Exception $e) {
                    throw $e;
                }

                break;

            case 'afterbegin':
                $aElement->preinsertNode($aNode, $aElement->mFirstChild);

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
                        $aElement->mNextSibling
                    );
                } catch (\Exception $e) {
                    throw $e;
                }

                break;

            default:
                throw new SyntaxError();
        }
    }

    protected function reflectURLAttributeValue(
        $aName,
        $aMissingValueDefault = null
    ) {
        $attr = $this->mAttributesList->getAttrByNamespaceAndLocalName(
            null,
            $aName,
            $this
        );

        if ($attr) {
            $url = $this->resolveURL($attr->value, self::$mBaseURI);

            if ($url !== false) {
                return $url['serialized_url'];
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
        return $this->mAttributesList->getAttrValue($this, $aName);
    }

    /**
     * Resolves a URL to the absolute URL that it implies.
     *
     * @link https://html.spec.whatwg.org/multipage/infrastructure.html#resolve-a-url
     *
     * @internal
     *
     * @param string $aUrl A URL string to be resolved.
     *
     * @param string $aBase Optional argument that should be an absolute URL
     *     that a relative URL can be resolved against.  Default is null.
     *
     * @return mixed[]|bool An array containing the serialized absolute URL as
     *     well as the parsed URL or false on failure.
     */
    protected function resolveURL($aUrl, URLInternal $aBase = null)
    {
        $url = $aUrl;
        $base = null;

        // TODO: Handle encoding
        $encoding = 'utf-8';

        if ($aBase && $aBase->isFlagSet(URLInternal::FLAG_CANNOT_BE_A_BASE_URL)) {
            $base = $aBase;
        } else {
            $base = self::$mBaseURI;
        }

        $parsedURL = URLInternal::URLParser($url, $base, $encoding);

        if ($parsedURL === false) {
            // Abort these steps.  The URL cannot be resolved.
            return false;
        }

        $serializedURL = $parsedURL->serializeURL();

        return array(
            'absolute_url' => $serializedURL,
            'parsed_url' => $parsedURL
        );
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

        if (!empty($aNewValue)) {
            $node = new Text($aNewValue);
            $node->mOwnerDocument = $this->mOwnerDocument;
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
        $node = $this->mFirstChild;
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
