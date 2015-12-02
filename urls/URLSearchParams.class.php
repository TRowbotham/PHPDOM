<?php
namespace phpjs\urls;

require_once 'URLParser.class.php';

/**
 * An object containing a list of all URL query parameters.  This allows you to manipulate
 * a URL's query string in a granular manner.
 *
 * @link https://url.spec.whatwg.org/#urlsearchparams
 * @link https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams
 */
class URLSearchParams implements \Iterator {
    private $mIndex;
    private $mParams;
    private $mPosition;
    private $mSequenceId;
    private $mUrl;

    public function __construct($aSearchParams = '') {
        $this->mIndex = array();
        $this->mParams = array();
        $this->mPosition = 0;
        $this->mSequenceId = 0;
        $this->mUrl = null;

        if ($aSearchParams instanceof URLSearchParams) {
            $this->mIndex = $aSearchParams->mIndex;
            $this->mParams = $aSearchParams->mParams;
            $this->mSequenceId = $aSearchParams->mSequenceId;
        } else if (is_string($aSearchParams)) {
            $pairs = URLParser::urlencodedStringParser($aSearchParams);

            foreach ($pairs as $pair) {
                $this->append($pair['name'], $pair['value']);
            }
        }
    }

    /**
     * Appends a new key -> value pair to the end of the query string.
     *
     * @link https://url.spec.whatwg.org/#dom-urlsearchparams-append
     *
     * @param  string $aName  The name of the key in the pair.
     *
     * @param  string $aValue The value assigned to the key.
     */
    public function append($aName, $aValue) {
        $this->mIndex[$this->mSequenceId] = $aName;
        $this->mParams[$aName][$this->mSequenceId++] = $aValue;
        $this->update();
    }

    /**
     * Returns an array containing the query parameters name as the first index's value
     * and the query parameters value as the second index's value.
     *
     * @return string[]
     */
    public function current() {
        $index = array_keys($this->mIndex);
        $sequenceId = $index[$this->mPosition];
        $name = $this->mIndex[$sequenceId];

        return array($name, $this->mParams[$name][$sequenceId]);
    }

    /**
     * Deletes all occurances of pairs with the specified key name.
     *
     * @link https://url.spec.whatwg.org/#dom-urlsearchparams-delete
     *
     * @param  string $aName The name of the key to delete.
     */
    public function delete($aName) {
        foreach ($this->mParams[$aName] as $key) {
            unset($this->mIndex[$key]);
        }

        unset($this->mParams[$aName]);
        $this->update();
    }

    /**
     * Get the value of the first key -> value pair with the specified key name.
     *
     * @link https://url.spec.whatwg.org/#dom-urlsearchparams-get
     *
     * @param  string $aName The name of the key whose value you want to retrive.
     *
     * @return string        The value of the specified key.
     */
    public function get($aName) {
        return $this->has($aName) ? reset($this->mParams[$aName]) : null;
    }

    /**
     * Gets all key -> value pairs that has the specified key name.
     *
     * @link https://url.spec.whatwg.org/#dom-urlsearchparams-getall
     *
     * @param  string   $aName  The name of the key whose values you want to retrieve.
     *
     * @return string[]         An array containing all the values of the specified key.
     */
    public function getAll($aName) {
        return $this->has($aName) ? array_values($this->mParams[$aName]) : array();
    }

    /**
     * Indicates whether or not a query string contains any keys with the specified key name.
     *
     * @link https://url.spec.whatwg.org/#dom-urlsearchparams-has
     *
     * @param  boolean  $aName The key name you want to test if it exists.
     *
     * @return boolean         Returns true if the key exits, otherwise false.
     */
    public function has($aName) {
        return isset($this->mParams[$aName]);
    }

    /**
     * Returns the key of the current name -> value pair of query parameters in the iterator.
     *
     * @return int
     */
    public function key() {
        return $this->mPosition;
    }

    /**
     * Moves the the iterator to the next name -> value pair of query parameters.
     */
    public function next() {
        $this->mPosition++;
    }

    /**
     * Rewinds the iterator back to the beginning position.
     */
    public function rewind() {
        $this->mPosition = 0;
    }

    /**
     * Sets the value of the specified key name.  If multiple pairs exist with the same key name
     * it will set the value for the first occurance of the key in the query string and all other
     * occurances will be removed from the query string.  If the key does not already exist in the
     * query string, it will be added to the end of the query string.
     *
     * @link https://url.spec.whatwg.org/#dom-urlsearchparams-set
     *
     * @param string $aName  The name of the key you want to modify the value of.
     *
     * @param string $aValue The value you want to associate with the key name.
     */
    public function set($aName, $aValue) {
        if ($this->has($aName)) {
            for ($i = count($this->mParams[$aName]) - 1; $i > 0; $i--) {
                end($this->mParams[$aName]);
                unset($this->mIndex[key($this->mParams[$aName])]);
                array_pop($this->mParams[$aName]);
            }

            reset($this->mParams[$aName]);
            $this->mParams[$aName][key($this->mParams[$aName])] = $aValue;
        } else {
            $this->append($aName, $aValue);
        }

        $this->update();
    }

    /**
     * Returns all key -> value pairs stringified in the correct order.
     *
     * @return string The query string.
     */
    public function toString() {
        $list = array();
        $output = '';

        foreach ($this->mIndex as $sequenceId => $name) {
            $list[] = array('name' => $name, 'value' => $this->mParams[$name][$sequenceId]);
        }

        return URLParser::urlencodedSerializer($list);
    }

    /**
     * Returns whether or not the iterator's current postion is a valid one.
     *
     * @return boolean
     */
    public function valid() {
        return $this->mPosition < count($this->mIndex);
    }

    /**
     * Mutates the the list of query parameters without going through the public API.
     *
     * @internal
     *
     * @param  array|null $aList A list of name -> value pairs to be added to the list or null to empty the list.
     */
    public function _mutateList(array $aList = null) {
        $this->mIndex = array();
        $this->mParams = array();
        $this->mSequenceId = 0;

        if (is_array($aList)) {
            foreach ($aList as $pair) {
                $this->mIndex[$this->mSequenceId] = $pair['name'];
                $this->mParams[$pair['name']][$this->mSequenceId++] = $pair['value'];
            }
        }
    }

    /**
     * Set's the URLSearchParam's associated URL object.
     *
     * @internal
     *
     * @param URLInternal|null $aUrl The associated URL object.
     */
    public function _setUrl(URLInternal $aUrl = null) {
        $this->mUrl = $aUrl;
    }

    /**
     * Set's the associated URL object's query to the serialization of URLSearchParams.
     *
     * @link https://url.spec.whatwg.org/#concept-urlsearchparams-update
     *
     * @internal
     */
    protected function update() {
        if ($this->mUrl) {
            $this->mUrl->setQuery($this->toString());
        }
    }
}
