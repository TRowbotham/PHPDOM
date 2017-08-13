<?php
namespace Rowbot\DOM\Parser;

use Exception;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Element\HTML\HTMLTableElement;
use Rowbot\DOM\Element\HTML\HTMLTableRowElement;
use Rowbot\DOM\Element\HTML\HTMLTableSectionElement;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;

class OpenElementStack extends ElementStack
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
    const BUTTON_SCOPE = [Namespaces::HTML => ['button']];
    const TABLE_SCOPE = [Namespaces::HTML => ['html', 'table', 'template']];
    const SELECT_SCOPE = [Namespaces::HTML => ['optgroup', 'option']];

    private $templateElements;

    public function __construct()
    {
        parent::__construct();

        $this->templateElements = 0;
    }

    public function containsTemplateElement()
    {
        return $this->templateElements > 0;
    }

    public function insertAfter($item, $newItem)
    {
        if (!$item instanceof Node ||
            !$newItem instanceof Node
        ) {
            throw new ParserException('Both arguments must be an instance of Node.');
            return;
        }

        try {
            parent::insertAfter($item, $newItem);
        } catch (ParserException $e) {
            return;
        }

        if ($newItem instanceof HTMLTemplateElement) {
            $this->templateElements++;
        }
    }

    public function push($item)
    {
        if (!$item instanceof Node) {
            throw new ParserException('The pushed item must be an instance of Node.');
            return;
        }

        try {
            parent::push($item);
        } catch (Exception $e) {
            return;
        }

        if ($item instanceof HTMLTemplateElement) {
            $this->templateElements++;
        }
    }

    public function pop()
    {
        try {
            $node = parent::pop();
        } catch (Exception $e) {
            return;
        }

        if ($node instanceof HTMLTemplateElement) {
            $this->templateElements--;
        }

        return $node;
    }

    public function replace($item, $newItem)
    {
        if (!$item instanceof Node ||
            !$newItem instanceof Node
        ) {
            throw new ParserException('Both arguments must be an instance of Node.');
            return;
        }

        $oldLength = $this->length;

        try {
            parent::replace($item, $newItem);
        } catch (ParserException $e) {
            return;
        }

        $itemIsTemplate = $item instanceof HTMLTemplateElement;

        if ($oldLength != $this->length) {
            if ($itemIsTemplate) {
                $this->templateElements--;
            }

            return;
        }

        $newItemIsTemplate = $newItem instanceof HTMLTemplateElement;

        if (!$itemIsTemplate && $newItemIsTemplate) {
            $this->templateElements++;
        } elseif ($itemIsTemplate && !$newItemIsTemplate) {
            $this->templateElements--;
        }
    }

    public function remove($item)
    {
        if (!$item instanceof Node) {
            throw new ParserException('The item being removed must be an instance of Node.');
            return;
        }

        if (!isset($this->map[$this->hash($item)])) {
            throw new ParserException('Can\'t remove an non-existant item from the stack.');
            return;
        }

        parent::remove($item);

        if ($item instanceof HTMLTemplateElement) {
            $this->templateElements--;
        }
    }

    /**
     * Pops nodes off the stack of open elements until it finds a thead, tfoot,
     * tbody, template, or html element.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#clear-the-stack-back-to-a-table-body-context
     */
    public function clearBackToTableBodyContext()
    {
        while ($this->length > 0) {
            $currentNode = $this->map[$this->keys[$this->length - 1]];

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
     */
    public function clearBackToTableContext()
    {
        while ($this->length > 0) {
            $currentNode = $this->map[$this->keys[$this->length - 1]];

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
     */
    public function clearBackToTableRowContext()
    {
        while ($this->length > 0) {
            $currentNode = $this->map[$this->keys[$this->length - 1]];

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
     * @param  [type]  $tagName    [description]
     * @param  [type]  $aNamespace [description]
     * @param  [type]  $list       [description]
     * @return boolean             [description]
     */
    private function hasElementInSpecificScope($tagName, $aNamespace, ...$list)
    {
        $list = array_merge_recursive(...$list);
        $bottom = $this->length - 1;
        $node = $this->map[$this->keys[$bottom]];

        while ($bottom > 0) {
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

            $node = $this->map[$this->keys[--$bottom]];
        }

        return false;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-scope
     * @param  [type]  $aTagName   [description]
     * @param  [type]  $aNamespace [description]
     * @return boolean             [description]
     */
    public function hasElementInScope($aTagName, $aNamespace)
    {
        return $this->hasElementInSpecificScope(
            $aTagName,
            $aNamespace,
            self::SPECIFIC_SCOPE
        );
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-list-item-scope
     * @param  [type]  $aTagName   [description]
     * @param  [type]  $aNamespace [description]
     * @return boolean             [description]
     */
    public function hasElementInListItemScope($aTagName, $aNamespace)
    {
        return $this->hasElementInSpecificScope(
            $aTagName,
            $aNamespace,
            self::SPECIFIC_SCOPE,
            self::LIST_ITEM_SCOPE
        );
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-button-scope
     * @param  [type]  $aTagName   [description]
     * @param  [type]  $aNamespace [description]
     * @return boolean             [description]
     */
    public function hasElementInButtonScope($aTagName, $aNamespace)
    {
        return $this->hasElementInSpecificScope(
            $aTagName,
            $aNamespace,
            self::SPECIFIC_SCOPE,
            self::BUTTON_SCOPE
        );
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-table-scope
     * @param  [type]  $aTagName   [description]
     * @param  [type]  $aNamespace [description]
     * @return boolean             [description]
     */
    public function hasElementInTableScope($aTagName, $aNamespace)
    {
        return $this->hasElementInSpecificScope(
            $aTagName,
            $aNamespace,
            self::TABLE_SCOPE
        );
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/syntax.html#has-an-element-in-select-scope
     * @param  [type]  $aTagName   [description]
     * @param  [type]  $aNamespace [description]
     * @return boolean             [description]
     */
    public function hasElementInSelectScope($aTagName, $aNamespace)
    {
        $bottom = $this->length - 1;
        $node = $this->map[$this->keys[$bottom]];

        while ($bottom > 0) {
            $ns = $node->namespaceURI;
            $localName = $node->localName;

            if ($aNamespace === $ns && $localName === $aTagName) {
                return true;
            }

            if (!($aNamespace === Namespaces::HTML &&
                ($localName === 'optgroup' || $localName === 'option'))
            ) {
                return false;
            }

            $node = $this->map[$this->keys[--$bottom]];
        }

        return false;
    }
}
