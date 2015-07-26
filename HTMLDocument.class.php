<?php
require_once 'Document.class.php';
require_once 'EventListener.class.php';

class HTMLDocument extends Document {
	private $mHead;
	private $mTitle;
	private $mBody;

	public function __construct() {
		parent::__construct();

		$this->mContentType = 'text/html';
		$this->mDoctype = $this->implementation->createDocumentType('html', '', '');
		$this->mDocumentElement = $this->createElement('html');
		$this->mHead = $this->createElement('head');
		$this->mBody = $this->createElement('body');
		$this->mTitle = '';
		$this->mHead->appendChild($this->createElement('title'));
		$this->mDocumentElement->appendChild($this->mHead);
		$this->mDocumentElement->appendChild($this->mBody);
		$this->appendChild($this->mDoctype);
		$this->appendChild($this->mDocumentElement);
	}

	public function __get( $aName ) {
		switch ($aName) {
			case 'body':
				return $this->mBody;
			case 'head':
				return $this->mHead;
			case 'title':
				return $this->mTitle;
			default:
				return parent::__get($aName);
		}
	}

	public function __set($aName, $aValue) {
		switch ($aName) {
			case 'title':
				if (!is_string($aValue)) {
					break;
				}

				$this->mTitle = $aValue;
				$this->head->getElementsByTagName('title')[0]->text = $aValue;

				break;
		}
	}

	/**
	 * Creates an HTMLElement with the specified tag name.
	 * @param  string 		$aTagName 	The name of the element to create.
	 * @return HTMLElement 				A known HTMLElement or HTMLUnknownElement.
	 */
	public function createElement($aTagName) {
		switch($aTagName) {
			/**
			 * These are elements whose tag name differs
			 * from its DOM interface name, so map the tag
			 * name to the interface name.
			 */
			case 'a':
				$interfaceName = 'Anchor';

				break;

			case 'br':
				$interfaceName = 'BR';

				break;

			case 'datalist':
				$interfaceName = 'DataList';

				break;

			case 'dl':
				$interfaceName = 'DList';

				break;

			case 'fieldset':
				$interfaceName = 'FieldSet';

				break;

			case 'hr':
				$interfaceName = 'HR';

				break;

			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
				$interfaceName = 'Heading';

				break;

			case 'iframe':
				$interfaceName = 'IFrame';

				break;

			case 'img':
				$interfaceName = 'Image';

				break;

			case 'ins':
			case 'del':
				$interfaceName = 'Mod';

				break;

			case 'li':
				$interfaceName = 'LI';

				break;

			case 'ol':
				$interfaceName = 'OList';

				break;

			case 'optgroup':
				$interfaceName = 'OptGroup';

				break;

			case 'p':
				$interfaceName = 'Paragraph';

				break;

			case 'blockquote':
			case 'cite':
			case 'q':
				$interfaceName = 'Quote';

				break;

			case 'caption':
				$interfaceName = 'TableCaption';

				break;

			case 'td':
				$interfaceName = 'TableDataCell';

				break;

			case 'th':
				$interfaceName = 'TableHeaderCell';

				break;

			case 'col':
			case 'colgroup':
				$interfaceName = 'TableCol';

				break;

			case 'tr':
				$interfaceName = 'TableRow';

				break;

			case 'tbody':
			case 'thead':
			case 'tfoot':
				$interfaceName = 'TableSection';

				break;

			case 'textarea':
				$interfaceName = 'TextArea';

				break;

			case 'ul':
				$interfaceName = 'UList';

				break;

			/**
			 * These are known HTML elements that don't have their
			 * own DOM interface, but should not be classified as
			 * HTMLUnknownElements.
			 */
			case 'abbr':
			case 'address':
			case 'article':
			case 'aside':
			case 'b':
			case 'bdi':
			case 'bdo':
			case 'cite':
			case 'code':
			case 'dd':
			case 'dfn':
			case 'dt':
			case 'em':
			case 'figcaption':
			case 'figure':
			case 'footer':
			case 'header':
			case 'hrgroup':
			case 'i':
			case 'kbd':
			case 'main':
			case 'mark':
			case 'nav':
			case 'rp':
			case 'rt':
			case 'rtc':
			case 'ruby':
			case 's':
			case 'samp':
			case 'section':
			case 'small':
			case 'strong':
			case 'sub':
			case 'sup':
			case 'u':
			case 'var':
			case 'wbr':
				$interfaceName = '';

				break;

			/**
			 * These are known HTML elements that have their own
			 * DOM interface and their names do not differ from
			 * their interface names.
			 */
			case 'area':
			case 'audio':
			case 'base':
			case 'body':
			case 'button':
			case 'canvas':
			case 'data':
			case 'div':
			case 'embed':
			case 'form':
			case 'head':
			case 'html':
			case 'input':
			case 'keygen':
			case 'label':
			case 'legend':
			case 'link':
			case 'map':
			case 'meta':
			case 'meter':
			case 'object':
			case 'option':
			case 'output':
			case 'param':
			case 'picture':
			case 'pre':
			case 'progress':
			case 'script':
			case 'select':
			case 'source':
			case 'span':
			case 'style':
			case 'table':
			case 'time':
			case 'title':
			case 'track':
			case 'video':
				$interfaceName = ucfirst(strtolower($aTagName));

				break;

			default:
				$interfaceName = 'Unknown';
		}

		$className = 'HTML' . $interfaceName . 'Element';
		require_once 'HTMLElement/' . $className . '.class.php';

		$node = new $className($aTagName);
		$node->mOwnerDocument = $this;

		return $node;
	}

	public function toHTML() {
		$html = '';

		foreach($this->mChildNodes as $child) {
			$html .= $child->toHTML();
		}

		return $html;
	}

	public function __toString() {
		return $this->toHTML();
	}
}
