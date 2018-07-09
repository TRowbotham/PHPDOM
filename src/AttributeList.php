<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Attr;
use Rowbot\DOM\AttributeChangeObserver;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\InUseAttributeError;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Support\OrderedSet;
use SplObjectStorage;

class AttributeList extends OrderedSet
{
    /**
     * @var \Rowbot\DOM\Element\Element
     */
    private $element;

    /**
     * @var \SplObjectStorage
     */
    private $observers;

    /**
     * Constructor.
     *
     * @param \Rowbot\DOM\Element\Element $element
     *
     * @return void
     */
    public function __construct(Element $element)
    {
        parent::__construct();

        $this->element = $element;
        $this->observers = new SplObjectStorage();
    }

    /**
     * Changes the value of an attribute.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-change
     *
     * @param \Rowbot\DOM\Attr $attribute The attribute whose value is to be changed.
     * @param string           $value     The attribute's new value.
     *
     * @return void
     */
    public function change(Attr $attribute, $value)
    {
        // TODO: Queue a mutation record of "attributes" for element with name
        // attribute’s local name, namespace attribute’s namespace, and
        // oldValue attribute’s value.

        foreach ($this->observers as $observer) {
            $observer->onAttributeChanged(
                $this->element,
                $attribute->localName,
                $attribute->value,
                $value,
                $attribute->namespaceURI
            );
        }

        $attribute->setValue($value);
    }

    /**
     * Appends an attribute to the list of attributes.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-append
     *
     * @param \Rowbot\DOM\Attr $attribute The attribute to be appended.
     *
     * @return void
     */
    public function append($attribute)
    {
        if (!$attribute instanceof Attr) {
            throw new TypeError();
            return;
        }

        // TODO: Queue a mutation record of "attributes" for element with name
        // attribute’s local name, namespace attribute’s namespace, and
        // oldValue null.

        foreach ($this->observers as $observer) {
            $observer->onAttributeChanged(
                $this->element,
                $attribute->localName,
                null,
                $attribute->value,
                $attribute->namespaceURI
            );
        }

        parent::append($attribute);
        $attribute->setOwnerElement($this->element);
    }

    /**
     * Removes an attribute from the list.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-remove
     *
     * @param \Rowbot\DOM\Attr $attribute The attribute to be removed from the list.
     *
     * @return void
     */
    public function remove($attribute)
    {
        if (!$attribute instanceof Attr) {
            throw new TypeError();
            return;
        }

        // TODO: Queue a mutation record of "attributes" for element with name
        // attribute’s local name, namespace attribute’s namespace, and
        // oldValue attribute’s value.

        foreach ($this->observers as $observer) {
            $observer->onAttributeChanged(
                $this->element,
                $attribute->localName,
                $attribute->value,
                null,
                $attribute->namespaceURI
            );
        }

        parent::remove($attribute);
        $attribute->setOwnerElement(null);
    }

    /**
     * Replaces and attribute with another attribute.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-replace
     *
     * @param \Rowbot\DOM\Attr $oldAttr The attribute being removed from the list.
     * @param \Rowbot\DOM\Attr $newAttr The attribute being inserted into the list.
     *
     * @return void
     */
    public function replace($oldAttr, $newAttr)
    {
        if (!$oldAttr instanceof Attr || !$newAttr instanceof Attr) {
            throw new TypeError();
            return;
        }

        // TODO: Queue a mutation record of "attributes" for element with name
        // oldAttr’s local name, namespace oldAttr’s namespace, and oldValue
        // oldAttr’s value.

        foreach ($this->observers as $observer) {
            $observer->onAttributeChanged(
                $this->element,
                $oldAttr->localName,
                $oldAttr->value,
                $newAttr->value,
                $oldAttr->namespaceURI
            );
        }

        parent::replace($oldAttr, $newAttr);
        $oldAttr->setOwnerElement(null);
        $newAttr->setOwnerElement($this->element);
    }

    /**
     * Gets an attribute using a fully qualified name.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-get-by-name
     *
     * @param string $qualifiedName The fully qualified name of the attribute to find.
     *
     * @return \Rowbot\DOM\Attr|null
     */
    public function getAttrByName($qualifiedName)
    {
        if ($this->element->namespaceURI === Namespaces::HTML
            && $this->element->getNodeDocument() instanceof HTMLDocument
        ) {
            $qualifiedName = Utils::toASCIILowercase($qualifiedName);
        }

        foreach ($this as $attribute) {
            if ($attribute->name === $qualifiedName) {
                return $attribute;
            }
        }

        return null;
    }

