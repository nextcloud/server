<?php

namespace Sabre\VObject;

use ArrayIterator;
use LogicException;

/**
 * VObject ElementList.
 *
 * This class represents a list of elements. Lists are the result of queries,
 * such as doing $vcalendar->vevent where there's multiple VEVENT objects.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ElementList extends ArrayIterator
{
    /* {{{ ArrayAccess Interface */

    /**
     * Sets an item through ArrayAccess.
     *
     * @param int   $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        throw new LogicException('You can not add new objects to an ElementList');
    }

    /**
     * Sets an item through ArrayAccess.
     *
     * This method just forwards the request to the inner iterator
     *
     * @param int $offset
     */
    public function offsetUnset($offset)
    {
        throw new LogicException('You can not remove objects from an ElementList');
    }

    /* }}} */
}
