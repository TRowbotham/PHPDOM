<?php
use phpjs\Attr;
use phpjs\elements\Element;
use phpjs\exceptions\InUseAttributeError;
use phpjs\HTMLDocument;
use phpjs\Namespaces;

class AttributeList implements \ArrayAccess, \Countable, \Iterator
{
    protected $mList;
    protected $mPosition;

    public function __construct()
    {
        $this->mList = [];
        $this->mPosition = 0;
    }

    /**
     * Appends an attribute to the list of attributes.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-append
     *
     * @param Attr $aAttr The attribute to be appended.
     *
     * @param Element $aElement The element that owns the list.
     */
    public function appendAttr(Attr $aAttr, Element $aElement)
    {
        // TODO: Queue a mutation record of "attributes" for element with name
        // attribute’s local name, namespace attribute’s namespace, and
        // oldValue null.

        $this->mList[] = $aAttr;
        $aAttr->setOwnerElement($aElement);
        $aElement->attributeHookHandler('set', $aAttr);
        $aElement->attributeHookHandler('added', $aAttr);
    }

    /**
     * Changes the value of an attribute.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-change
     *
     * @param Attr $aAttr The attribute whose value is to be changed.
     *
     * @param Element $aElement The element that owns the list.
     */
    public function changeAttr(Attr $aAttr, Element $aElement)
    {
        // TODO: Queue a mutation record of "attributes" for element with name
        // attribute’s local name, namespace attribute’s namespace, and
        // oldValue attribute’s value.

        $aAttr->setValue($aValue);
        $aElement->attributeHookHandler('set', $aAttr);
        $aElement->attributeHookHandler('changed', $aAttr);
    }

    /**
     * Gets the number of attributes in the list.
     *
     * @return int
     */
    public function count()
    {
        return count($this->mList);
    }

    /**
     * Returns the iterator's current element.
     *
     * @return string|null
     */
    public function current()
    {
        return $this->mList[$this->mPosition];
    }

    /**
     * Gets an attribute using a fully qualified name.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-get-by-name
     *
     * @param string $aQualifiedName The fully qualified name of the attribute
     *     to find.
     *
     * @param Element $aElement The element that owns the list.
     *
     * @return Attr|null
     */
    public function getAttrByName($aQualifiedName, Element $aElement)
    {
        if (
            $aElement->namespaceURI === Namespaces::HTML &&
            $aElement->ownerDocument instanceof HTMLDocument
        ) {
            $qualifiedName = strtolower($aQualifiedName);
        } else {
            $qualifiedName = $aQualifiedName;
        }

        foreach ($this->mList as $attr) {
            if ($attr->name === $qualifiedName) {
                return $attr;
            }
        }

        return null;
    }

    /**
     * Gets an attribute using a namespace and local name.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-get-by-namespace
     *
     * @param string $aNamespace The namespace of the attribute to find.
     *
     * @param string $aLocalName The local name of the attribute to find.
     *
     * @param  Element $aElement The element that owns the list.
     *
     * @return Attr|null
     */
    public function getAttrByNamespaceAndLocalName(
        $aNamespace,
        $aLocalName,
        Element $aElement
    ) {
        if (is_string($aNamespace) && empty($aNamespace)) {
            $namespace = null;
        } else {
            $namespace = $aNamespace;
        }

        foreach ($this->mList as $attr) {
            if (
                $attr->namespaceURI === $namespace &&
                $attr->localName === $aLocalName
            ) {
                return $attr;
            }
        }

        return null;
    }

    /**
     * Gets an attribute's value.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-get-value
     *
     * @param Element $aElement The element that owns the list.
     *
     * @param string $aLocalName The local name of the attribute whose value is
     *     to be returned.
     *
     * @param string $aNamespace The namespace of the attribute whose value is
     *     to be returned.
     *
     * @return string
     */
    public function getAttrValue(
        Element $aElement,
        $aLocalName,
        $aNamespace = null
    ) {
        $attr = $this->getAttrByNamespaceAndLocalName(
            $aNamespace,
            $aLocalName,
            $aElement
        );

        return $attr ? $attr->value : '';
    }

    /**
     * Returns the iterator's current pointer.
     *
     * @return int
     */
    public function key()
    {
        return $this->mPosition;
    }

    /**
     * Advances the iterator's pointer by 1.
     */
    public function next()
    {
        $this->mPosition++;
    }

    /**
     * Used with isset() to check if a specific index exists.
     *
     * @param int $aIndex An integer index.
     *
     * @return bool Returns true if an attribute exits at the specified index,
     *     otherwise, return false.
     */
    public function offsetExists($aIndex)
    {
        return isset($this->mList[$aIndex]);
    }

    /**
     * Returns the attribute at the specified index.
     *
     * @param int $aIndex An integer index.
     *
     * @return string The attribute at the specified index or null if the index
     *     does not exist.
     */
    public function offsetGet($aIndex)
    {
        return isset($this->mList[$aIndex]) ? $this->mList[$aIndex] : null;
    }

    /**
     * Setting an attribute using array notation is not permitted.
     */
    public function offsetSet($aIndex, $aValue)
    {

    }

