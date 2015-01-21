<?php
trait ChildNode {
	public function after(Node ...$aNodes) {
	//public function after($aNodes) {
		if (!$this->parentNode) {
			return;
		}

		$next = $this->mNextSibling;

		foreach($aNodes as &$node) {
			$this->insertBefore($node, $next);
		}
	}

	public function before(Node ...$aNodes) {
	//public function before($aNodes) {
		if (!$this->parentNode) {
			return;
		}

		foreach($aNodes as &$node) {
			$this->insertBefore($node, $this);
		}
	}

	public function remove() {
		if (!$this->parentNode) {
			return;
		}

		$this->parentNode->removeChild($this);
	}

	public function replace(Node ...$aNodes) {
	//public function replace($aNodes) {
		if (!$this->parentNode) {
			return;
		}

		$this->after($aNodes);
		$this->remove();
	}
}