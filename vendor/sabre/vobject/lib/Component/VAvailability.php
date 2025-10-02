<?php

namespace Sabre\VObject\Component;

use DateTimeInterface;
use Sabre\VObject;

/**
 * The VAvailability component.
 *
 * This component adds functionality to a component, specific for VAVAILABILITY
 * components.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Ivan Enderlin
 * @license http://sabre.io/license/ Modified BSD License
 */
class VAvailability extends VObject\Component
{
    /**
     * Returns true or false depending on if the event falls in the specified
     * time-range. This is used for filtering purposes.
     *
     * The rules used to determine if an event falls within the specified
     * time-range is based on:
     *
     * https://tools.ietf.org/html/draft-daboo-calendar-availability-05#section-3.1
     *
     * @return bool
     */
    public function isInTimeRange(DateTimeInterface $start, DateTimeInterface $end)
    {
        list($effectiveStart, $effectiveEnd) = $this->getEffectiveStartEnd();

        return
            (is_null($effectiveStart) || $start < $effectiveEnd) &&
            (is_null($effectiveEnd) || $end > $effectiveStart)
        ;
    }

    /**
     * Returns the 'effective start' and 'effective end' of this VAVAILABILITY
     * component.
     *
     * We use the DTSTART and DTEND or DURATION to determine this.
     *
     * The returned value is an array containing DateTimeImmutable instances.
     * If either the start or end is 'unbounded' its value will be null
     * instead.
     *
     * @return array
     */
    public function getEffectiveStartEnd()
    {
        $effectiveStart = null;
        $effectiveEnd = null;

        if (isset($this->DTSTART)) {
            $effectiveStart = $this->DTSTART->getDateTime();
        }
        if (isset($this->DTEND)) {
            $effectiveEnd = $this->DTEND->getDateTime();
        } elseif ($effectiveStart && isset($this->DURATION)) {
            $effectiveEnd = $effectiveStart->add(VObject\DateTimeParser::parseDuration($this->DURATION));
        }

        return [$effectiveStart, $effectiveEnd];
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
        return [
            'UID' => 1,
            'DTSTAMP' => 1,

            'BUSYTYPE' => '?',
            'CLASS' => '?',
            'CREATED' => '?',
            'DESCRIPTION' => '?',
            'DTSTART' => '?',
            'LAST-MODIFIED' => '?',
            'ORGANIZER' => '?',
            'PRIORITY' => '?',
            'SEQUENCE' => '?',
            'SUMMARY' => '?',
            'URL' => '?',
            'DTEND' => '?',
            'DURATION' => '?',

            'CATEGORIES' => '*',
            'COMMENT' => '*',
            'CONTACT' => '*',
        ];
    }

    /**
     * Validates the node for correctness.
     *
     * The following options are supported:
     *   Node::REPAIR - May attempt to automatically repair the problem.
     *   Node::PROFILE_CARDDAV - Validate the vCard for CardDAV purposes.
     *   Node::PROFILE_CALDAV - Validate the iCalendar for CalDAV purposes.
     *
     * This method returns an array with detected problems.
     * Every element has the following properties:
     *
     *  * level - problem level.
     *  * message - A human-readable string describing the issue.
     *  * node - A reference to the problematic node.
     *
     * The level means:
     *   1 - The issue was repaired (only happens if REPAIR was turned on).
     *   2 - A warning.
     *   3 - An error.
     *
     * @param int $options
     *
     * @return array
     */
    public function validate($options = 0)
    {
        $result = parent::validate($options);

        if (isset($this->DTEND) && isset($this->DURATION)) {
            $result[] = [
                'level' => 3,
                'message' => 'DTEND and DURATION cannot both be present',
                'node' => $this,
            ];
        }

        return $result;
    }
}
