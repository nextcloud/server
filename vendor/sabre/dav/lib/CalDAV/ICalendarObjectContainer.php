<?php

declare(strict_types=1);

namespace Sabre\CalDAV;

/**
 * This interface represents a node that may contain calendar objects.
 *
 * This is the shared parent for both the Inbox collection and calendars
 * resources.
 *
 * In most cases you will likely want to look at ICalendar instead of this
 * interface.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface ICalendarObjectContainer extends \Sabre\DAV\ICollection
{
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
     * @return array
     */
    public function calendarQuery(array $filters);
}
