<?php

declare(strict_types=1);

namespace Sabre\CalDAV;

use DateTime;
use Sabre\VObject;

/**
 * CalendarQuery Validator.
 *
 * This class is responsible for checking if an iCalendar object matches a set
 * of filters. The main function to do this is 'validate'.
 *
 * This is used to determine which icalendar objects should be returned for a
 * calendar-query REPORT request.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class CalendarQueryValidator
{
    /**
     * Verify if a list of filters applies to the calendar data object.
     *
     * The list of filters must be formatted as parsed by \Sabre\CalDAV\CalendarQueryParser
     *
     * @return bool
     */
    public function validate(VObject\Component\VCalendar $vObject, array $filters)
    {
        // The top level object is always a component filter.
        // We'll parse it manually, as it's pretty simple.
        if ($vObject->name !== $filters['name']) {
            return false;
        }

        return
            $this->validateCompFilters($vObject, $filters['comp-filters']) &&
            $this->validatePropFilters($vObject, $filters['prop-filters']);
    }

    /**
     * This method checks the validity of comp-filters.
     *
     * A list of comp-filters needs to be specified. Also the parent of the
     * component we're checking should be specified, not the component to check
     * itself.
     *
     * @return bool
     */
    protected function validateCompFilters(VObject\Component $parent, array $filters)
    {
        foreach ($filters as $filter) {
            $isDefined = isset($parent->{$filter['name']});

            if ($filter['is-not-defined']) {
                if ($isDefined) {
                    return false;
                } else {
                    continue;
                }
            }
            if (!$isDefined) {
                return false;
            }

            if (array_key_exists('time-range', $filter) && $filter['time-range']) {
                foreach ($parent->{$filter['name']} as $subComponent) {
                    $start = null;
                    $end = null;
                    if (array_key_exists('start', $filter['time-range'])) {
                        $start = $filter['time-range']['start'];
                    }
                    if (array_key_exists('end', $filter['time-range'])) {
                        $end = $filter['time-range']['end'];
                    }
                    if ($this->validateTimeRange($subComponent, $start, $end)) {
                        continue 2;
                    }
                }

                return false;
            }

            if (!$filter['comp-filters'] && !$filter['prop-filters']) {
                continue;
            }

            // If there are sub-filters, we need to find at least one component
            // for which the subfilters hold true.
            foreach ($parent->{$filter['name']} as $subComponent) {
                if (
                    $this->validateCompFilters($subComponent, $filter['comp-filters']) &&
                    $this->validatePropFilters($subComponent, $filter['prop-filters'])) {
                    // We had a match, so this comp-filter succeeds
                    continue 2;
                }
            }

            // If we got here it means there were sub-comp-filters or
            // sub-prop-filters and there was no match. This means this filter
            // needs to return false.
            return false;
        }

        // If we got here it means we got through all comp-filters alive so the
        // filters were all true.
        return true;
    }

    /**
     * This method checks the validity of prop-filters.
     *
     * A list of prop-filters needs to be specified. Also the parent of the
     * property we're checking should be specified, not the property to check
     * itself.
     *
     * @return bool
     */
    protected function validatePropFilters(VObject\Component $parent, array $filters)
    {
        foreach ($filters as $filter) {
            $isDefined = isset($parent->{$filter['name']});

            if ($filter['is-not-defined']) {
                if ($isDefined) {
                    return false;
                } else {
                    continue;
                }
            }
            if (!$isDefined) {
                return false;
            }

            if (array_key_exists('time-range', $filter) && $filter['time-range']) {
                foreach ($parent->{$filter['name']} as $subComponent) {
                    $start = null;
                    $end = null;
                    if (array_key_exists('start', $filter['time-range'])) {
                        $start = $filter['time-range']['start'];
                    }
                    if (array_key_exists('end', $filter['time-range'])) {
                        $end = $filter['time-range']['end'];
                    }
                    if ($this->validateTimeRange($subComponent, $start, $end)) {
                        continue 2;
                    }
                }

                return false;
            }

            if (!$filter['param-filters'] && !$filter['text-match']) {
                continue;
            }

            // If there are sub-filters, we need to find at least one property
            // for which the subfilters hold true.
            foreach ($parent->{$filter['name']} as $subComponent) {
                if (
                    $this->validateParamFilters($subComponent, $filter['param-filters']) &&
                    (!$filter['text-match'] || $this->validateTextMatch($subComponent, $filter['text-match']))
                ) {
                    // We had a match, so this prop-filter succeeds
                    continue 2;
                }
            }

            // If we got here it means there were sub-param-filters or
            // text-match filters and there was no match. This means the
            // filter needs to return false.
            return false;
        }

        // If we got here it means we got through all prop-filters alive so the
        // filters were all true.
        return true;
    }

    /**
     * This method checks the validity of param-filters.
     *
     * A list of param-filters needs to be specified. Also the parent of the
     * parameter we're checking should be specified, not the parameter to check
     * itself.
     *
     * @return bool
     */
    protected function validateParamFilters(VObject\Property $parent, array $filters)
    {
        foreach ($filters as $filter) {
            $isDefined = isset($parent[$filter['name']]);

            if ($filter['is-not-defined']) {
                if ($isDefined) {
                    return false;
                } else {
                    continue;
                }
            }
            if (!$isDefined) {
                return false;
            }

            if (!$filter['text-match']) {
                continue;
            }

            // If there are sub-filters, we need to find at least one parameter
            // for which the subfilters hold true.
            foreach ($parent[$filter['name']]->getParts() as $paramPart) {
                if ($this->validateTextMatch($paramPart, $filter['text-match'])) {
                    // We had a match, so this param-filter succeeds
                    continue 2;
                }
            }

            // If we got here it means there was a text-match filter and there
            // were no matches. This means the filter needs to return false.
            return false;
        }

        // If we got here it means we got through all param-filters alive so the
        // filters were all true.
        return true;
    }

    /**
     * This method checks the validity of a text-match.
     *
     * A single text-match should be specified as well as the specific property
     * or parameter we need to validate.
     *
     * @param VObject\Node|string $check value to check against
     *
     * @return bool
     */
    protected function validateTextMatch($check, array $textMatch)
    {
        if ($check instanceof VObject\Node) {
            $check = $check->getValue();
        }

        $isMatching = \Sabre\DAV\StringUtil::textMatch($check, $textMatch['value'], $textMatch['collation']);

        return $textMatch['negate-condition'] xor $isMatching;
    }

    /**
     * Validates if a component matches the given time range.
     *
     * This is all based on the rules specified in rfc4791, which are quite
     * complex.
     *
     * @param DateTime $start
     * @param DateTime $end
     *
     * @return bool
     */
    protected function validateTimeRange(VObject\Node $component, $start, $end)
    {
        if (is_null($start)) {
            $start = new DateTime('1900-01-01');
        }
        if (is_null($end)) {
            $end = new DateTime('3000-01-01');
        }

        switch ($component->name) {
            case 'VEVENT':
            case 'VTODO':
            case 'VJOURNAL':
                return $component->isInTimeRange($start, $end);

            case 'VALARM':
                // If the valarm is wrapped in a recurring event, we need to
                // expand the recursions, and validate each.
                //
                // Our datamodel doesn't easily allow us to do this straight
                // in the VALARM component code, so this is a hack, and an
                // expensive one too.
                if ('VEVENT' === $component->parent->name && $component->parent->RRULE) {
                    // Fire up the iterator!
                    $it = new VObject\Recur\EventIterator($component->parent->parent, (string) $component->parent->UID);
                    while ($it->valid()) {
                        $expandedEvent = $it->getEventObject();

                        // We need to check from these expanded alarms, which
                        // one is the first to trigger. Based on this, we can
                        // determine if we can 'give up' expanding events.
                        $firstAlarm = null;
                        if (null !== $expandedEvent->VALARM) {
                            foreach ($expandedEvent->VALARM as $expandedAlarm) {
                                $effectiveTrigger = $expandedAlarm->getEffectiveTriggerTime();
                                if ($expandedAlarm->isInTimeRange($start, $end)) {
                                    return true;
                                }

                                if ('DATE-TIME' === (string) $expandedAlarm->TRIGGER['VALUE']) {
                                    // This is an alarm with a non-relative trigger
                                    // time, likely created by a buggy client. The
                                    // implication is that every alarm in this
                                    // recurring event trigger at the exact same
                                    // time. It doesn't make sense to traverse
                                    // further.
                                } else {
                                    // We store the first alarm as a means to
                                    // figure out when we can stop traversing.
                                    if (!$firstAlarm || $effectiveTrigger < $firstAlarm) {
                                        $firstAlarm = $effectiveTrigger;
                                    }
                                }
                            }
                        }
                        if (is_null($firstAlarm)) {
                            // No alarm was found.
                            //
                            // Or technically: No alarm that will change for
                            // every instance of the recurrence was found,
                            // which means we can assume there was no match.
                            return false;
                        }
                        if ($firstAlarm > $end) {
                            return false;
                        }
                        $it->next();
                    }

                    return false;
                } else {
                    return $component->isInTimeRange($start, $end);
                }

                // no break
            case 'VFREEBUSY':
                throw new \Sabre\DAV\Exception\NotImplemented('time-range filters are currently not supported on '.$component->name.' components');
            case 'COMPLETED':
            case 'CREATED':
            case 'DTEND':
            case 'DTSTAMP':
            case 'DTSTART':
            case 'DUE':
            case 'LAST-MODIFIED':
                return $start <= $component->getDateTime() && $end >= $component->getDateTime();

            default:
                throw new \Sabre\DAV\Exception\BadRequest('You cannot create a time-range filter on a '.$component->name.' component');
        }
    }
}
