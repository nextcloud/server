<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV;
use Sabre\VObject;

/**
 * Abstract Calendaring backend. Extend this class to create your own backends.
 *
 * Checkout the BackendInterface for all the methods that must be implemented.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class AbstractBackend implements BackendInterface
{
    /**
     * Updates properties for a calendar.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param mixed $calendarId
     */
    public function updateCalendar($calendarId, \Sabre\DAV\PropPatch $propPatch)
    {
    }

    /**
     * Returns a list of calendar objects.
     *
     * This method should work identical to getCalendarObject, but instead
     * return all the calendar objects in the list as an array.
     *
     * If the backend supports this, it may allow for some speed-ups.
     *
     * @param mixed $calendarId
     *
     * @return array
     */
    public function getMultipleCalendarObjects($calendarId, array $uris)
    {
        return array_map(function ($uri) use ($calendarId) {
            return $this->getCalendarObject($calendarId, $uri);
        }, $uris);
    }

    /**
     * Performs a calendar-query on the contents of this calendar.
     *
     * The calendar-query is defined in RFC4791 : CalDAV. Using the
     * calendar-query it is possible for a client to request a specific set of
     * object, based on contents of iCalendar properties, date-ranges and
     * iCalendar component types (VTODO, VEVENT).
     *
     * This method should just return a list of (relative) urls that match this
     * query.
     *
     * The list of filters are specified as an array. The exact array is
     * documented by \Sabre\CalDAV\CalendarQueryParser.
     *
     * Note that it is extremely likely that getCalendarObject for every path
     * returned from this method will be called almost immediately after. You
     * may want to anticipate this to speed up these requests.
     *
     * This method provides a default implementation, which parses *all* the
     * iCalendar objects in the specified calendar.
     *
     * This default may well be good enough for personal use, and calendars
     * that aren't very large. But if you anticipate high usage, big calendars
     * or high loads, you are strongly adviced to optimize certain paths.
     *
     * The best way to do so is override this method and to optimize
     * specifically for 'common filters'.
     *
     * Requests that are extremely common are:
     *   * requests for just VEVENTS
     *   * requests for just VTODO
     *   * requests with a time-range-filter on either VEVENT or VTODO.
     *
     * ..and combinations of these requests. It may not be worth it to try to
     * handle every possible situation and just rely on the (relatively
     * easy to use) CalendarQueryValidator to handle the rest.
     *
     * Note that especially time-range-filters may be difficult to parse. A
     * time-range filter specified on a VEVENT must for instance also handle
     * recurrence rules correctly.
     * A good example of how to interprete all these filters can also simply
     * be found in \Sabre\CalDAV\CalendarQueryFilter. This class is as correct
     * as possible, so it gives you a good idea on what type of stuff you need
     * to think of.
     *
     * @param mixed $calendarId
     *
     * @return array
     */
    public function calendarQuery($calendarId, array $filters)
    {
        $result = [];
        $objects = $this->getCalendarObjects($calendarId);

        foreach ($objects as $object) {
            if ($this->validateFilterForObject($object, $filters)) {
                $result[] = $object['uri'];
            }
        }

        return $result;
    }

    /**
     * This method validates if a filter (as passed to calendarQuery) matches
     * the given object.
     *
     * @return bool
     */
    protected function validateFilterForObject(array $object, array $filters)
    {
        // Unfortunately, setting the 'calendardata' here is optional. If
        // it was excluded, we actually need another call to get this as
        // well.
        if (!isset($object['calendardata'])) {
            $object = $this->getCalendarObject($object['calendarid'], $object['uri']);
        }

        $vObject = VObject\Reader::read($object['calendardata']);

        $validator = new CalDAV\CalendarQueryValidator();
        $result = $validator->validate($vObject, $filters);

        // Destroy circular references so PHP will GC the object.
        $vObject->destroy();

        return $result;
    }

    /**
     * Searches through all of a users calendars and calendar objects to find
     * an object with a specific UID.
     *
     * This method should return the path to this object, relative to the
     * calendar home, so this path usually only contains two parts:
     *
     * calendarpath/objectpath.ics
     *
     * If the uid is not found, return null.
     *
     * This method should only consider * objects that the principal owns, so
     * any calendars owned by other principals that also appear in this
     * collection should be ignored.
     *
     * @param string $principalUri
     * @param string $uid
     *
     * @return string|null
     */
    public function getCalendarObjectByUID($principalUri, $uid)
    {
        // Note: this is a super slow naive implementation of this method. You
        // are highly recommended to optimize it, if your backend allows it.
        foreach ($this->getCalendarsForUser($principalUri) as $calendar) {
            // We must ignore calendars owned by other principals.
            if ($calendar['principaluri'] !== $principalUri) {
                continue;
            }

            // Ignore calendars that are shared.
            if (isset($calendar['{http://sabredav.org/ns}owner-principal']) && $calendar['{http://sabredav.org/ns}owner-principal'] !== $principalUri) {
                continue;
            }

            $results = $this->calendarQuery(
                $calendar['id'],
                [
                    'name' => 'VCALENDAR',
                    'prop-filters' => [],
                    'comp-filters' => [
                        [
                            'name' => 'VEVENT',
                            'is-not-defined' => false,
                            'time-range' => null,
                            'comp-filters' => [],
                            'prop-filters' => [
                                [
                                    'name' => 'UID',
                                    'is-not-defined' => false,
                                    'time-range' => null,
                                    'text-match' => [
                                        'value' => $uid,
                                        'negate-condition' => false,
                                        'collation' => 'i;octet',
                                    ],
                                    'param-filters' => [],
                                ],
                            ],
                        ],
                    ],
                ]
            );
            if ($results) {
                // We have a match
                return $calendar['uri'].'/'.$results[0];
            }
        }
    }
}