    /**
     * Unsetting an attribute using array notation is not permitted.
     */
    public function offsetUnset($aIndex)
    {

    }

    /**
     * Removes an attribute from the list.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-remove
     *
     * @param Attr $aAttr The attribute to be removed from the list.
     *
     * @param Element $aElement The element that owns the list.
     */
    public function removeAttr(Attr $aAttr, Element $aElement)
    {
        // TODO: Queue a mutation record of "attributes" for element with name
        // attribute’s local name, namespace attribute’s namespace, and
        // oldValue attribute’s value.

        $index = array_search($aAttr, $this->mList, true);
        array_splice($this->mList, $index, 1);
        $aAttr->setOwnerElement(null);
        $aElement->attributeHookHandler('removed', $aAttr);
    }

    /**
     * Removes an attribute from the list with the specified fully qualified
     * name.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-remove-by-name
     *
     * @param string $aQualifiedName The fully qualified name of the attribute
     *     to be removed.
     *
     * @param Element $aElement The element that owns the list.
     *
     * @return Attr|null
     */
    public function removeAttrByName($aQualifiedName, Element $aElement)
    {
        $attr = $this->getAttrByName($aQualifiedName, $aElement);

        if ($attr) {
            $this->removeAttr($attr, $aElement);
        }

        return $attr;
    }

    /**
     * Remove an attribute from the list using a namespace and local name.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-remove-by-namespace
     *
     * @param string $aNamespace The namespace of the attribute to be removed.
     *
     * @param string $aLocalName The local name of the attribute to be removed.
     *
     * @param Element $aElement The element that owns the list.
     *
     * @return Attr|null
     */
    public function removeAttrByNamespaceAndLocalName(
        $aNamespace,
        $aLocalName,
        Element $aElement
    ) {
        $attr = $this->getAttrByNamespaceAndLocalName(
            $aNamespace,
            $aLocalName,
            $aElement
        );

        if ($attr) {
            $this->removeAttr($attr, $aElement);
        }

        return $attr;
    }

    /**
     * Replaces and attribute with another attribute.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-replace
     *
     * @param Attr $aOldAttr The attribute being removed from the list.
     *
     * @param Attr $aNewAttr The attribute being inserted into the list.
     *
     * @param Element $aElement The element that owns the list.
     */
    public function replaceAttr(
        Attr $aOldAttr,
        Attr $aNewAttr,
        Element $aElement
    ) {
        // TODO: Queue a mutation record of "attributes" for element with name
        // oldAttr’s local name, namespace oldAttr’s namespace, and oldValue
        // oldAttr’s value.

        $index = array_search($aOldAttr, $this->mList);
        array_splice($this->mList, $index, 1, [$aNewAttr]);
        $aOldAttr->setOwnerElement(null);
        $aNewAttr->setOwnerElement($aElement);
        $aElement->attributeHookHandler('set', $aAttr);
        $aElement->attributeHookHandler('changed', $aAttr);
    }

    /**
     * Rewinds the iterator's pointer back to the start.
     */
    public function rewind()
    {
        $this->mPosition = 0;
    }

    /**
     * Sets an attribute on an element.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-set
     *
     * @param Attr $aAttr The attribute to be set on an element.
     *
     * @param Element $aElement The element that owns the list.
     *
     * @return Attr|null
     *
     * @throws InUseAttributeError If the attribute's owning element is not null
     *     and not an element.
     */
    public function setAttr(Attr $aAttr, Element $aElement)
    {
        $owner = $aAttr->ownerElement;

        if ($owner && !($owner instanceof Element)) {
            throw new InUseAttributeError();
        }

        $oldAttr = $this->getAttrByNamespaceAndLocalName(
            $aAttr->namespaceURI,
            $aAttr->localName,
            $aElement
        );

        if ($oldAttr === $aAttr) {
            return $aAttr;
        }

        if ($oldAttr) {
            $this->replaceAttr($oldAttr, $aAttr, $aElement);
        } else {
            $this->appendAttr($aAttr, $aElement);
        }

        return $oldAttr;
    }

    /**
     * Sets the attributes value.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-set-value
     *
     * @param Element $aElement The element that owns the list.
     *
     * @param string $aLocalName The local name of the attribute whose value is
     *     to be set.
     *
     * @param string $aValue The value of the attribute whose value is to be
     *     set.
     *
     * @param string|null $aPrefix Optional. The namespace prefix of the
     *     attribute whose value is to be set.
     *
     * @param string|null $aNamespace Optional. The namespace of the attribute
     *     whose value is to be set.
     */
    public function setAttrValue(
        Element $aElement,
        $aLocalName,
        $aValue,
        $aPrefix = null,
        $aNamespace = null
    ) {
        $attr = $this->getAttrByNamespaceAndLocalName(
            $aNamespace,
            $aLocalName,
            $aElement
        );

        if (!$attr) {
            $attr = new Attr($aLocalName, $aValue, $aNamespace, $aPrefix);
            $this->appendAttr($attr, $aElement);
            return;
        }

        $this->changeAttr($attr, $aElement);
    }

    /**
     * Checks if the iterator's current pointer points to a valid position.
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->mList[$this->mPosition]);
    }
}
