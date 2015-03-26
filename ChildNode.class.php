<?php
trait ChildNode {
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

	public function remove() {
		if (!$this->parentNode) {
			return;
		}

		$this->parentNode->removeChild($this);
	}

	public function replace() {
		if (!$this->parentNode || !func_num_args()) {
			return;
		}

		$this->after(func_get_args());
		$this->remove();
	}
}