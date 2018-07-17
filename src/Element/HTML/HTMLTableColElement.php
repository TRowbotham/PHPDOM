<?php
namespace Rowbot\DOM\Element\HTML;

/**
 * @see https://html.spec.whatwg.org/#the-colgroup-element
 * @see https://html.spec.whatwg.org/#the-col-element
 */
class HTMLTableColElement extends HTMLElement
{
    private $span;

    protected function __construct()
    {
        parent::__construct();

        $this->span = 0;
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'span':
                return $this->span;

            default:
                return parent::__get($name);
        }
    }

    public function __set(string $name, $value)
    {
        switch ($name) {
            case 'span':
                $this->span = $value;
                $this->_updateAttributeOnPropertyChange($name, $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }
}
