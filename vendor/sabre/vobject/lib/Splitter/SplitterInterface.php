<?php

namespace Sabre\VObject\Splitter;

/**
 * VObject splitter.
 *
 * The splitter is responsible for reading a large vCard or iCalendar object,
 * and splitting it into multiple objects.
 *
 * This is for example for Card and CalDAV, which require every event and vcard
 * to exist in their own objects, instead of one large one.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Dominik Tobschall (http://tobschall.de/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface SplitterInterface
{
    /**
     * Constructor.
     *
     * The splitter should receive an readable file stream as its input.
     *
     * @param resource $input
     */
    public function __construct($input);

    /**
     * Every time getNext() is called, a new object will be parsed, until we
     * hit the end of the stream.
     *
     * When the end is reached, null will be returned.
     *
     * @return \Sabre\VObject\Component|null
     */
    public function getNext();
}
