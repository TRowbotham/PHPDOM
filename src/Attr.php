<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;

/**
 * Represents a content attribute on an Element.
 *
 * @see https://dom.spec.whatwg.org/#attr
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Attr
 *
 * @property-read string $localName The attribute's local name.
 *
 * @property-read string $name The attribute's fully qualified name, usually in
 *     the form of prefix:localName or localName if the attribute does not have
 *     a namespace.
 *
 * @property-read string|null $namespaceURI The attribute's namespace or null
 *     if it does not have a namespace.
 *
 * @property-read Element|null $ownerElement The Element to which this attribute
 *     belongs to, or null if it is not owned by an Element.
 *
 * @property-read string|null $prefix The attribute's namespace prefix or null
 *     if it does not have a namespace.
 *
 * @property-read string $value The value of the attribute.
 */
class Attr extends Node
{
    /**
     * @var string
     */
    private $localName;

    /**
     * @var string|null
     */
    private $namespaceURI;

    /**
     * @var Element|null
     */
    private $ownerElement;

    /**
     * @var string|null
     */
    private $prefix;

    /**
     * @var string
     */
    private $value;

    public function __construct(
        $localName,
        $value,
        $namespace = null,
        $prefix = null
    ) {
        parent::__construct();

        $this->localName = $localName;
        $this->nodeType = self::ATTRIBUTE_NODE;
        $this->namespaceURI = $namespace;
        $this->ownerElement = null;
        $this->prefix = $prefix;
        $this->value = $value;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'localName':
                return $this->localName;

            case 'name':
                if ($this->prefix) {
                    return $this->prefix . ':' . $this->localName;
                }

                return $this->localName;

            case 'namespaceURI':
                return $this->namespaceURI;

            case 'ownerElement':
                return $this->ownerElement;

            case 'prefix':
                return $this->prefix;

            case 'value':
                return $this->value;

            default:
                return parent::__get($name);
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'value':
                $this->setExistingAttributeValue(Utils::DOMString($value));

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
    ): Node {
        $document = $document ?: $this->getNodeDocument();
        $copy = new static(
            $this->localName,
            $this->value,
            $this->namespaceURI,
            $this->prefix
        );
        $this->postCloneNode($copy, $document, $cloneChildren);

        return $copy;
    }

    /**
     * Set the attribute's owning Element.
     *
     * @internal
     *
     * @param Element|null $element The Element that this attribute belongs
     *     to.
     */
    public function setOwnerElement(Element $element = null)
    {
        $this->ownerElement = $element;
    }

    /**
     * Sets the attribute's value without running the change algorithm when an
     * owning element is present.
     *
     * @internal
     *
     * @param string $value The attribute's value.
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Sets the value of an existing attribute.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#set-an-existing-attribute-value
     *
     * @param string $value The attribute's value.
     */
    protected function setExistingAttributeValue($value)
    {
        if (!$this->ownerElement) {
            $this->value = $value;
            return;
        }

        $this->ownerElement->getAttributeList()->change($this, $value);
    }

    /**
     * Gets the name of the node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodename
     * @see Node::getNodeName()
     *
     * @return string Returns the attirbute's qualified name.
     */
    protected function getNodeName(): string
    {
        if ($this->prefix) {
            return $this->prefix . ':' . $this->localName;
        }

        return $this->localName;
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
    public function getLength(): int
    {
        // Attr nodes cannot contain children, so just return 0.
        return 0;
    }

    /**
     * Gets the value of the node.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodevalue
     * @see Node::getNodeValue()
     *
     * @return string
     */
    protected function getNodeValue(): string
    {
        return $this->value;
    }

    /**
     * Sets the node's value.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodevalue
     * @see Node::setNodeValue()
     *
     * @param string|null $newValue The node's new value.
     */
    protected function setNodeValue($newValue): void
    {
        $this->setExistingAttributeValue($newValue);
    }

    /**
     * Gets the attribute's value.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-textcontent
     * @see Node::getTextContent()
     *
     * @return string
     */
    protected function getTextContent(): string
    {
        return $this->value;
    }

    /**
     * Sets the nodes text content.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-textcontent
     * @see Node::setTextContent()
     *
     * @param string|null $newValue The new attribute value.
     */
    protected function setTextContent($newValue): void
    {
        $this->setExistingAttributeValue($newValue);
    }
}
