<?php
// https://developer.mozilla.org/en-US/docs/Web/API/Element
// https://dom.spec.whatwg.org/#element

require_once 'Node.class.php';
require_once 'DOMTokenList.class.php';
require_once 'NamedNodeMap.class.php';
require_once 'ParentNode.class.php';
require_once 'ChildNode.class.php';

abstract class Element extends Node implements SplObserver {
	use ParentNode;
	use ChildNode;
	protected $mAttributes; // NamedNodeMap
	protected $mClassList; // ClassList
	protected $mClassName;
	protected $mEndTagOmitted;
	protected $mTagName;

	private $mReconstructClassList;

	protected function __construct() {
		parent::__construct();

		$this->mAttributes = new NamedNodeMap();
		$this->mClassName = '';
		$this->mEndTagOmitted = false;
		$this->mTagName = '';
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
					$this->mClassList = new DOMTokenList();
					$this->mClassList->attach($this);

					if (!empty($this->mClassName)) {
						$this->mClassList->add($this->mClassName);
					}
				}

				return $this->mClassList;

			case 'className':
				return $this->mClassName;

			case 'firstElementChild':
				return $this->getFirstElementChild();

			case 'innerHTML':
				$rv = '';

				foreach ($this->mChildNodes as $child) {
					$rv .= $child->toHTML();
				}

				return $rv;

			case 'lastElementChild':
				return $this->getLastElementChild();

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
		}
	}

	public function appendChild(Node $aNode) {
		$this->mInvalidateChildren = true;
		return parent::appendChild($aNode);
	}

	public function closest($aSelectorRule) {
		// TODO
	}

	public function getAttribute( $aName ) {
		$rv = '';

		foreach ($this->mAttributes as $attribute) {
			if ($attribute->nodeName == $aName) {
				$rv = $attribute->nodeValue;
				break;
			}
		}

		return $rv;
	}

	public function getAttributeNode($aName) {
		foreach ($this->mAttributes as &$attr) {
			if ($attr->name == $aName) {
				return $attr;
			}
		}

		return null;
	}

	public function getElementsByClassName( $aClassName ) {
		$elements = array();

		foreach($this->mChildNodes as $child) {
			if ($child->nodeType == Node::ELEMENT_NODE) {
				if ($child->classList->contains($aClassName)) {
					$elements[] = $child;
				}

				if ($this->hasChildNodes()) {
					$elements = array_merge($elements, $child->getElementsByClassName($aClassName));
				}
			}
		}

		return $elements;
	}

	public function getElementsByTagName($aTagName) {
		// TODO
	}

	public function hasAttribute( $aName ) {
		$rv = false;

		foreach ($this->mAttributes as $attribute) {
			if ($attribute->nodeName == $aName) {
				$rv = true;
				break;
			}
		}

		return $rv;
	}

	public function hasAttributes() {
		return $this->mAttributes->length > 0;
	}

	public function insertAdjacentHTML($aHTML) {
		// TODO
	}

	public function insertBefore(Node $aNewNode, Node $aRefNode = null) {
		$this->mInvalidateChildren = true;
		return parent::insertBefore($aNewNode, $aRefNode);
	}

	public function matches( $aSelectorRule ) {
		// TODO
	}

	public function removeAttribute( $aName ) {
		$attr = $this->mAttributes->removeNamedItem($aName)[0];
		$dict = new CustomEventInit();
		$dict->detail =& $attr;
		$e = new CustomEvent('attributechange', $dict);
		$this->dispatchEvent($e);
	}

	public function removeAttributeNode(Attr $aNode) {
		// TODO
	}

	public function removeChild(Node $aNode) {
		$this->mInvalidateChildren = true;
		return parent::removeChild($aNode);
	}

	public function replaceChild(Node $aNewNode, Node $aOldNode) {
		$this->mInvalidateChildren = true;
		return parent::replaceChild($aNewNode, $aOldNode);
	}

	public function setAttribute($aName, $aValue = "") {
		$node = $this->getAttributeNode($aName);

		if ($node) {
			$node->namespaceURI = null;
			$node->value = $aValue;
		} else {
			$node = new Attr();
			$node->name = $aName;
			$node->value = $aValue;
			$this->mAttributes->setNamedItem($node);
		}

		$dict = new CustomEventInit();
		$dict->detail = $node;
		$event = new CustomEvent('attributechange', $dict);
		$this->dispatchEvent($event);
	}

	public function setAttributeNode(Attr $aNode) {
		// TODO
	}

	public function toHTML() {
		$html = '';

		switch ($this->mNodeType) {
			case Node::ELEMENT_NODE:
				$tagName = strtolower($this->mNodeName);
				$html = '<' . $tagName;

				foreach($this->mAttributes as $attribute) {
					$html .= ' ' . $attribute->nodeName;

					if (!Attr::_isBool($attribute->nodeName)) {
						$html .= '="' . $attribute->nodeValue . '"';
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
		if ($aObject instanceof DOMTokenList && $aObject == $this->mClassList) {
			$this->mClassName = $aObject->__toString();
			$this->_updateAttributeOnPropertyChange('class', $this->mClassName);
		}
	}

	public function _isEndTagOmitted() {
		return $this->mEndTagOmitted;
	}

	protected function _onAttributeChange(Event $aEvent) {
		switch ($aEvent->detail->nodeName) {
			case 'class':
				$this->mReconstructClassList = true;
				$this->mClassName = $aEvent->detail->nodeValue;
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
}