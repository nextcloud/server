<?php

namespace Sabre\VObject\Component;

use Sabre\VObject;

/**
 * The Available sub-component.
 *
 * This component adds functionality to a component, specific for AVAILABLE
 * components.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Ivan Enderlin
 * @license http://sabre.io/license/ Modified BSD License
 */
class Available extends VObject\Component
{
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
        $effectiveStart = $this->DTSTART->getDateTime();
        if (isset($this->DTEND)) {
            $effectiveEnd = $this->DTEND->getDateTime();
        } else {
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
            'DTSTART' => 1,
            'DTSTAMP' => 1,

            'DTEND' => '?',
            'DURATION' => '?',

            'CREATED' => '?',
            'DESCRIPTION' => '?',
            'LAST-MODIFIED' => '?',
            'RECURRENCE-ID' => '?',
            'RRULE' => '?',
            'SUMMARY' => '?',

            'CATEGORIES' => '*',
            'COMMENT' => '*',
            'CONTACT' => '*',
            'EXDATE' => '*',
            'RDATE' => '*',

            'AVAILABLE' => '*',
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
