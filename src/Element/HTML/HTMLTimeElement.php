<?php
namespace Rowbot\DOM\Element\HTML;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-time-element
 */
class HTMLTimeElement extends HTMLElement
{
    private $dateTime;

    protected function __construct()
    {
        parent::__construct();

        $this->dateTime = '';
    }

    public function __get($name)
    {
        switch ($name) {
            case 'dateTime':
                return $this->dateTime;

            default:
                return parent::__get($name);
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'dateTime':
                if (!\is_string($value)) {
                    break;
                }

                $this->dateTime = $value;
                $this->_updateAttributeOnPropertyChange($name, $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }
}
