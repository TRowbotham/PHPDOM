<?php
// https://developer.mozilla.org/en-US/docs/Web/API/Element
// https://dom.spec.whatwg.org/#element

require_once 'Node.class.php';
require_once 'DOMTokenList.class.php';
require_once 'NamedNodeMap.class.php';
require_once 'ParentNode.class.php';
require_once 'ChildNode.class.php';
require_once 'NonDocumentTypeChildNode.class.php';

abstract class Element extends Node implements SplObserver {
    use ParentNode, ChildNode, NonDocumentTypeChildNode;

    protected $mAttributes; // NamedNodeMap
    protected $mAttributesList;
    protected $mClassList; // ClassList
    protected $mClassName;
    protected $mEndTagOmitted;
    protected $mId;
    protected $mLocalName;
    protected $mNamespaceURI;
    protected $mPrefix;
    protected $mTagName;

    private $mReconstructClassList;

    protected function __construct($aLocalName) {
        parent::__construct();

        $this->mAttributes = new NamedNodeMap($this, $this->mAttributesList);
        $this->mAttributesList = array();
        $this->mClassList = null;
        $this->mClassName = '';
        $this->mEndTagOmitted = false;
        $this->mId = '';
        $this->mLocalName = strtolower($aLocalName);
        $this->mNamespaceURI = null;
        $this->mNodeName = strtoupper($aLocalName);
        $this->mPrefix = null;
        $this->mTagName = (!$this->mPrefix ? '' : $this->mPrefix . ':') .
                          ($this->mOwnerDocument instanceof HTMLDocument ?strtoupper($aLocalName) : $aLocalName);
        $this->addEventListener('attributechange', array($this, '_onAttributeChange'));
    }

    public function __get( $aName ) {
        switch ($aName) {
            case 'attributes':
                return $this->mAttributes;

            case 'childElementCount':
                return $this->getChildElementCount();

            case 'children':
                return $this->getChildren();

            case 'classList':
                if (!isset($this->mClassList) || $this->mReconstructClassList) {
                    $this->mClassList = new DOMTokenList($this, 'class');

                    if (!empty($this->mClassName)) {
                        call_user_func_array(array($this->mClassList, 'add'), DOMTokenList::_parseOrderedSet($this->mClassName));
                    }
                }

                return $this->mClassList;

            case 'className':
                return $this->mClassName;

            case 'firstElementChild':
                return $this->getFirstElementChild();

            case 'id':
                return $this->mId;

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
                return $this->mTagName;

            default:
                return parent::__get($aName);
        }
    }