    /**
     * Gets an attribute using a namespace and local name.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-get-by-namespace
     *
     * @param string $namespace The namespace of the attribute to find.
     * @param string $localName The local name of the attribute to find.
     *
     * @return \Rowbot\DOM\Attr|null
     */
    public function getAttrByNamespaceAndLocalName(
        $namespace,
        $localName
    ) {
        if ($namespace === '') {
            $namespace = null;
        }

        foreach ($this as $attribute) {
            if ($attribute->namespaceURI === $namespace
                && $attribute->localName === $localName
            ) {
                return $attribute;
            }
        }

        return null;
    }

    /**
     * Gets an attribute's value.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-get-value
     *
     * @param string      $localName The local name of the attribute whose value is to be returned.
     * @param string|null $namespace (optional) The namespace of the attribute whose value is to be returned.
     *
     * @return string
     */
    public function getAttrValue($localName, $namespace = null)
    {
        $attr = $this->getAttrByNamespaceAndLocalName(
            $namespace,
            $localName
        );

        if ($attr === null) {
            return '';
        }

        return $attr->value;
    }

    /**
     * Sets an attribute on an element.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-set
     *
     * @param \Rowbot\DOM\Attr $attr The attribute to be set on an element.
     *
     * @return \Rowbot\DOM\Attr|null
     *
     * @throws \Rowbot\DOM\Exception\InUseAttributeError If the attribute's owning element is not null and not an
     *                                                   element.
     */
    public function setAttr(Attr $attr)
    {
        $owner = $attr->ownerElement;

        if ($owner !== null && $owner !== $this->element) {
            throw new InUseAttributeError();
            return;
        }

        $oldAttr = $this->getAttrByNamespaceAndLocalName(
            $attr->namespace,
            $attr->localName
        );

        if ($oldAttr === $attr) {
            return $attr;
        }

        if ($oldAttr !== null) {
            $this->replace($oldAttr, $attr);

            return $oldAttr;
        }

        $this->append($attr);

        return $oldAttr;
    }

    /**
     * Sets the attributes value.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-set-value
     *
     * @param string      $localName The local name of the attribute whose value is to be set.
     * @param string      $value     The value of the attribute whose value is to be set.
     * @param string|null $prefix    (optional) The namespace prefix of the attribute whose value is to be set.
     * @param string|null $namespace (optional) The namespace of the attribute whose value is to be set.
     *
     * @return void
     */
    public function setAttrValue(
        $localName,
        $value,
        $prefix = null,
        $namespace = null
    ) {
        $attribute = $this->getAttrByNamespaceAndLocalName(
            $namespace,
            $localName
        );

        if ($attribute === null) {
            $attribute = new Attr($localName, $value, $namespace, $prefix);
            $attribute->setNodeDocument($this->element->getNodeDocument());
            $this->append($attribute);
            return;
        }

        $this->change($attribute, $value);
    }

    /**
     * Removes an attribute from the list with the specified fully qualified
     * name.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-remove-by-name
     *
     * @param string $qualifiedName The fully qualified name of the attribute to be removed.
     *
     * @return \Rowbot\DOM\Attr|null
     */
    public function removeAttrByName($qualifiedName)
    {
        $attr = $this->getAttrByName($qualifiedName);

        if ($attr !== null) {
            $this->remove($attr);
        }

        return $attr;
    }

    /**
     * Remove an attribute from the list using a namespace and local name.
     *
     * @see https://dom.spec.whatwg.org/#concept-element-attributes-remove-by-namespace
     *
     * @param string $namespace The namespace of the attribute to be removed.
     * @param string $localName The local name of the attribute to be removed.
     *
     * @return \Rowbot\DOM\Attr|null
     */
    public function removeAttrByNamespaceAndLocalName(
        $namespace,
        $localName
    ) {
        $attr = $this->getAttrByNamespaceAndLocalName($namespace, $localName);

        if ($attr !== null) {
            $this->remove($attr);
        }

        return $attr;
    }

    /**
     * @param \Rowbot\DOM\AttributeChangeObserver $observer
     *
     * @return void
     */
    public function observe(AttributeChangeObserver $observer)
    {
        if (!$this->observers->contains($observer)) {
            $this->observers->attach($observer);
        }
    }

    /**
     * @param \Rowbot\DOM\AttributeChangeObserver $observer
     *
     * @return void
     */
    public function unobserve(AttributeChangeObserver $observer)
    {
        $this->observers->detach($observer);
    }
}
