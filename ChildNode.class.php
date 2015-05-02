<?php
// https://developer.mozilla.org/en-US/docs/Web/API/ChildNode
// https://dom.spec.whatwg.org/#interface-childnode

trait ChildNode {
	/**
	 * Inserts any number of Node or DOMString objects after this ChildNode.
	 * @param  Node|DOMString ...$aNodes A set of Node or DOMString objects to be inserted.
	 */
	public function after() {
		if (!$this->parentNode || !func_num_args()) {
			return;
		}

		$df = new DocumentFragment();

		foreach (func_get_args() as $node) {
			if ($node instanceof DocumentFragment) {
				foreach ($node->childNodes as $child) {
					$df->appendChild($child);
				}
			} elseif ($node instanceof Node) {
				$df->appendChild($node);
			}
		}

		$this->insertBefore($df, $this->mNextSibling);
	}

	/**
	 * Inserts any number of Node or DOMString objects before this ChildNode.
	 * @param  Node|DOMString ...$aNodes A set of Node or DOMString objects to be inserted.
	 */
	public function before() {
		if (!$this->parentNode || !func_num_args()) {
			return;
		}

		$df = new DocumentFragment();

		foreach (func_get_args() as $node) {
			if ($node instanceof DocumentFragment) {
				foreach ($node->childNodes as $child) {
					$df->appendChild($child);
				}
			} elseif ($node instanceof Node) {
				$df->appendChild($node);
			}
		}

		$this->insertBefore($df, $this);
	}

	/**
	 * Removes this ChildNode from its ParentNode.
	 */
	public function remove() {
		if (!$this->parentNode) {
			return;
		}

		$this->parentNode->removeChild($this);
	}

	/**
	 * Replaces this ChildNode with any number of Node or DOMString objects.
	 * @param  Node|DOMString ...$aNodes A set of Node or DOMString objects to be inserted in place of this ChildNode.
	 */
	public function replaceWith() {
		if (!$this->parentNode || !func_num_args()) {
			return;
		}

		$this->after(func_get_args());
		$this->remove();
	}
}