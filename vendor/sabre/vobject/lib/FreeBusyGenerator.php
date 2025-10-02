<?php

namespace Sabre\VObject;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Recur\EventIterator;
use Sabre\VObject\Recur\NoInstancesException;

/**
 * This class helps with generating FREEBUSY reports based on existing sets of
 * objects.
 *
 * It only looks at VEVENT and VFREEBUSY objects from the sourcedata, and
 * generates a single VFREEBUSY object.
 *
 * VFREEBUSY components are described in RFC5545, The rules for what should
 * go in a single freebusy report is taken from RFC4791, section 7.10.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class FreeBusyGenerator
{
    /**
     * Input objects.
     *
     * @var array
     */
    protected $objects = [];

    /**
     * Start of range.
     *
     * @var DateTimeInterface|null
     */
    protected $start;

    /**
     * End of range.
     *
     * @var DateTimeInterface|null
     */
    protected $end;

    /**
     * VCALENDAR object.
     *
     * @var Document
     */
    protected $baseObject;

    /**
     * Reference timezone.
     *
     * When we are calculating busy times, and we come across so-called
     * floating times (times without a timezone), we use the reference timezone
     * instead.
     *
     * This is also used for all-day events.
     *
     * This defaults to UTC.
     *
     * @var DateTimeZone
     */
    protected $timeZone;

    /**
     * A VAVAILABILITY document.
     *
     * If this is set, its information will be included when calculating
     * freebusy time.
     *
     * @var Document
     */
    protected $vavailability;

    /**
     * Creates the generator.
     *
     * Check the setTimeRange and setObjects methods for details about the
     * arguments.
     *
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     * @param mixed             $objects
     * @param DateTimeZone      $timeZone
     */
    public function __construct(?DateTimeInterface $start = null, ?DateTimeInterface $end = null, $objects = null, ?DateTimeZone $timeZone = null)
    {
        $this->setTimeRange($start, $end);

        if ($objects) {
            $this->setObjects($objects);
        }
        if (is_null($timeZone)) {
            $timeZone = new DateTimeZone('UTC');
        }
        $this->setTimeZone($timeZone);
    }

    /**
     * Sets the VCALENDAR object.
     *
     * If this is set, it will not be generated for you. You are responsible
     * for setting things like the METHOD, CALSCALE, VERSION, etc..
     *
     * The VFREEBUSY object will be automatically added though.
     */
    public function setBaseObject(Document $vcalendar)
    {
        $this->baseObject = $vcalendar;
    }

    /**
     * Sets a VAVAILABILITY document.
     */
    public function setVAvailability(Document $vcalendar)
    {
        $this->vavailability = $vcalendar;
    }

    /**
     * Sets the input objects.
     *
     * You must either specify a vcalendar object as a string, or as the parse
     * Component.
     * It's also possible to specify multiple objects as an array.
     *
     * @param mixed $objects
     */
    public function setObjects($objects)
    {
        if (!is_array($objects)) {
            $objects = [$objects];
        }

        $this->objects = [];
        foreach ($objects as $object) {
            if (is_string($object) || is_resource($object)) {
                $this->objects[] = Reader::read($object);
            } elseif ($object instanceof Component) {
                $this->objects[] = $object;
            } else {
                throw new \InvalidArgumentException('You can only pass strings or \\Sabre\\VObject\\Component arguments to setObjects');
            }
        }
    }

    /**
     * Sets the time range.
     *
     * Any freebusy object falling outside of this time range will be ignored.
     *
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     */
    public function setTimeRange(?DateTimeInterface $start = null, ?DateTimeInterface $end = null)
    {
        if (!$start) {
            $start = new DateTimeImmutable(Settings::$minDate);
        }
        if (!$end) {
            $end = new DateTimeImmutable(Settings::$maxDate);
        }
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * Sets the reference timezone for floating times.
     */
    public function setTimeZone(DateTimeZone $timeZone)
    {
        $this->timeZone = $timeZone;
    }

    /**
     * Parses the input data and returns a correct VFREEBUSY object, wrapped in
     * a VCALENDAR.
     *
     * @return Component
     */
    public function getResult()
    {
        $fbData = new FreeBusyData(
            $this->start->getTimeStamp(),
            $this->end->getTimeStamp()
        );
        if ($this->vavailability) {
            $this->calculateAvailability($fbData, $this->vavailability);
        }

        $this->calculateBusy($fbData, $this->objects);

        return $this->generateFreeBusyCalendar($fbData);
    }

    /**
     * This method takes a VAVAILABILITY component and figures out all the
     * available times.
     */
    protected function calculateAvailability(FreeBusyData $fbData, VCalendar $vavailability)
    {
        $vavailComps = iterator_to_array($vavailability->VAVAILABILITY);
        usort(
            $vavailComps,
            function ($a, $b) {
                // We need to order the components by priority. Priority 1
                // comes first, up until priority 9. Priority 0 comes after
                // priority 9. No priority implies priority 0.
                //
                // Yes, I'm serious.
                $priorityA = isset($a->PRIORITY) ? (int) $a->PRIORITY->getValue() : 0;
                $priorityB = isset($b->PRIORITY) ? (int) $b->PRIORITY->getValue() : 0;

                if (0 === $priorityA) {
                    $priorityA = 10;
                }
                if (0 === $priorityB) {
                    $priorityB = 10;
                }

                return $priorityA - $priorityB;
            }
        );

        // Now we go over all the VAVAILABILITY components and figure if
        // there's any we don't need to consider.
        //
        // This is can be because of one of two reasons: either the
        // VAVAILABILITY component falls outside the time we are interested in,
        // or a different VAVAILABILITY component with a higher priority has
        // already completely covered the time-range.
        $old = $vavailComps;
        $new = [];

        foreach ($old as $vavail) {
            list($compStart, $compEnd) = $vavail->getEffectiveStartEnd();

            // We don't care about datetimes that are earlier or later than the
            // start and end of the freebusy report, so this gets normalized
            // first.
            if (is_null($compStart) || $compStart < $this->start) {
                $compStart = $this->start;
            }
            if (is_null($compEnd) || $compEnd > $this->end) {
                $compEnd = $this->end;
            }

            // If the item fell out of the timerange, we can just skip it.
            if ($compStart > $this->end || $compEnd < $this->start) {
                continue;
            }

            // Going through our existing list of components to see if there's
            // a higher priority component that already fully covers this one.
            foreach ($new as $higherVavail) {
                list($higherStart, $higherEnd) = $higherVavail->getEffectiveStartEnd();
                if (
                    (is_null($higherStart) || $higherStart < $compStart) &&
                    (is_null($higherEnd) || $higherEnd > $compEnd)
                ) {
                    // Component is fully covered by a higher priority
                    // component. We can skip this component.
                    continue 2;
                }
            }

            // We're keeping it!
            $new[] = $vavail;
        }

        // Lastly, we need to traverse the remaining components and fill in the
        // freebusydata slots.
        //
        // We traverse the components in reverse, because we want the higher
        // priority components to override the lower ones.
        foreach (array_reverse($new) as $vavail) {
            $busyType = isset($vavail->BUSYTYPE) ? strtoupper($vavail->BUSYTYPE) : 'BUSY-UNAVAILABLE';
            list($vavailStart, $vavailEnd) = $vavail->getEffectiveStartEnd();

            // Making the component size no larger than the requested free-busy
            // report range.
            if (!$vavailStart || $vavailStart < $this->start) {
                $vavailStart = $this->start;
            }
            if (!$vavailEnd || $vavailEnd > $this->end) {
                $vavailEnd = $this->end;
            }

            // Marking the entire time range of the VAVAILABILITY component as
            // busy.
            $fbData->add(
                $vavailStart->getTimeStamp(),
                $vavailEnd->getTimeStamp(),
                $busyType
            );

            // Looping over the AVAILABLE components.
            if (isset($vavail->AVAILABLE)) {
                foreach ($vavail->AVAILABLE as $available) {
                    list($availStart, $availEnd) = $available->getEffectiveStartEnd();
                    $fbData->add(
                    $availStart->getTimeStamp(),
                    $availEnd->getTimeStamp(),
                    'FREE'
                );

                    if ($available->RRULE) {
                        // Our favourite thing: recurrence!!

                        $rruleIterator = new Recur\RRuleIterator(
                        $available->RRULE->getValue(),
                        $availStart
                    );
                        $rruleIterator->fastForward($vavailStart);

                        $startEndDiff = $availStart->diff($availEnd);

                        while ($rruleIterator->valid()) {
                            $recurStart = $rruleIterator->current();
                            $recurEnd = $recurStart->add($startEndDiff);

                            if ($recurStart > $vavailEnd) {
                                // We're beyond the legal timerange.
                                break;
                            }

                            if ($recurEnd > $vavailEnd) {
                                // Truncating the end if it exceeds the
                                // VAVAILABILITY end.
                                $recurEnd = $vavailEnd;
                            }

                            $fbData->add(
                            $recurStart->getTimeStamp(),
                            $recurEnd->getTimeStamp(),
                            'FREE'
                        );

                            $rruleIterator->next();
                        }
                    }
                }
            }
        }
    }

    /**
     * This method takes an array of iCalendar objects and applies its busy
     * times on fbData.
     *
     * @param VCalendar[] $objects
     */
    protected function calculateBusy(FreeBusyData $fbData, array $objects)
    {
        foreach ($objects as $key => $object) {
            foreach ($object->getBaseComponents() as $component) {
                switch ($component->name) {
                    case 'VEVENT':
                        $FBTYPE = 'BUSY';
                        if (isset($component->TRANSP) && ('TRANSPARENT' === strtoupper($component->TRANSP))) {
                            break;
                        }
                        if (isset($component->STATUS)) {
                            $status = strtoupper($component->STATUS);
                            if ('CANCELLED' === $status) {
                                break;
                            }
                            if ('TENTATIVE' === $status) {
                                $FBTYPE = 'BUSY-TENTATIVE';
                            }
                        }

                        $times = [];

                        if ($component->RRULE) {
                            try {
                                $iterator = new EventIterator($object, (string) $component->UID, $this->timeZone);
                            } catch (NoInstancesException $e) {
                                // This event is recurring, but it doesn't have a single
                                // instance. We are skipping this event from the output
                                // entirely.
                                unset($this->objects[$key]);
                                break;
                            }

                            if ($this->start) {
                                $iterator->fastForward($this->start);
                            }

                            $maxRecurrences = Settings::$maxRecurrences;

                            while ($iterator->valid() && --$maxRecurrences) {
                                $startTime = $iterator->getDTStart();
                                if ($this->end && $startTime > $this->end) {
                                    break;
                                }
                                $times[] = [
                                    $iterator->getDTStart(),
                                    $iterator->getDTEnd(),
                                ];

                                $iterator->next();
                            }
                        } else {
                            $startTime = $component->DTSTART->getDateTime($this->timeZone);
                            if ($this->end && $startTime > $this->end) {
                                break;
                            }
                            $endTime = null;
                            if (isset($component->DTEND)) {
                                $endTime = $component->DTEND->getDateTime($this->timeZone);
                            } elseif (isset($component->DURATION)) {
                                $duration = DateTimeParser::parseDuration((string) $component->DURATION);
                                $endTime = clone $startTime;
                                $endTime = $endTime->add($duration);
                            } elseif (!$component->DTSTART->hasTime()) {
                                $endTime = clone $startTime;
                                $endTime = $endTime->modify('+1 day');
                            } else {
                                // The event had no duration (0 seconds)
                                break;
                            }

                            $times[] = [$startTime, $endTime];
                        }

                        foreach ($times as $time) {
                            if ($this->end && $time[0] > $this->end) {
                                break;
                            }
                            if ($this->start && $time[1] < $this->start) {
                                break;
                            }

                            $fbData->add(
                                $time[0]->getTimeStamp(),
                                $time[1]->getTimeStamp(),
                                $FBTYPE
                            );
                        }
                        break;

                    case 'VFREEBUSY':
                        foreach ($component->FREEBUSY as $freebusy) {
                            $fbType = isset($freebusy['FBTYPE']) ? strtoupper($freebusy['FBTYPE']) : 'BUSY';

                            // Skipping intervals marked as 'free'
                            if ('FREE' === $fbType) {
                                continue;
                            }

                            $values = explode(',', $freebusy);
                            foreach ($values as $value) {
                                list($startTime, $endTime) = explode('/', $value);
                                $startTime = DateTimeParser::parseDateTime($startTime);

                                if ('P' === substr($endTime, 0, 1) || '-P' === substr($endTime, 0, 2)) {
                                    $duration = DateTimeParser::parseDuration($endTime);
                                    $endTime = clone $startTime;
                                    $endTime = $endTime->add($duration);
                                } else {
                                    $endTime = DateTimeParser::parseDateTime($endTime);
                                }

                                if ($this->start && $this->start > $endTime) {
                                    continue;
                                }
                                if ($this->end && $this->end < $startTime) {
                                    continue;
                                }
                                $fbData->add(
                                    $startTime->getTimeStamp(),
                                    $endTime->getTimeStamp(),
                                    $fbType
                                );
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * This method takes a FreeBusyData object and generates the VCALENDAR
     * object associated with it.
     *
     * @return VCalendar
     */
    protected function generateFreeBusyCalendar(FreeBusyData $fbData)
    {
        if ($this->baseObject) {
            $calendar = $this->baseObject;
        } else {
            $calendar = new VCalendar();
        }

        $vfreebusy = $calendar->createComponent('VFREEBUSY');
        $calendar->add($vfreebusy);

        if ($this->start) {
            $dtstart = $calendar->createProperty('DTSTART');
            $dtstart->setDateTime($this->start);
            $vfreebusy->add($dtstart);
        }
        if ($this->end) {
            $dtend = $calendar->createProperty('DTEND');
            $dtend->setDateTime($this->end);
            $vfreebusy->add($dtend);
        }

        $tz = new \DateTimeZone('UTC');
        $dtstamp = $calendar->createProperty('DTSTAMP');
        $dtstamp->setDateTime(new DateTimeImmutable('now', $tz));
        $vfreebusy->add($dtstamp);

        foreach ($fbData->getData() as $busyTime) {
            $busyType = strtoupper($busyTime['type']);

            // Ignoring all the FREE parts, because those are already assumed.
            if ('FREE' === $busyType) {
                continue;
            }

            $busyTime[0] = new \DateTimeImmutable('@'.$busyTime['start'], $tz);
            $busyTime[1] = new \DateTimeImmutable('@'.$busyTime['end'], $tz);

            $prop = $calendar->createProperty(
                'FREEBUSY',
                $busyTime[0]->format('Ymd\\THis\\Z').'/'.$busyTime[1]->format('Ymd\\THis\\Z')
            );

            // Only setting FBTYPE if it's not BUSY, because BUSY is the
            // default anyway.
            if ('BUSY' !== $busyType) {
                $prop['FBTYPE'] = $busyType;
            }
            $vfreebusy->add($prop);
        }

        return $calendar;
    }
}
