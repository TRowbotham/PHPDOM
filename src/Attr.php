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
    protected $mLocalName;

    /**
     * @var string|null
     */
    protected $mNamespaceURI;

    /**
     * @var Element|null
     */
    protected $mOwnerElement;

    /**
     * @var string|null
     */
    protected $mPrefix;

    /**
     * @var string
     */
    protected $mValue;

    public function __construct(
        $aLocalName,
        $aValue,
        $aNamespace = null,
        $aPrefix = null
    ) {
        parent::__construct();

        $this->mLocalName = $aLocalName;
        $this->mNodeType = self::ATTRIBUTE_NODE;
        $this->mNamespaceURI = $aNamespace;
        $this->mOwnerElement = null;
        $this->mPrefix = $aPrefix;
        $this->mValue = $aValue;
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'localName':
                return $this->mLocalName;

            case 'name':
                if ($this->mPrefix) {
                    return $this->mPrefix . ':' . $this->mLocalName;
                }

                return $this->mLocalName;

            case 'namespaceURI':
                return $this->mNamespaceURI;

            case 'ownerElement':
                return $this->mOwnerElement;

            case 'prefix':
                return $this->mPrefix;

            case 'value':
                return $this->mValue;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'value':
                $this->setExistingAttributeValue(Utils::DOMString($aValue));

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    /**
     * Set the attribute's owning Element.
     *
     * @internal
     *
     * @param Element|null $aElement The Element that this attribute belongs
     *     to.
     */
    public function setOwnerElement(Element $aElement = null)
    {
        $this->mOwnerElement = $aElement;
    }

    /**
     * Sets the attribute's value without running the change algorithm when an
     * owning element is present.
     *
     * @internal
     *
     * @param string $aValue The attribute's value.
     */
    public function setValue($aValue)
    {
        $this->mValue = $aValue;
    }

    /**
     * Sets the value of an existing attribute.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#set-an-existing-attribute-value
     *
     * @param string $aValue The attribute's value.
     */
    protected function setExistingAttributeValue($aValue)
    {
        if (!$this->mOwnerElement) {
            $this->mValue = $aValue;
            return;
        }

        $this->mOwnerElement->getAttributeList()->change(
            $this,
            $aValue
        );
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
    protected function getNodeName()
    {
        if ($this->mPrefix) {
            return $this->mPrefix . ':' . $this->mLocalName;
        }

        return $this->mLocalName;
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
    protected function getNodeValue()
    {
        return $this->mValue;
    }

    /**
     * Sets the node's value.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-nodevalue
     * @see Node::setNodeValue()
     *
     * @param string|null $aNewValue The node's new value.
     */
    protected function setNodeValue($aNewValue)
    {
        $this->setExistingAttributeValue($aNewValue);
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
    protected function getTextContent()
    {
        return $this->mValue;
    }

    /**
     * Sets the nodes text content.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-node-textcontent
     * @see Node::setTextContent()
     *
     * @param string|null $aNewValue The new attribute value.
     */
    protected function setTextContent($aNewValue)
    {
        $this->setExistingAttributeValue($aNewValue);
    }
}
