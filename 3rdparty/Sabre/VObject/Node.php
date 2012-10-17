<?php

namespace Sabre\VObject;

/**
 * Base class for all nodes
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Node implements \IteratorAggregate, \ArrayAccess, \Countable {

    /**
     * The following constants are used by the validate() method.
     */
    const REPAIR = 1;

    /**
     * Turns the object back into a serialized blob.
     *
     * @return string
     */
    abstract function serialize();

    /**
     * Iterator override
     *
     * @var ElementList
     */
    protected $iterator = null;

    /**
     * A link to the parent node
     *
     * @var Node
     */
    public $parent = null;

    /**
     * Validates the node for correctness.
     *
     * The following options are supported:
     *   - Node::REPAIR - If something is broken, and automatic repair may
     *                    be attempted.
     *
     * An array is returned with warnings.
     *
     * Every item in the array has the following properties:
     *    * level - (number between 1 and 3 with severity information)
     *    * message - (human readable message)
     *    * node - (reference to the offending node)
     *
     * @param int $options
     * @return array
     */
    public function validate($options = 0) {

        return array();

    }

    /* {{{ IteratorAggregator interface */

    /**
     * Returns the iterator for this object
     *
     * @return ElementList
     */
    public function getIterator() {

        if (!is_null($this->iterator))
            return $this->iterator;

        return new ElementList(array($this));

    }

    /**
     * Sets the overridden iterator
     *
     * Note that this is not actually part of the iterator interface
     *
     * @param ElementList $iterator
     * @return void
     */
    public function setIterator(ElementList $iterator) {

        $this->iterator = $iterator;

    }

    /* }}} */

    /* {{{ Countable interface */

    /**
     * Returns the number of elements
     *
     * @return int
     */
    public function count() {

        $it = $this->getIterator();
        return $it->count();

    }

    /* }}} */

    /* {{{ ArrayAccess Interface */


    /**
     * Checks if an item exists through ArrayAccess.
     *
     * This method just forwards the request to the inner iterator
     *
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset) {

        $iterator = $this->getIterator();
        return $iterator->offsetExists($offset);

    }

    /**
     * Gets an item through ArrayAccess.
     *
     * This method just forwards the request to the inner iterator
     *
     * @param int $offset
     * @return mixed
     */
    public function offsetGet($offset) {

        $iterator = $this->getIterator();
        return $iterator->offsetGet($offset);

    }

    /**
     * Sets an item through ArrayAccess.
     *
     * This method just forwards the request to the inner iterator
     *
     * @param int $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset,$value) {

        $iterator = $this->getIterator();
        $iterator->offsetSet($offset,$value);

    // @codeCoverageIgnoreStart
    //
    // This method always throws an exception, so we ignore the closing
    // brace
    }
    // @codeCoverageIgnoreEnd

    /**
     * Sets an item through ArrayAccess.
     *
     * This method just forwards the request to the inner iterator
     *
     * @param int $offset
     * @return void
     */
    public function offsetUnset($offset) {

        $iterator = $this->getIterator();
        $iterator->offsetUnset($offset);

    // @codeCoverageIgnoreStart
    //
    // This method always throws an exception, so we ignore the closing
    // brace
    }
    // @codeCoverageIgnoreEnd

    /* }}} */

}
