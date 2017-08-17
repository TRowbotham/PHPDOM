<?php
namespace Rowbot\DOM\Parser\Collection;

use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Element\HTML\HTMLTableElement;
use Rowbot\DOM\Element\HTML\HTMLTableRowElement;
use Rowbot\DOM\Element\HTML\HTMLTableSectionElement;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Parser\Collection\Exception\CollectionException;
use Rowbot\DOM\Parser\Collection\Exception\EmptyStackException;
use Rowbot\DOM\Support\UniquelyIdentifiable;

class OpenElementStack extends ObjectStack
{
    const SPECIFIC_SCOPE = [
        Namespaces::HTML => [
            'applet',
            'caption',
            'html',
            'table',
            'td',
            'th',
            'marquee',
            'object',
            'template',
        ],
        Namespaces::MATHML => [
            'mi',
            'mo',
            'mn',
            'ms',
            'mtext',
            'annotation-xml'
        ],
        Namespaces::SVG => [
            'foreignObject',
            'desc',
            'title'
        ]
    ];
    const LIST_ITEM_SCOPE = [Namespaces::HTML => ['ol', 'ul']];
    const BUTTON_SCOPE    = [Namespaces::HTML => ['button']];
    const TABLE_SCOPE     = [Namespaces::HTML => ['html', 'table', 'template']];
    const SELECT_SCOPE    = [Namespaces::HTML => ['optgroup', 'option']];

