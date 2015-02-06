<?php
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

	private $mReconstructClassList;

	protected function __construct() {
		parent::__construct();

		$this->mAttributes = new NamedNodeMap();
		$this->mEndTagOmitted = false;
		$this->mClassName = '';
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
				return $this->mNodeName;

			default:
				return parent::__get($aName);
		}
	}

	public function __set( $aName, $aValue ) {
		switch ($aName) {
			case 'className':
				$this->mClassName = $aValue;
				$this->mReconstructClassList = true;

				if (empty($aValue) || $this->mClassName == '') {
					$this->removeAttribute('class');
				} else {
					$this->setAttribute('class', $aValue);
				}

				break;
		}
	}

	public function appendChild(Node $aNode) {
		$this->mInvalidateChildren = true;
		return parent::appendChild($aNode);
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

	public function insertBefore(Node $aNewNode, Node $aRefNode = null) {
		$this->mInvalidateChildren = true;
		return parent::insertBefore($aNewNode, $aRefNode);
	}

	public function matches( $aSelectorRule ) {
		// TODO
	}

	public function removeAttribute( $aName ) {
		$this->mAttributes->removeNamedItem($aName);
	}

	public function removeChild(Node $aNode) {
		$this->mInvalidateChildren = true;
		return parent::removeChild($aNode);
	}

	public function replaceChild($aNewNode, $aOldNode) {
		$this->mInvalidateChildren = true;
		return parent::replaceChild($aNewNode, $aOldNode);
	}

	public function setAttribute( $aName, $aValue = "" ) {
		$updateOnly = false;

		foreach ($this->mAttributes as $attribute) {
			if ($attribute->nodeName == $aName) {
				$attribute->nodeValue = $aValue;
				$updateOnly = true;
				break;
			}
		}

		if (!$updateOnly) {
			$node = new Attr();
			$node->nodeName = $aName;
			$node->nodeValue = $aValue;
			$this->mAttributes->setNamedItem($node);
		}
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
		if ($aObject instanceof DOMTokenList) {
			$this->mClassName = $aObject->__toString();

			if (!$this->mClassName) {
				$this->removeAttribute('class');
			} else {
				$this->setAttribute('class', $this->mClassName);
			}
		}
	}

	public function _isEndTagOmitted() {
		return $this->mEndTagOmitted;
	}
}