<?php

namespace Sabre\VObject\Component;

use DateTimeInterface;
use DateTimeZone;
use Sabre\VObject;
use Sabre\VObject\Component;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\Property;
use Sabre\VObject\Recur\EventIterator;
use Sabre\VObject\Recur\NoInstancesException;

/**
 * The VCalendar component.
 *
 * This component adds functionality to a component, specific for a VCALENDAR.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class VCalendar extends VObject\Document
{
    /**
     * The default name for this component.
     *
     * This should be 'VCALENDAR' or 'VCARD'.
     *
     * @var string
     */
    public static $defaultName = 'VCALENDAR';

    /**
     * This is a list of components, and which classes they should map to.
     *
     * @var array
     */
    public static $componentMap = [
        'VCALENDAR' => self::class,
        'VALARM' => VAlarm::class,
        'VEVENT' => VEvent::class,
        'VFREEBUSY' => VFreeBusy::class,
        'VAVAILABILITY' => VAvailability::class,
        'AVAILABLE' => Available::class,
        'VJOURNAL' => VJournal::class,
        'VTIMEZONE' => VTimeZone::class,
        'VTODO' => VTodo::class,
    ];

    /**
     * List of value-types, and which classes they map to.
     *
     * @var array
     */
    public static $valueMap = [
        'BINARY' => VObject\Property\Binary::class,
        'BOOLEAN' => VObject\Property\Boolean::class,
        'CAL-ADDRESS' => VObject\Property\ICalendar\CalAddress::class,
        'DATE' => VObject\Property\ICalendar\Date::class,
        'DATE-TIME' => VObject\Property\ICalendar\DateTime::class,
        'DURATION' => VObject\Property\ICalendar\Duration::class,
        'FLOAT' => VObject\Property\FloatValue::class,
        'INTEGER' => VObject\Property\IntegerValue::class,
        'PERIOD' => VObject\Property\ICalendar\Period::class,
        'RECUR' => VObject\Property\ICalendar\Recur::class,
        'TEXT' => VObject\Property\Text::class,
        'TIME' => VObject\Property\Time::class,
        'UNKNOWN' => VObject\Property\Unknown::class, // jCard / jCal-only.
        'URI' => VObject\Property\Uri::class,
        'UTC-OFFSET' => VObject\Property\UtcOffset::class,
    ];

    /**
     * List of properties, and which classes they map to.
     *
     * @var array
     */
    public static $propertyMap = [
        // Calendar properties
        'CALSCALE' => VObject\Property\FlatText::class,
        'METHOD' => VObject\Property\FlatText::class,
        'PRODID' => VObject\Property\FlatText::class,
        'VERSION' => VObject\Property\FlatText::class,

        // Component properties
        'ATTACH' => VObject\Property\Uri::class,
        'CATEGORIES' => VObject\Property\Text::class,
        'CLASS' => VObject\Property\FlatText::class,
        'COMMENT' => VObject\Property\FlatText::class,
        'DESCRIPTION' => VObject\Property\FlatText::class,
        'GEO' => VObject\Property\FloatValue::class,
        'LOCATION' => VObject\Property\FlatText::class,
        'PERCENT-COMPLETE' => VObject\Property\IntegerValue::class,
        'PRIORITY' => VObject\Property\IntegerValue::class,
        'RESOURCES' => VObject\Property\Text::class,
        'STATUS' => VObject\Property\FlatText::class,
        'SUMMARY' => VObject\Property\FlatText::class,

        // Date and Time Component Properties
        'COMPLETED' => VObject\Property\ICalendar\DateTime::class,
        'DTEND' => VObject\Property\ICalendar\DateTime::class,
        'DUE' => VObject\Property\ICalendar\DateTime::class,
        'DTSTART' => VObject\Property\ICalendar\DateTime::class,
        'DURATION' => VObject\Property\ICalendar\Duration::class,
        'FREEBUSY' => VObject\Property\ICalendar\Period::class,
        'TRANSP' => VObject\Property\FlatText::class,

        // Time Zone Component Properties
        'TZID' => VObject\Property\FlatText::class,
        'TZNAME' => VObject\Property\FlatText::class,
        'TZOFFSETFROM' => VObject\Property\UtcOffset::class,
        'TZOFFSETTO' => VObject\Property\UtcOffset::class,
        'TZURL' => VObject\Property\Uri::class,

        // Relationship Component Properties
        'ATTENDEE' => VObject\Property\ICalendar\CalAddress::class,
        'CONTACT' => VObject\Property\FlatText::class,
        'ORGANIZER' => VObject\Property\ICalendar\CalAddress::class,
        'RECURRENCE-ID' => VObject\Property\ICalendar\DateTime::class,
        'RELATED-TO' => VObject\Property\FlatText::class,
        'URL' => VObject\Property\Uri::class,
        'UID' => VObject\Property\FlatText::class,

        // Recurrence Component Properties
        'EXDATE' => VObject\Property\ICalendar\DateTime::class,
        'RDATE' => VObject\Property\ICalendar\DateTime::class,
        'RRULE' => VObject\Property\ICalendar\Recur::class,
        'EXRULE' => VObject\Property\ICalendar\Recur::class, // Deprecated since rfc5545

        // Alarm Component Properties
        'ACTION' => VObject\Property\FlatText::class,
        'REPEAT' => VObject\Property\IntegerValue::class,
        'TRIGGER' => VObject\Property\ICalendar\Duration::class,

        // Change Management Component Properties
        'CREATED' => VObject\Property\ICalendar\DateTime::class,
        'DTSTAMP' => VObject\Property\ICalendar\DateTime::class,
        'LAST-MODIFIED' => VObject\Property\ICalendar\DateTime::class,
        'SEQUENCE' => VObject\Property\IntegerValue::class,

        // Request Status
        'REQUEST-STATUS' => VObject\Property\Text::class,

        // Additions from draft-daboo-valarm-extensions-04
        'ALARM-AGENT' => VObject\Property\Text::class,
        'ACKNOWLEDGED' => VObject\Property\ICalendar\DateTime::class,
        'PROXIMITY' => VObject\Property\Text::class,
        'DEFAULT-ALARM' => VObject\Property\Boolean::class,

        // Additions from draft-daboo-calendar-availability-05
        'BUSYTYPE' => VObject\Property\Text::class,
    ];

    /**
     * Returns the current document type.
     *
     * @return int
     */
    public function getDocumentType()
    {
        return self::ICALENDAR20;
    }

    /**
     * Returns a list of all 'base components'. For instance, if an Event has
     * a recurrence rule, and one instance is overridden, the overridden event
     * will have the same UID, but will be excluded from this list.
     *
     * VTIMEZONE components will always be excluded.
     *
     * @param string $componentName filter by component name
     *
     * @return VObject\Component[]
     */
    public function getBaseComponents($componentName = null)
    {
        $isBaseComponent = function ($component) {
            if (!$component instanceof VObject\Component) {
                return false;
            }
            if ('VTIMEZONE' === $component->name) {
                return false;
            }
            if (isset($component->{'RECURRENCE-ID'})) {
                return false;
            }

            return true;
        };

        if ($componentName) {
            // Early exit
            return array_filter(
                $this->select($componentName),
                $isBaseComponent
            );
        }

        $components = [];
        foreach ($this->children as $childGroup) {
            foreach ($childGroup as $child) {
                if (!$child instanceof Component) {
                    // If one child is not a component, they all are so we skip
                    // the entire group.
                    continue 2;
                }
                if ($isBaseComponent($child)) {
                    $components[] = $child;
                }
            }
        }

        return $components;
    }

    /**
     * Returns the first component that is not a VTIMEZONE, and does not have
     * an RECURRENCE-ID.
     *
     * If there is no such component, null will be returned.
     *
     * @param string $componentName filter by component name
     *
     * @return VObject\Component|null
     */
    public function getBaseComponent($componentName = null)
    {
        $isBaseComponent = function ($component) {
            if (!$component instanceof VObject\Component) {
                return false;
            }
            if ('VTIMEZONE' === $component->name) {
                return false;
            }
            if (isset($component->{'RECURRENCE-ID'})) {
                return false;
            }

            return true;
        };

        if ($componentName) {
            foreach ($this->select($componentName) as $child) {
                if ($isBaseComponent($child)) {
                    return $child;
                }
            }

            return null;
        }

        // Searching all components
        foreach ($this->children as $childGroup) {
            foreach ($childGroup as $child) {
                if ($isBaseComponent($child)) {
                    return $child;
                }
            }
        }

        return null;
    }

    /**
     * Expand all events in this VCalendar object and return a new VCalendar
     * with the expanded events.
     *
     * If this calendar object, has events with recurrence rules, this method
     * can be used to expand the event into multiple sub-events.
     *
     * Each event will be stripped from its recurrence information, and only
     * the instances of the event in the specified timerange will be left
     * alone.
     *
     * In addition, this method will cause timezone information to be stripped,
     * and normalized to UTC.
     *
     * @param DateTimeZone $timeZone reference timezone for floating dates and
     *                               times
     *
     * @return VCalendar
     */
    public function expand(DateTimeInterface $start, DateTimeInterface $end, DateTimeZone $timeZone = null)
    {
        $newChildren = [];
        $recurringEvents = [];

        if (!$timeZone) {
            $timeZone = new DateTimeZone('UTC');
        }

        $stripTimezones = function (Component $component) use ($timeZone, &$stripTimezones) {
            foreach ($component->children() as $componentChild) {
                if ($componentChild instanceof Property\ICalendar\DateTime && $componentChild->hasTime()) {
                    $dt = $componentChild->getDateTimes($timeZone);
                    // We only need to update the first timezone, because
                    // setDateTimes will match all other timezones to the
                    // first.
                    $dt[0] = $dt[0]->setTimeZone(new DateTimeZone('UTC'));
                    $componentChild->setDateTimes($dt);
                } elseif ($componentChild instanceof Component) {
                    $stripTimezones($componentChild);
                }
            }

            return $component;
        };

        foreach ($this->children() as $child) {
            if ($child instanceof Property && 'PRODID' !== $child->name) {
                // We explicitly want to ignore PRODID, because we want to
                // overwrite it with our own.
                $newChildren[] = clone $child;
            } elseif ($child instanceof Component && 'VTIMEZONE' !== $child->name) {
                // We're also stripping all VTIMEZONE objects because we're
                // converting everything to UTC.
                if ('VEVENT' === $child->name && (isset($child->{'RECURRENCE-ID'}) || isset($child->RRULE) || isset($child->RDATE))) {
                    // Handle these a bit later.
                    $uid = (string) $child->UID;
                    if (!$uid) {
                        throw new InvalidDataException('Every VEVENT object must have a UID property');
                    }
                    if (isset($recurringEvents[$uid])) {
                        $recurringEvents[$uid][] = clone $child;
                    } else {
                        $recurringEvents[$uid] = [clone $child];
                    }
                } elseif ('VEVENT' === $child->name && $child->isInTimeRange($start, $end)) {
                    $newChildren[] = $stripTimezones(clone $child);
                }
            }
        }

        foreach ($recurringEvents as $events) {
            try {
                $it = new EventIterator($events, null, $timeZone);
            } catch (NoInstancesException $e) {
                // This event is recurring, but it doesn't have a single
                // instance. We are skipping this event from the output
                // entirely.
                continue;
            }
            $it->fastForward($start);

            while ($it->valid() && $it->getDTStart() < $end) {
                if ($it->getDTEnd() > $start) {
                    $newChildren[] = $stripTimezones($it->getEventObject());
                }
                $it->next();
            }
        }

        return new self($newChildren);
    }

    /**
     * This method should return a list of default property values.
     *
     * @return array
     */
    protected function getDefaults()
    {
        return [
            'VERSION' => '2.0',
            'PRODID' => '-//Sabre//Sabre VObject '.VObject\Version::VERSION.'//EN',
            'CALSCALE' => 'GREGORIAN',
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
        return [
            'PRODID' => 1,
            'VERSION' => 1,

            'CALSCALE' => '?',
            'METHOD' => '?',
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
        $warnings = parent::validate($options);

        if ($ver = $this->VERSION) {
            if ('2.0' !== (string) $ver) {
                $warnings[] = [
                    'level' => 3,
                    'message' => 'Only iCalendar version 2.0 as defined in rfc5545 is supported.',
                    'node' => $this,
                ];
            }
        }

        $uidList = [];
        $componentsFound = 0;
        $componentTypes = [];

        foreach ($this->children() as $child) {
            if ($child instanceof Component) {
                ++$componentsFound;

                if (!in_array($child->name, ['VEVENT', 'VTODO', 'VJOURNAL'])) {
                    continue;
                }
                $componentTypes[] = $child->name;

                $uid = (string) $child->UID;
                $isMaster = isset($child->{'RECURRENCE-ID'}) ? 0 : 1;
                if (isset($uidList[$uid])) {
                    ++$uidList[$uid]['count'];
                    if ($isMaster && $uidList[$uid]['hasMaster']) {
                        $warnings[] = [
                            'level' => 3,
                            'message' => 'More than one master object was found for the object with UID '.$uid,
                            'node' => $this,
                        ];
                    }
                    $uidList[$uid]['hasMaster'] += $isMaster;
                } else {
                    $uidList[$uid] = [
                        'count' => 1,
                        'hasMaster' => $isMaster,
                    ];
                }
            }
        }

        if (0 === $componentsFound) {
            $warnings[] = [
                'level' => 3,
                'message' => 'An iCalendar object must have at least 1 component.',
                'node' => $this,
            ];
        }

        if ($options & self::PROFILE_CALDAV) {
            if (count($uidList) > 1) {
                $warnings[] = [
                    'level' => 3,
                    'message' => 'A calendar object on a CalDAV server may only have components with the same UID.',
                    'node' => $this,
                ];
            }
            if (0 === count($componentTypes)) {
                $warnings[] = [
                    'level' => 3,
                    'message' => 'A calendar object on a CalDAV server must have at least 1 component (VTODO, VEVENT, VJOURNAL).',
                    'node' => $this,
                ];
            }
            if (count(array_unique($componentTypes)) > 1) {
                $warnings[] = [
                    'level' => 3,
                    'message' => 'A calendar object on a CalDAV server may only have 1 type of component (VEVENT, VTODO or VJOURNAL).',
                    'node' => $this,
                ];
            }

            if (isset($this->METHOD)) {
                $warnings[] = [
                    'level' => 3,
                    'message' => 'A calendar object on a CalDAV server MUST NOT have a METHOD property.',
                    'node' => $this,
                ];
            }
        }

        return $warnings;
    }

    /**
     * Returns all components with a specific UID value.
     *
     * @return array
     */
    public function getByUID($uid)
    {
        return array_filter($this->getComponents(), function ($item) use ($uid) {
            if (!$itemUid = $item->select('UID')) {
                return false;
            }
            $itemUid = current($itemUid)->getValue();

            return $uid === $itemUid;
        });
    }
}
