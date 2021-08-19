<?php

namespace Sabre\VObject\Splitter;

use Sabre\VObject;
use Sabre\VObject\Component\VCalendar;

/**
 * Splitter.
 *
 * This class is responsible for splitting up iCalendar objects.
 *
 * This class expects a single VCALENDAR object with one or more
 * calendar-objects inside. Objects with identical UID's will be combined into
 * a single object.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Dominik Tobschall (http://tobschall.de/)
 * @author Armin Hackmann
 * @license http://sabre.io/license/ Modified BSD License
 */
class ICalendar implements SplitterInterface
{
    /**
     * Timezones.
     *
     * @var array
     */
    protected $vtimezones = [];

    /**
     * iCalendar objects.
     *
     * @var array
     */
    protected $objects = [];

    /**
     * Constructor.
     *
     * The splitter should receive an readable file stream as its input.
     *
     * @param resource $input
     * @param int      $options parser options, see the OPTIONS constants
     */
    public function __construct($input, $options = 0)
    {
        $data = VObject\Reader::read($input, $options);

        if (!$data instanceof VObject\Component\VCalendar) {
            throw new VObject\ParseException('Supplied input could not be parsed as VCALENDAR.');
        }

        foreach ($data->children() as $component) {
            if (!$component instanceof VObject\Component) {
                continue;
            }

            // Get all timezones
            if ('VTIMEZONE' === $component->name) {
                $this->vtimezones[(string) $component->TZID] = $component;
                continue;
            }

            // Get component UID for recurring Events search
            if (!$component->UID) {
                $component->UID = sha1(microtime()).'-vobjectimport';
            }
            $uid = (string) $component->UID;

            // Take care of recurring events
            if (!array_key_exists($uid, $this->objects)) {
                $this->objects[$uid] = new VCalendar();
            }

            $this->objects[$uid]->add(clone $component);
        }
    }

    /**
     * Every time getNext() is called, a new object will be parsed, until we
     * hit the end of the stream.
     *
     * When the end is reached, null will be returned.
     *
     * @return \Sabre\VObject\Component|null
     */
    public function getNext()
    {
        if ($object = array_shift($this->objects)) {
            // create our baseobject
            $object->version = '2.0';
            $object->prodid = '-//Sabre//Sabre VObject '.VObject\Version::VERSION.'//EN';
            $object->calscale = 'GREGORIAN';

            // add vtimezone information to obj (if we have it)
            foreach ($this->vtimezones as $vtimezone) {
                $object->add($vtimezone);
            }

            return $object;
        } else {
            return;
        }
    }
}
