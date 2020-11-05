<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\Collection;

use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Element\HTML\HTMLTableElement;
use Rowbot\DOM\Element\HTML\HTMLTableRowElement;
use Rowbot\DOM\Element\HTML\HTMLTableSectionElement;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Parser\Collection\Exception\DuplicateItemException;
use Rowbot\DOM\Parser\Collection\Exception\EmptyStackException;
use Rowbot\DOM\Parser\Collection\Exception\NotInCollectionException;

use function array_merge_recursive;
use function array_push;
use function array_search;
use function array_splice;

/**
 * @extends \Rowbot\DOM\Parser\Collection\ObjectStack<\Rowbot\DOM\Node>
 */
class OpenElementStack extends ObjectStack
{
    protected const SPECIFIC_SCOPE = [
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
            'annotation-xml',
        ],
        Namespaces::SVG => [
            'foreignObject',
            'desc',
            'title',
        ],
    ];
    protected const LIST_ITEM_SCOPE = [Namespaces::HTML => ['ol', 'ul']];
    protected const BUTTON_SCOPE    = [Namespaces::HTML => ['button']];
    protected const TABLE_SCOPE     = [Namespaces::HTML => ['html', 'table', 'template']];
    protected const SELECT_SCOPE    = [Namespaces::HTML => ['optgroup', 'option']];

    /**
     * The number of HTMLTemplateElements on the stack.
     *
     * @var int
     */
    private $templateElementCount;

    public function __construct()
    {
        parent::__construct();

        $this->templateElementCount = 0;
    }

    public function push($item): void
    {
        parent::push($item);

        if ($item instanceof HTMLTemplateElement) {
            ++$this->templateElementCount;
        }
    }

    public function pop()
    {
        $popped = parent::pop();

        if ($popped instanceof HTMLTemplateElement) {
            --$this->templateElementCount;
        }

        return $popped;
    }

    public function top()
    {
        if ($this->size === 0) {
            throw new EmptyStackException();
        }

        return $this->stack[0];
    }

    public function bottom()
    {
        if ($this->size === 0) {
            throw new EmptyStackException();
        }

        return $this->stack[$this->size - 1];
    }

    public function remove($item): void
    {
        parent::remove($item);

        if ($item instanceof HTMLTemplateElement) {
            --$this->templateElementCount;
        }
    }

    public function replace($newItem, $oldItem): void
    {
        parent::replace($newItem, $oldItem);

        if ($newItem instanceof HTMLTemplateElement) {
            ++$this->templateElementCount;
        }

        if ($oldItem instanceof HTMLTemplateElement) {
            --$this->templateElementCount;
        }
    }

    public function insertAfter($newItem, $oldItem): void
    {
        if (!$this->cache->contains($oldItem)) {
            throw new NotInCollectionException();
        }

        if ($this->cache->contains($newItem)) {
            throw new DuplicateItemException();
        }

        if ($newItem instanceof HTMLTemplateElement) {
            ++$this->templateElementCount;
        }

        $this->cache->attach($newItem);
        ++$this->size;

        if ($this->stack[$this->size - 2] === $oldItem) {
            array_push($this->stack, $newItem);

            return;
        }

        $index = array_search($oldItem, $this->stack, true);
        array_splice($this->stack, $index + 1, 0, [$newItem]);
    }

    /**
     * Returns true if the stack contains a template element, false otherwise.
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
     */
    public function clearBackToTableBodyContext(): void
    {
        $size = $this->size;

        while ($size--) {
            $currentNode = $this->stack[$size];

            if (
                $currentNode instanceof HTMLTableSectionElement
                || $currentNode instanceof HTMLTemplateElement
                || $currentNode instanceof HTMLHtmlElement
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
     */
    public function clearBackToTableContext(): void
    {
        $size = $this->size;

        while ($size--) {
            $currentNode = $this->stack[$size];

            if (
                $currentNode instanceof HTMLTableElement
                || $currentNode instanceof HTMLTemplateElement
                || $currentNode instanceof HTMLHtmlElement
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
     */
    public function clearBackToTableRowContext(): void
    {
        $size = $this->size;

        while ($size--) {
            $currentNode = $this->stack[$size];

            if (
                $currentNode instanceof HTMLTableRowElement
                || $currentNode instanceof HTMLTemplateElement
                || $currentNode instanceof HTMLHtmlElement
            ) {
                break;
            }

            $this->pop();
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-the-specific-scope
     *
     * @param array<string, list<string>> $list
     */
    private function hasElementInSpecificScope(
        string $tagName,
        string $aNamespace,
        array ...$list
    ): bool {
        $list = array_merge_recursive(...$list);
        $size = $this->size;

        while ($size--) {
            $node = $this->stack[$size];

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
     */
    public function hasElementInScope(string $tagName, string $namespace): bool
    {
        return $this->hasElementInSpecificScope($tagName, $namespace, self::SPECIFIC_SCOPE);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-list-item-scope
     */
    public function hasElementInListItemScope(string $tagName, string $namespace): bool
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
     */
    public function hasElementInButtonScope(string $tagName, string $namespace): bool
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
     */
    public function hasElementInTableScope(string $tagName, string $namespace): bool
    {
        return $this->hasElementInSpecificScope($tagName, $namespace, self::TABLE_SCOPE);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-select-scope
     */
    public function hasElementInSelectScope(string $tagName, string $namespace): bool
    {
        $size = $this->size;

        while ($size--) {
            $node = $this->stack[$size];
            $ns = $node->namespaceURI;
            $localName = $node->localName;

            if ($namespace === $ns && $localName === $tagName) {
                return true;
            }

            if (
                !(
                    $namespace === Namespaces::HTML
                    && ($localName === 'optgroup' || $localName === 'option')
                )
            ) {
                return false;
            }
        }

        return false;
    }
}