    /**
     * The number of HTMLTemplateElements on the stack.
     *
     * @var int
     */
    private $templateElementCount;

    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->templateElementCount = 0;
    }

    /**
     * {@inheritDoc}
     */
    public function replace(
        UniquelyIdentifiable $newItem,
        UniquelyIdentifiable $oldItem
    ) {
        try {
            parent::replace($newItem, $oldItem);
        } catch (CollectionException $e) {
            throw $e;
            return;
        }

        if ($newItem instanceof HTMLTemplateElement) {
            ++$this->templateElementCount;
        }

        if ($oldItem instanceof HTMLTemplateElement) {
            --$this->templateElementCount;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function insertBefore(
        UniquelyIdentifiable $newItem,
        UniquelyIdentifiable $oldItem = null
    ) {
        try {
            parent::insertBefore($newItem, $oldItem);
        } catch (CollectionException $e) {
            throw $e;
            return;
        }

        if ($newItem instanceof HTMLTemplateElement) {
            ++$this->templateElementCount;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function insertAfter(
        UniquelyIdentifiable $newItem,
        UniquelyIdentifiable $oldItem
    ) {
        try {
            parent::insertAfter($newItem, $oldItem);
        } catch (CollectionException $e) {
            throw $e;
            return;
        }

        if ($newItem instanceof HTMLTemplateElement) {
            ++$this->templateElementCount;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function remove(UniquelyIdentifiable $item)
    {
        try {
            parent::remove($item);
        } catch (CollectionException $e) {
            throw $e;
            return;
        }

        if ($item instanceof HTMLTemplateElement) {
            --$this->templateElementCount;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function push(UniquelyIdentifiable $item)
    {
        try {
            parent::push($item);
        } catch (CollectionException $e) {
            throw $e;
            return;
        }

        if ($item instanceof HTMLTemplateElement) {
            ++$this->templateElementCount;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function pop()
    {
        try {
            $popped = parent::pop();
        } catch (CollectionException $e) {
            throw $e;
            return;
        }

        if ($popped instanceof HTMLTemplateElement) {
            --$this->templateElementCount;
        }

        return $popped;
    }

    /**
     * {@inheritDoc}
     */
    public function top()
    {
        if (empty($this->collection)) {
            throw new EmptyStackException();
            return;
        }

        return $this->collection[0];
    }

    /**
     * {@inheritDoc}
     */
    public function bottom()
    {
        if (empty($this->collection)) {
            throw new EmptyStackException();
            return;
        }

        return end($this->collection);
    }

    /**
     * Returns true if the stack contains a template element, false otherwise.
     *
     * @return bool
     */
    public function containsTemplateElement(): bool
    {
        return $this->templateElementCount > 0;
    }

    /**
     * Pops nodes off the stack of open elements until it finds a thead, tfoot,
     * tbody, template, or html element.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#clear-the-stack-back-to-a-table-body-context
     *
     * @return void
     */
    public function clearBackToTableBodyContext()
    {
        while (true) {
            $currentNode = $this->bottom();

            if ($currentNode instanceof HTMLTableSectionElement ||
                $currentNode instanceof HTMLTemplateElement ||
                $currentNode instanceof HTMLHtmlElement
            ) {
                break;
            }

            $this->pop();
        }
    }

    /**
     * Pops nodes off the stack of open elements until it finds a table,
     * template, or html element.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#clear-the-stack-back-to-a-table-context
     *
     * @return void
     */
    public function clearBackToTableContext()
    {
        foreach ($this as $currentNode) {
            if ($currentNode instanceof HTMLTableElement ||
                $currentNode instanceof HTMLTemplateElement ||
                $currentNode instanceof HTMLHtmlElement
            ) {
                break;
            }

            $this->pop();
        }
    }

    /**
     * Pops nodes off the stack of open elements until it finds a tr, template,
     * or html element.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#clear-the-stack-back-to-a-table-row-context
     *
     * @return void
     */
    public function clearBackToTableRowContext()
    {
        foreach ($this as $currentNode) {
            if ($currentNode instanceof HTMLTableRowElement ||
                $currentNode instanceof HTMLTemplateElement ||
                $currentNode instanceof HTMLHtmlElement
            ) {
                break;
            }

            $this->pop();
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-the-specific-scope
     *
     * @param  string $tagName
     * @param  string $aNamespace
     * @param  array  $list
     *
     * @return bool
     */
    private function hasElementInSpecificScope(
        $tagName,
        $aNamespace,
        ...$list
    ): bool {
        $list = array_merge_recursive(...$list);

        foreach ($this as $node) {
            $ns = $node->namespaceURI;
            $localName = $node->localName;

            if ($aNamespace === $ns && $localName === $tagName) {
                return true;
            }

            foreach ($list as $namespace => $elements) {
                foreach ($elements as $name) {
                    if ($namespace === $ns && $name === $localName) {
                        return false;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-scope
     *
     * @param  string $tagName
     * @param  string $namespace
     *
     * @return bool
     */
    public function hasElementInScope($tagName, $namespace): bool
    {
        return $this->hasElementInSpecificScope(
            $tagName,
            $namespace,
            self::SPECIFIC_SCOPE
        );
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-list-item-scope
     *
     * @param  string $tagName
     * @param  string $namespace
     *
     * @return bool
     */
    public function hasElementInListItemScope($tagName, $namespace): bool
    {
        return $this->hasElementInSpecificScope(
            $tagName,
            $namespace,
            self::SPECIFIC_SCOPE,
            self::LIST_ITEM_SCOPE
        );
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-button-scope
     *
     * @param  string $tagName
     * @param  string $namespace
     *
     * @return bool
     */
    public function hasElementInButtonScope($tagName, $namespace): bool
    {
        return $this->hasElementInSpecificScope(
            $tagName,
            $namespace,
            self::SPECIFIC_SCOPE,
            self::BUTTON_SCOPE
        );
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-table-scope
     *
     * @param  string $tagName
     * @param  string $namespace
     *
     * @return bool
     */
    public function hasElementInTableScope($tagName, $namespace): bool
    {
        return $this->hasElementInSpecificScope(
            $tagName,
            $namespace,
            self::TABLE_SCOPE
        );
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-select-scope
     *
     * @param  string $tagName
     * @param  string $namespace
     *
     * @return bool
     */
    public function hasElementInSelectScope($tagName, $namespace): bool
    {
        foreach ($this as $node) {
            $ns = $node->namespaceURI;
            $localName = $node->localName;

            if ($namespace === $ns && $localName === $tagName) {
                return true;
            }

            if (!($namespace === Namespaces::HTML &&
                ($localName === 'optgroup' || $localName === 'option'))
            ) {
                return false;
            }
        }

        return false;
    }
}
