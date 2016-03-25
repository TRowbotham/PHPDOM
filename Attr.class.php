<?php
namespace phpjs;

use phpjs\elements\Element;

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
class Attr {
    /**
     * @var string
     */
    protected $mLocalName;

    /**
     * @var string
     */
    protected $mName;

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
        $aName = null,
        $aNamespace = null,
        $aPrefix = null
    ) {
        $this->mLocalName = $aLocalName;
        $this->mName = $aName ? $aName : $aLocalName;
        $this->mNamespaceURI = $aNamespace;
        $this->mOwnerElement = null;
        $this->mPrefix = $aPrefix;
        $this->mValue = $aValue;
    }

    public function __destruct()
    {
        $this->mOwnerElement = null;
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'localName':
                return $this->mLocalName;

            case 'name':
                return $this->mName;

            case 'namespaceURI':
                return $this->mNamespaceURI;

            case 'ownerElement':
                return $this->mOwnerElement;

            case 'prefix':
                return $this->mPrefix;

            case 'value':
                return $this->mValue;
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'value':
                if (!$this->mOwnerElement) {
                    $this->mValue = $aValue;
                } else {
                    $this->mOwnerElement->_changeAttributeValue($this, $aValue);
                }
        }
    }

    /**
     * Set's the attribute's owning Element.
     *
     * @internal
     *
     * @param Element|null $aElement The Element that this attribute belongs
     *     to.
     */
    public function _setOwnerElement(Element $aElement = null)
    {
        $this->mOwnerElement = $aElement;
    }
}
