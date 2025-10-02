<?php

namespace Sabre\VObject\Component;

use DateTimeInterface;
use Sabre\VObject;

/**
 * VTodo component.
 *
 * This component contains some additional functionality specific for VTODOs.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class VTodo extends VObject\Component
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
        $dtstart = isset($this->DTSTART) ? $this->DTSTART->getDateTime() : null;
        $duration = isset($this->DURATION) ? VObject\DateTimeParser::parseDuration($this->DURATION) : null;
        $due = isset($this->DUE) ? $this->DUE->getDateTime() : null;
        $completed = isset($this->COMPLETED) ? $this->COMPLETED->getDateTime() : null;
        $created = isset($this->CREATED) ? $this->CREATED->getDateTime() : null;

        if ($dtstart) {
            if ($duration) {
                $effectiveEnd = $dtstart->add($duration);

                return $start <= $effectiveEnd && $end > $dtstart;
            } elseif ($due) {
                return
                    ($start < $due || $start <= $dtstart) &&
                    ($end > $dtstart || $end >= $due);
            } else {
                return $start <= $dtstart && $end > $dtstart;
            }
        }
        if ($due) {
            return $start < $due && $end >= $due;
        }
        if ($completed && $created) {
            return
                ($start <= $created || $start <= $completed) &&
                ($end >= $created || $end >= $completed);
        }
        if ($completed) {
            return $start <= $completed && $end >= $completed;
        }
        if ($created) {
            return $end > $created;
        }

        return true;
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

            'CLASS' => '?',
            'COMPLETED' => '?',
            'CREATED' => '?',
            'DESCRIPTION' => '?',
            'DTSTART' => '?',
            'GEO' => '?',
            'LAST-MODIFIED' => '?',
            'LOCATION' => '?',
            'ORGANIZER' => '?',
            'PERCENT' => '?',
            'PRIORITY' => '?',
            'RECURRENCE-ID' => '?',
            'SEQUENCE' => '?',
            'STATUS' => '?',
            'SUMMARY' => '?',
            'URL' => '?',

            'RRULE' => '?',
            'DUE' => '?',
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
        $result = parent::validate($options);
        if (isset($this->DUE) && isset($this->DTSTART)) {
            $due = $this->DUE;
            $dtStart = $this->DTSTART;

            if ($due->getValueType() !== $dtStart->getValueType()) {
                $result[] = [
                    'level' => 3,
                    'message' => 'The value type (DATE or DATE-TIME) must be identical for DUE and DTSTART',
                    'node' => $due,
                ];
            } elseif ($due->getDateTime() < $dtStart->getDateTime()) {
                $result[] = [
                    'level' => 3,
                    'message' => 'DUE must occur after DTSTART',
                    'node' => $due,
                ];
            }
        }

        return $result;
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
            'DTSTAMP' => date('Ymd\\THis\\Z'),
        ];
    }
}
