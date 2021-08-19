<?php

namespace Sabre\VObject;

use Sabre\Xml;

/**
 * A node is the root class for every element in an iCalendar of vCard object.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class Node implements \IteratorAggregate, \ArrayAccess, \Countable, \JsonSerializable, Xml\XmlSerializable
{
    /**
     * The following constants are used by the validate() method.
     *
     * If REPAIR is set, the validator will attempt to repair any broken data
     * (if possible).
     */
    const REPAIR = 1;

    /**
     * If this option is set, the validator will operate on the vcards on the
     * assumption that the vcards need to be valid for CardDAV.
     *
     * This means for example that the UID is required, whereas it is not for
     * regular vcards.
     */
    const PROFILE_CARDDAV = 2;

    /**
     * If this option is set, the validator will operate on iCalendar objects
     * on the assumption that the vcards need to be valid for CalDAV.
     *
     * This means for example that calendars can only contain objects with
     * identical component types and UIDs.
     */
    const PROFILE_CALDAV = 4;

    /**
     * Reference to the parent object, if this is not the top object.
     *
     * @var Node
     */
    public $parent;

    /**
     * Iterator override.
     *
     * @var ElementList
     */
    protected $iterator = null;

    /**
     * The root document.
     *
     * @var Component
     */
    protected $root;

    /**
     * Serializes the node into a mimedir format.
     *
     * @return string
     */
    abstract public function serialize();

    /**
     * This method returns an array, with the representation as it should be
     * encoded in JSON. This is used to create jCard or jCal documents.
     *
     * @return array
     */
    abstract public function jsonSerialize();

    /**
     * This method serializes the data into XML. This is used to create xCard or
     * xCal documents.
     *
     * @param Xml\Writer $writer XML writer
     */
    abstract public function xmlSerialize(Xml\Writer $writer);

    /**
     * Call this method on a document if you're done using it.
     *
     * It's intended to remove all circular references, so PHP can easily clean
     * it up.
     */
    public function destroy()
    {
        $this->parent = null;
        $this->root = null;
    }

    /* {{{ IteratorAggregator interface */

    /**
     * Returns the iterator for this object.
     *
     * @return ElementList
     */
    public function getIterator()
    {
        if (!is_null($this->iterator)) {
            return $this->iterator;
        }

        return new ElementList([$this]);
    }

    /**
     * Sets the overridden iterator.
     *
     * Note that this is not actually part of the iterator interface
     */
    public function setIterator(ElementList $iterator)
    {
        $this->iterator = $iterator;
    }

    /**
     * Validates the node for correctness.
     *
     * The following options are supported:
     *   Node::REPAIR - May attempt to automatically repair the problem.
     *
     * This method returns an array with detected problems.
     * Every element has the following properties:
     *
     *  * level - problem level.
     *  * message - A human-readable string describing the issue.
     *  * node - A reference to the problematic node.
     *
     * The level means:
     *   1 - The issue was repaired (only happens if REPAIR was turned on)
     *   2 - An inconsequential issue
     *   3 - A severe issue.
     *
     * @param int $options
     *
     * @return array
     */
    public function validate($options = 0)
    {
        return [];
    }

    /* }}} */

    /* {{{ Countable interface */

    /**
     * Returns the number of elements.
     *
     * @return int
     */
    public function count()
    {
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
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        $iterator = $this->getIterator();

        return $iterator->offsetExists($offset);
    }

    /**
     * Gets an item through ArrayAccess.
     *
     * This method just forwards the request to the inner iterator
     *
     * @param int $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $iterator = $this->getIterator();

        return $iterator->offsetGet($offset);
    }

    /**
     * Sets an item through ArrayAccess.
     *
     * This method just forwards the request to the inner iterator
     *
     * @param int   $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $iterator = $this->getIterator();
        $iterator->offsetSet($offset, $value);

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
     */
    public function offsetUnset($offset)
    {
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
