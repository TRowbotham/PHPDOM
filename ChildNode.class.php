<?php
// https://developer.mozilla.org/en-US/docs/Web/API/ChildNode
// https://dom.spec.whatwg.org/#interface-childnode

trait ChildNode {
	/**
	 * Inserts any number of Node or DOMString objects after this ChildNode.
	 * @param  Node|DOMString ...$aNodes A set of Node or DOMString objects to be inserted.
	 */
	public function after() {
		if (!$this->mParentNode || !func_num_args()) {
			return;
		}

		$node = $this->mutationMethodMacro(func_get_args());
		$this->mParentNode->preinsertNodeBeforeChild($node, $this->mNextSibling);
	}

	/**
	 * Inserts any number of Node or DOMString objects before this ChildNode.
	 * @param  Node|DOMString ...$aNodes A set of Node or DOMString objects to be inserted.
	 */
	public function before() {
		if (!$this->mParentNode || !func_num_args()) {
			return;
		}

		$node = $this->mutationMethodMacro(func_get_args());
		$this->mParentNode->preinsertNodeBeforeChild($node, $this);
	}

	/**
	 * Removes this ChildNode from its ParentNode.
	 */
	public function remove() {
		if (!$this->mParentNode) {
			return;
		}

		$this->mParentNode->_removeChild($this);
	}

	/**
	 * Replaces this ChildNode with any number of Node or DOMString objects.
	 * @param  Node|DOMString ...$aNodes A set of Node or DOMString objects to be inserted in place of this ChildNode.
	 */
	public function replaceWith() {
		if (!$this->parentNode || !func_num_args()) {
			return;
		}

		$node = $this->mutationMethodMacro(func_get_args());
		$this->mParentNode->replaceChild($node, $this);
	}

	private function mutationMethodMacro($aNodes) {
		$node = null;
		$nodes = $aNodes;

		// Turn all strings into Text nodes.
		array_walk($nodes, function(&$aArg) {
			if (is_string($aArg)) {
				$aArg = new Text($aArg);
			}
		});

		// If we were given mutiple nodes, throw them all into a DocumentFragment
		if (count($nodes) > 1) {
			$node = new DocumentFragment();

			foreach ($nodes as $arg) {
				$node->appendChild($arg);
			}
		} else {
			$node = $nodes[0];
		}

		return $node;
	}
}
