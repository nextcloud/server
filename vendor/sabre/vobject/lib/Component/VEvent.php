<?php

namespace Sabre\VObject\Component;

use DateTimeInterface;
use Sabre\VObject;
use Sabre\VObject\Recur\EventIterator;
use Sabre\VObject\Recur\NoInstancesException;

/**
 * VEvent component.
 *
 * This component contains some additional functionality specific for VEVENT's.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class VEvent extends VObject\Component
{
    /**
     * Returns true or false depending on if the event falls in the specified
     * time-range. This is used for filtering purposes.
     *
     * The rules used to determine if an event falls within the specified
     * time-range is based on the CalDAV specification.
     *
     * @return bool
     */
    public function isInTimeRange(DateTimeInterface $start, DateTimeInterface $end)
    {
        if ($this->RRULE) {
            try {
                $it = new EventIterator($this, null, $start->getTimezone());
            } catch (NoInstancesException $e) {
                // If we've caught this exception, there are no instances
                // for the event that fall into the specified time-range.
                return false;
            }

            $it->fastForward($start);

            // We fast-forwarded to a spot where the end-time of the
            // recurrence instance exceeded the start of the requested
            // time-range.
            //
            // If the starttime of the recurrence did not exceed the
            // end of the time range as well, we have a match.
            return $it->getDTStart() < $end && $it->getDTEnd() > $start;
        }

        $effectiveStart = $this->DTSTART->getDateTime($start->getTimezone());
        if (isset($this->DTEND)) {
            // The DTEND property is considered non inclusive. So for a 3 day
            // event in july, dtstart and dtend would have to be July 1st and
            // July 4th respectively.
            //
            // See:
            // http://tools.ietf.org/html/rfc5545#page-54
            $effectiveEnd = $this->DTEND->getDateTime($end->getTimezone());
        } elseif (isset($this->DURATION)) {
            $effectiveEnd = $effectiveStart->add(VObject\DateTimeParser::parseDuration($this->DURATION));
        } elseif (!$this->DTSTART->hasTime()) {
            $effectiveEnd = $effectiveStart->modify('+1 day');
        } else {
            $effectiveEnd = $effectiveStart;
        }

        return
            ($start < $effectiveEnd) && ($end > $effectiveStart)
        ;
    }

    /**
     * This method should return a list of default property values.
     *
     * @return array
     */
    protected function getDefaults()
    {
        return [
            'UID' => 'sabre-vobject-'.VObject\UUIDUtil::getUUID(),
            'DTSTAMP' => gmdate('Ymd\\THis\\Z'),
        ];
    }

    /**
     * A simple list of validation rules.
     *
     * This is simply a list of properties, and how many times they either
     * must or must not appear.
     *
     * Possible values per property:
     *   * 0 - Must not appear.
     *   * 1 - Must appear exactly once.
     *   * + - Must appear at least once.
     *   * * - Can appear any number of times.
     *   * ? - May appear, but not more than once.
     *
     * @var array
     */
    public function getValidationRules()
    {
        $hasMethod = isset($this->parent->METHOD);

        return [
            'UID' => 1,
            'DTSTAMP' => 1,
            'DTSTART' => $hasMethod ? '?' : '1',
            'CLASS' => '?',
            'CREATED' => '?',
            'DESCRIPTION' => '?',
            'GEO' => '?',
            'LAST-MODIFIED' => '?',
            'LOCATION' => '?',
            'ORGANIZER' => '?',
            'PRIORITY' => '?',
            'SEQUENCE' => '?',
            'STATUS' => '?',
            'SUMMARY' => '?',
            'TRANSP' => '?',
            'URL' => '?',
            'RECURRENCE-ID' => '?',
            'RRULE' => '?',
            'DTEND' => '?',
            'DURATION' => '?',

            'ATTACH' => '*',
            'ATTENDEE' => '*',
            'CATEGORIES' => '*',
            'COMMENT' => '*',
            'CONTACT' => '*',
            'EXDATE' => '*',
            'REQUEST-STATUS' => '*',
            'RELATED-TO' => '*',
            'RESOURCES' => '*',
            'RDATE' => '*',
        ];
    }
}