    public function __set( $aName, $aValue ) {
        switch ($aName) {
            case 'className':
                $this->mClassName = $aValue;
                $this->mReconstructClassList = true;
                $this->_updateAttributeOnPropertyChange('class', $aValue);

                break;

            case 'id':
                $this->mId = $aValue;
                $this->_updateAttributeOnPropertyChange('id', $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    public function closest($aSelectorRule) {
        // TODO
    }

    /**
     * Retrieves the value of the attribute with the given name, if any.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getattribute
     *
     * @param  string       $aName The name of the attribute whose value is to be retrieved.
     *
     * @return string|null
     */
    public function getAttribute($aName) {
        $attr = $this->_getAttributeByName($aName);

        return $attr ? $attr->value : null;
    }

    /**
     * Retrieves the attribute node with the given name, if any.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getattributenode
     *
     * @param  string       $aName The name of the attribute that is to be retrieved.
     *
     * @return Attr|null
     */
    public function getAttributeNode($aName) {
        return $this->_getAttributeByName($aName);
    }

    /**
     * Retrieves the attribute node with the given namespace and local name, if any.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getattributenodens
     *
     * @param  string       $aNamespace The namespaceURI of the attribute node to be retrieved.
     *
     * @param  string       $aLocalName The localName of the attribute node to be retrieved.
     *
     * @return Attr|null
     */
    public function getAttributeNodeNS($aNamespace, $aLocalName) {
        return $this->_getAttributeByNamespaceAndLocalName($aNamespace, $aLocalName);
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
    public function getAttributeNS($aNamespace, $aLocalName) {
        $attr = $this->_getAttributeByNamespaceAndLocalName($aNamespace, $aLocalName);

        return $attr ? $attr->value : null;
    }

    /**
     * Returns a list of all the Element's that have all the given class names.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getelementsbyclassname
     *
     * @param  string       $aClassName A space delimited string containing the classNames to search for.
     *
     * @return Element[]
     */
    public function getElementsByClassName($aClassName) {
        return static::_getElementsByClassName($this, $aClassName);
    }

    /**
     * Returns an array of Elements with the specified local name.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getelementsbytagname
     *
     * @param  string       $aLocalName The element's local name to search for.  If given '*',
     *                                  all element decendants will be returned.
     *
     * @return Element[]                A list of Elements with the specified local name.
     */

    public function getElementsByTagName($aLocalName) {
        return static::_getElementsByTagName($this, $aLocalName);
    }

    /**
     * Returns a collection of Elements that match the given namespace and local name.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getelementsbytagnamens
     *
     * @param  string       $aNamespace The namespaceURI to search for.  If both namespace and local
     *                                  name are given '*', all element decendants will be returned.  If only
     *                                  namespace is given '*' all element decendants matching only local
     *                                  name will be returned.
     *
     * @param  string       $aLocalName The Element's local name to search for.  If both namespace and local
     *                                  name are given '*', all element decendants will be returned.  If only
     *                                  local name is given '*' all element decendants matching only namespace
     *                                  will be returned.
     *
     * @return Element[]
     */
    public function getElementsByTagNameNS($aNamespace, $aLocalName) {
        return static::_getElementsByTagNameNS($this, $aNamespace, $aLocalName);
    }

    /**
     * Returns true if the attribtue with the given name is present in the Element's
     * attribute list, otherwise false.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-hasattribute
     *
     * @param  string  $aName The name of the attribute to find.
     *
     * @return bool
     */
    public function hasAttribute($aName) {
        $name = $aName;

        if ($this->mNamespaceURI === Namespaces::HTML &&
            $this->mOwnerDocument instanceof HTMLDocument) {
            $name = strtolower($aName);
        }

        foreach ($this->mAttributesList as $attr) {
            if ($attr->name == $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the attribute with the given namespace and localName
     * is present in the Element's attribute list, otherwise false.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-hasattributens
     *
     * @param  string  $aNamespace The namespace of the attribute to find.
     *
     * @param  string  $aLocalName The localName of the attribute to find.
     *
     * @return bool
     */
    public function hasAttribueNS($aNamespace, $aLocalName) {
        $namespace = $aNamespace === '' ? null : $aNamespace;

        foreach ($this->mAttributesList as $attr) {
            if ($attr->namespaceURI == $namespace &&
                $attr->localName == $aLocalName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if there are attributes present in the Element's attribute
     * list, otherwise false.
     *
     * @return bool
     */
    public function hasAttributes() {
        return !empty($this->mAttributesList);
    }

    public function insertAdjacentHTML($aHTML) {
        // TODO
    }

    public function matches( $aSelectorRule ) {
        // TODO
    }

    /**
     * Removes an attribute with the specified name from the Element's attribute
     * list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-removeattribute
     *
     * @param  string $aName The attributes name.
     */
    public function removeAttribute($aName) {
        $this->_removeAttributeByName($aName);
    }

    /**
     * Removes the given attribute from the Element's attribute list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-removeattributenode
     *
     * @param  Attr   $aAttr The attribute to be removed.
     *
     * @return Attr          The Attr node that was removed.
     *
     * @throws NotFoundError
     */
    public function removeAttributeNode(Attr $aAttr) {
        $index = array_search($aAttr, $this->mAttributesList);

        if ($index === false) {
            throw new NotFoundError;
        }

        $this->_removeAttribute($aAttr);

        return $aAttr;
    }

    /**
     * Removes the attribute with the given namespace and local name from the Element's
     * attribute list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-hasattributens
     *
     * @param  string $aNamespace The namespaceURI of the attribute to be removed.
     *
     * @param  string $aLocalName The localName of the attribute to be removed.
     */
    public function removeAttributeNS($aNamespace, $aLocalName) {
        $this->_removeAttributeByNamespaceAndLocalName($aNamespace, $aLocalName);
    }

    /**
     * Either adds a new attribute to the Element's attribute list or it modifies
     * the value of an already existing attribute with the the same name.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-setattribute
     *
     * @param string $aName  The name of the attribute.
     *
     * @param string $aValue The value of the attribute.
     */
    public function setAttribute($aName, $aValue) {
        $name = $aName;

        // TODO: Check Name production in XML documents

        if ($this->mNamespaceURI === Namespaces::HTML &&
            $this->mOwnerDocument instanceof HTMLDocument) {
            $name = strtolower($aName);
        }

        $attr = $this->_getAttributeByName($name);

        if (!$attr) {
            $attr = new Attr($this, $name, $aValue);
            $this->_appendAttribute($attr);
            return;
        }

        $this->_changeAttributeValue($attr, $aValue);
    }

    /**
     * Appends the given attribute to the Element's attribute list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-setattributenode
     *
     * @param Attr $aAttr The attribute to be appended.
     */
    public function setAttributeNode(Attr $aAttr) {
        try {
            return $this->_setAttribute($aAttr);
        } catch (Exception $e) {
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
    public function setAttributeNodeNS(Attr $aAttr) {
        try {
            return $this->_setAttribute($aAttr, $aAttr->namespaceURI, $aAttr->localName);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Either appends a new attribute or modifies the value of an existing attribute
     * with the given namespace and name.
     *
     * @param string $aNamespace The namespaceURI of the attribute.
     *
     * @param string $aName      The name of the attribute.
     *
     * @param string $aValue     The value of the attribute.
     */
    public function setAttributeNS($aNamespace, $aName, $aValue) {
        // TODO
    }

    public function toHTML() {
        $html = '';

        switch ($this->mNodeType) {
            case Node::ELEMENT_NODE:
                $tagName = strtolower($this->mNodeName);
                $html = '<' . $tagName;

                foreach($this->mAttributesList as $attribute) {
                    $html .= ' ' . $attribute->name;

                    if (!Attr::_isBool($attribute->name)) {
                        $html .= '="' . $attribute->value . '"';
                    }
                }

                $html .= '>';

                foreach($this->mChildNodes as $child) {
                    $html .= $child->toHTML();
                }

                if (!$this->mEndTagOmitted) {
                    $html .= '</' . $tagName . '>';
                }

                break;

            case Node::TEXT_NODE:
                $html = $this->textContent;

                break;

            case Node::PROCESSING_INSTRUCTION_NODE:
                // TODO
                break;

            case Node::COMMENT_NODE:
                $html = '<!-- ' . $this->textContent . ' -->';

                break;

            case Node::DOCUMENT_TYPE_NODE:
                // TODO
                break;

            case Node::DOCUMENT_NODE:
            case Node::DOCUMENT_FRAGMENT_NODE:
                foreach ($this->mChildNodes as $child) {
                    $html .= $child->toHTML();
                }

                break;

            default:
                # code...
                break;
        }

        return $html;
    }

    public function update(SplSubject $aObject) {

    }

    /**
     * Appends an Attr node to the Element's attribute list.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-append
     *
     * @param  Attr   $aAttr The Attr node to be appended.
     */
    public function _appendAttribute(Attr $aAttr) {
        // TODO: Queue a mutation record for "attributes"
        $dict = new CustomEventInit();
        $dict->detail = array('attr' => $aAttr, 'action' => 'set');
        $event = new CustomEvent('attributechange', $dict);
        $this->dispatchEvent($event);

        $this->mAttributesList[] = $aAttr;
    }

    /**
     * Changes the value of an attribute.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-change
     *
     * @param  Attr   $aAttr  The Attr whose value is to be changed.
     * @param  string $aValue The new value of the given Attr.
     */
    public function _changeAttributeValue(Attr $aAttr, $aValue) {
        // TODO: Queue a mutation record for "attributes"
        $dict = new CustomEventInit();
        $dict->detail = array('attr' => $aAttr, 'action' => 'set');
        $event = new CustomEvent('attributechange', $dict);
        $this->dispatchEvent($event);

        $aAttr->value = $aValue;
    }

    /**
     * Returns the first Attr in the Element's attribute list that has the given
     * name.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-get-by-name
     *
     * @param  string       $aName The name of the attribute to find.
     *
     * @return Attr|null
     */
    public function _getAttributeByName($aName) {
        $name = $aName;

        if ($this->mNamespaceURI === Namespaces::HTML &&
            $this->mOwnerDocument instanceof HTMLDocument) {
            $name = strtolower($aName);
        }

        foreach ($this->mAttributesList as $attr) {
            if (strcmp($attr->name, $name) === 0) {
                return $attr;
            }
        }

        return null;
    }

    /**
     * Returns the first Attr in the Element's attribute list that has the given namespace
     * and local name.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-get-by-namespace
     *
     * @param  string       $aNamespace The namespaceURI of the attribute to find.
     *
     * @param  string       $aLocalName The localName of the attribute to find.
     *
     * @return Attr|null
     */
    public function _getAttributeByNamespaceAndLocalName($aNamespace, $aLocalName) {
        $namespace = $aNamespace === '' ? null : $aNamespace;

        foreach ($this->mAttributesList as $attr) {
            if (strcmp($attr->namespaceURI, $namespace) === 0 &&
                strcmp($attr->localName, $aLocalName) === 0) {
                return $attr;
            }
        }

        return null;
    }

    /**
     * Returns a list of all the Element's that have all the given class names.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-getElementsByClassName
     *
     * @param  Node         $aRoot      A node at which the search is rooted at.
     *
     * @param  string       $aClassName A space delimited string containing the classNames to search for.
     *
     * @return Element[]
     */
    public static function _getElementsByClassName(Node $aRoot, $aClassName) {
        $classes = DOMTokenList::_parseOrderedSet($aClassName);

        if (empty($classes)) {
            return $classes;
        }

        $ownerDocument = $aRoot instanceof Document ? $aRoot : $aRoot->ownerDocument;
        $nodeFilter = function ($aNode) use ($classes) {
            $hasClasses = false;

            foreach ($classes as $className) {
                if (!($hasClasses = $aNode->classList->contains($className))) {
                    break;
                }
            }

            return $hasClasses ? NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
        };
        $tw = $ownerDocument->createTreeWalker($aRoot, NodeFilter::SHOW_ELEMENT, $nodeFilter);

        while ($node = $tw->nextNode()) {
            $collection[] = $node;
        }

        return $collection;
    }

    /**
     * Returns a collection of Elements that match the given local name.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-getElementsByTagName
     *
     * @param  Node         $aRoot      A node at which the search is rooted at.  If given '*',
     *                                  all element decendants will be returned.
     *
     * @param  string       $aLocalName The Element's local name to search for.
     *
     * @return Element[]
     */
    public static function _getElementsByTagName(Node $aRoot, $aLocalName) {
        $rootIsDocument = $aRoot instanceof Document;
        $ownerDocument =  $rootIsDocument ? $aRoot : $aRoot->ownerDocument;
        $collection = array();

        if (strcmp($aLocalName, '*') === 0) {
            $nodeFilter = null;
        } else if ($rootIsDocument) {
            $nodeFilter = function ($aNode) use ($aLocalName) {
                if (($aNode->namespaceURI === Namespaces::HTML &&
                    strcmp($aNode->localName, strtolower($aLocalName)) === 0) ||
                    ($aNode->namespaceURI === Namespaces::HTML &&
                    strcmp($aNode->localName, $aLocalName) === 0)) {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_SKIP;
            };
        } else {
            $nodeFilter = function ($aNode) use ($aLocalName) {
                return strcmp($aNode->localName, $aLocalName) === 0 ? NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
            };
        }

        $tw = $ownerDocument->createTreeWalker($aRoot, NodeFilter::SHOW_ELEMENT, $nodeFilter);

        while ($node = $tw->nextNode()) {
            $collection[] = $node;
        }

        return $collection;
    }

    /**
     * Returns a collection of Elements that match the given namespace and local name.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-getElementsByTagNameNS
     *
     * @param  Node         $aRoot      A node at which the search is rooted at.
     *
     * @param  string       $aNamespace The namespaceURI to search for.  If both namespace and local
     *                                  name are given '*', all element decendants will be returned.  If only
     *                                  namespace is given '*' all element decendants matching only local
     *                                  name will be returned.
     *
     * @param  string       $aLocalName The Element's local name to search for.  If both namespace and local
     *                                  name are given '*', all element decendants will be returned.  If only
     *                                  local name is given '*' all element decendants matching only namespace
     *                                  will be returned.
     *
     * @return Element[]
     */
    public static function _getElementsByTagNameNS(Node $aRoot, $aNamespace, $aLocalName) {
        $namespace = strcmp($aNamespace, '') === 0 ? null : $aNamespace;
        $collection = array();

        if (strcmp($namespace, '*') === 0 && strcmp($aLocalName, '*') === 0) {
            $nodeFilter = null;
        } else if (strcmp($namespace, '*') === 0) {
            $nodeFilter = function ($aNode) use ($aLocalName) {
                return strcmp($aNode->localName, $aLocalName) === 0 ? NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
            };
        } else if (strcmp($aLocalName, '*') === 0) {
            $nodeFilter =  function ($aNode) use ($namespace) {
                return strcmp($aNode->namespaceURI, $namespace) === 0 ? NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
            };
        }

        $ownerDocument = $aRoot instanceof Document ? $aRoot : $aRoot->ownerDocument;
        $tw = $ownerDocument->createTreeWalker($aRoot, NodeFilter::SHOW_ELEMENT, $nodeFilter);

        while ($node = $tw->nextNode()) {
            $collection[] = $node;
        }

        return $collection;
    }

    public function _isEndTagOmitted() {
        return $this->mEndTagOmitted;
    }

    /**
     * Removes the given Attr from the Element's attribute list.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-remove
     *
     * @param  Attr $aAttr The Attr to be removed.
     */
    public function _removeAttribute(Attr $aAttr) {
        // TODO: Queue a mutation record for "attributes"
        $dict = new CustomEventInit();
        $dict->detail = array('attr' => $aAttr, 'action' => 'remove');
        $e = new CustomEvent('attributechange', $dict);
        $this->dispatchEvent($e);

        $index = array_search($aAttr, $this->mAttributesList);

        if ($index !== false) {
            array_splice($this->mAttributesList, $index, 1);
            // TODO: Set attribute's ownerElement to null
        }
    }

    /**
     * Removes the attribute with the given name from the Element's attribute list.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-remove-by-name
     *
     * @param  string       $aName The name of the attribute to be removed.
     *
     * @return Attr|null
     */
    public function _removeAttributeByName($aName) {
        $attr = $this->_getAttributeByName($aName);

        if ($attr) {
            $this->_removeAttribute($attr);
        }

        return $attr;
    }

    /**
     * Removes the attribtue with the given namespace and local name from the Element's attribute
     * list.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-remove-by-namespace
     *
     * @param  string       $aNamespace The namespaceURI of the attribute to be removed.
     *
     * @param  string       $aLocalName The localName of the attribute to be removed.
     *
     * @return Attr|null
     */
    public function _removeAttributeByNamespaceAndLocalName($aNamespace, $aLocalName) {
        $attr = $this->_getAttributeByNamespaceAndLocalName($aNamespace, $aLocalName);

        if ($attr) {
            $this->_removeAttribute($attr);
        }

        return $attr;
    }

    /**
     * Associates an Attr with this Element.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-set
     *
     * @param Attr   $aAttr      The Attr to be appended to this Element's attribute list.
     *
     * @param string $aNamespace Optional.  Whether or not the attribute being appended is namespaced.
     *
     * @param string $aLocalName Optional.  Whether or not the attribute being appended is namespaced.
     */
    public function _setAttribute(Attr $aAttr, $aNamespace = null, $aLocalName = null) {
        if ($aAttr->ownerElement !== null && !($aAttr->ownerElement instanceof Element)) {
            throw new InUseAttributeError;
        }

        $oldAttr = null;

        if ($aNamespace && $aLocalName) {
            $oldAttr = $this->_getAttributeByNamespaceAndLocalName($attr->namespaceURI, $attr->localName);
        } else {
            $oldAttr = $this->_getAttributeByName($aAttr->name);
        }

        if ($oldAttr === $aAttr) {
            return $aAttr;
        }

        if ($oldAttr) {
            $this->_removeAttribute($oldAttr);
        }

        $this->_appendAttribute($aAttr);

        return $oldAttr;
    }

    /**
     * Sets an attributes value.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-set-value
     *
     * @param string $aLocalName The localName of the attribute to find.
     *
     * @param string $aValue     The value of the attribute.
     *
     * @param string $aName      The name of the attribute.
     *
     * @param string $aPrefix    The namespace prefix of the attribute.
     *
     * @param string $aNamespace The namespaceURI of the attribute to find.
     */
    public function _setAttributeValue($aLocalName, $aValue, $aName = null, $aPrefix = null, $aNamespace = null) {
        $name = !$aName ? $aLocalName : $aName;
        $prefix = !$aPrefix ? null : $aPrefix;
        $namespace = !$aNamespace ? null : $aNamespace;

        $attr = $this->_getAttributeByNamespaceAndLocalName($namespace, $aLocalName);

        if (!$attr) {
            $attr = new Attr($this, $aLocalName, $aValue, $namespace, $prefix);
            $this->_appendAttribute($attr);
            return;
        }

        $this->_changeAttributeValue($attr, $aValue);
    }

    protected function _onAttributeChange(Event $aEvent) {
        switch ($aEvent->detail['attr']->name) {
            case 'class':
                $this->mReconstructClassList = true;
                $this->mClassName = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';

                break;

            case 'id':
                $this->mId = $aEvent->detail['action'] == 'set' ? $aEvent->detail['attr']->value : '';
        }
    }

    protected function _updateAttributeOnPropertyChange($aAttributeName, $aValue) {
        $attrName = strtolower($aAttributeName);

        if (empty($aValue) || $aValue === '') {
            $this->removeAttribute($attrName);
        } else {
            $this->setAttribute($attrName, $aValue);
        }
    }

    /**
     * Returns an array of Elements with the specified tagName that are immediate children
     * of the parent.
     * @param  string       $aTagName   The tagName to search for.
     * @return Element[]                A list of Elements with the specified tagName.
     */
    protected function shallowGetElementsByTagName($aTagName) {
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
