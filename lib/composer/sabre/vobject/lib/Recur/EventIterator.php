<?php

namespace Sabre\VObject\Recur;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use Sabre\VObject\Component;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Settings;

/**
 * This class is used to determine new for a recurring event, when the next
 * events occur.
 *
 * This iterator may loop infinitely in the future, therefore it is important
 * that if you use this class, you set hard limits for the amount of iterations
 * you want to handle.
 *
 * Note that currently there is not full support for the entire iCalendar
 * specification, as it's very complex and contains a lot of permutations
 * that's not yet used very often in software.
 *
 * For the focus has been on features as they actually appear in Calendaring
 * software, but this may well get expanded as needed / on demand
 *
 * The following RRULE properties are supported
 *   * UNTIL
 *   * INTERVAL
 *   * COUNT
 *   * FREQ=DAILY
 *     * BYDAY
 *     * BYHOUR
 *     * BYMONTH
 *   * FREQ=WEEKLY
 *     * BYDAY
 *     * BYHOUR
 *     * WKST
 *   * FREQ=MONTHLY
 *     * BYMONTHDAY
 *     * BYDAY
 *     * BYSETPOS
 *   * FREQ=YEARLY
 *     * BYMONTH
 *     * BYYEARDAY
 *     * BYWEEKNO
 *     * BYMONTHDAY (only if BYMONTH is also set)
 *     * BYDAY (only if BYMONTH is also set)
 *
 * Anything beyond this is 'undefined', which means that it may get ignored, or
 * you may get unexpected results. The effect is that in some applications the
 * specified recurrence may look incorrect, or is missing.
 *
 * The recurrence iterator also does not yet support THISANDFUTURE.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class EventIterator implements \Iterator
{
    /**
     * Reference timeZone for floating dates and times.
     *
     * @var DateTimeZone
     */
    protected $timeZone;

    /**
     * True if we're iterating an all-day event.
     *
     * @var bool
     */
    protected $allDay = false;

    /**
     * Creates the iterator.
     *
     * There's three ways to set up the iterator.
     *
     * 1. You can pass a VCALENDAR component and a UID.
     * 2. You can pass an array of VEVENTs (all UIDS should match).
     * 3. You can pass a single VEVENT component.
     *
     * Only the second method is recommended. The other 1 and 3 will be removed
     * at some point in the future.
     *
     * The $uid parameter is only required for the first method.
     *
     * @param Component|array $input
     * @param string|null     $uid
     * @param DateTimeZone    $timeZone reference timezone for floating dates and
     *                                  times
     */
    public function __construct($input, $uid = null, DateTimeZone $timeZone = null)
    {
        if (is_null($timeZone)) {
            $timeZone = new DateTimeZone('UTC');
        }
        $this->timeZone = $timeZone;

        if (is_array($input)) {
            $events = $input;
        } elseif ($input instanceof VEvent) {
            // Single instance mode.
            $events = [$input];
        } else {
            // Calendar + UID mode.
            $uid = (string) $uid;
            if (!$uid) {
                throw new InvalidArgumentException('The UID argument is required when a VCALENDAR is passed to this constructor');
            }
            if (!isset($input->VEVENT)) {
                throw new InvalidArgumentException('No events found in this calendar');
            }
            $events = $input->getByUID($uid);
        }

        foreach ($events as $vevent) {
            if (!isset($vevent->{'RECURRENCE-ID'})) {
                $this->masterEvent = $vevent;
            } else {
                $this->exceptions[
                    $vevent->{'RECURRENCE-ID'}->getDateTime($this->timeZone)->getTimeStamp()
                ] = true;
                $this->overriddenEvents[] = $vevent;
            }
        }

        if (!$this->masterEvent) {
            // No base event was found. CalDAV does allow cases where only
            // overridden instances are stored.
            //
            // In this particular case, we're just going to grab the first
            // event and use that instead. This may not always give the
            // desired result.
            if (!count($this->overriddenEvents)) {
                throw new InvalidArgumentException('This VCALENDAR did not have an event with UID: '.$uid);
            }
            $this->masterEvent = array_shift($this->overriddenEvents);
        }

        $this->startDate = $this->masterEvent->DTSTART->getDateTime($this->timeZone);
        $this->allDay = !$this->masterEvent->DTSTART->hasTime();

        if (isset($this->masterEvent->EXDATE)) {
            foreach ($this->masterEvent->EXDATE as $exDate) {
                foreach ($exDate->getDateTimes($this->timeZone) as $dt) {
                    $this->exceptions[$dt->getTimeStamp()] = true;
                }
            }
        }

        if (isset($this->masterEvent->DTEND)) {
            $this->eventDuration =
                $this->masterEvent->DTEND->getDateTime($this->timeZone)->getTimeStamp() -
                $this->startDate->getTimeStamp();
        } elseif (isset($this->masterEvent->DURATION)) {
            $duration = $this->masterEvent->DURATION->getDateInterval();
            $end = clone $this->startDate;
            $end = $end->add($duration);
            $this->eventDuration = $end->getTimeStamp() - $this->startDate->getTimeStamp();
        } elseif ($this->allDay) {
            $this->eventDuration = 3600 * 24;
        } else {
            $this->eventDuration = 0;
        }

        if (isset($this->masterEvent->RDATE)) {
            $this->recurIterator = new RDateIterator(
                $this->masterEvent->RDATE->getParts(),
                $this->startDate
            );
        } elseif (isset($this->masterEvent->RRULE)) {
            $this->recurIterator = new RRuleIterator(
                $this->masterEvent->RRULE->getParts(),
                $this->startDate
            );
        } else {
            $this->recurIterator = new RRuleIterator(
                [
                    'FREQ' => 'DAILY',
                    'COUNT' => 1,
                ],
                $this->startDate
            );
        }

        $this->rewind();
        if (!$this->valid()) {
            throw new NoInstancesException('This recurrence rule does not generate any valid instances');
        }
    }

    /**
     * Returns the date for the current position of the iterator.
     *
     * @return DateTimeImmutable
     */
    public function current()
    {
        if ($this->currentDate) {
            return clone $this->currentDate;
        }
    }

    /**
     * This method returns the start date for the current iteration of the
     * event.
     *
     * @return DateTimeImmutable
     */
    public function getDtStart()
    {
        if ($this->currentDate) {
            return clone $this->currentDate;
        }
    }

    /**
     * This method returns the end date for the current iteration of the
     * event.
     *
     * @return DateTimeImmutable
     */
    public function getDtEnd()
    {
        if (!$this->valid()) {
            return;
        }
        $end = clone $this->currentDate;

        return $end->modify('+'.$this->eventDuration.' seconds');
    }

    /**
     * Returns a VEVENT for the current iterations of the event.
     *
     * This VEVENT will have a recurrence id, and its DTSTART and DTEND
     * altered.
     *
     * @return VEvent
     */
    public function getEventObject()
    {
        if ($this->currentOverriddenEvent) {
            return $this->currentOverriddenEvent;
        }

        $event = clone $this->masterEvent;

        // Ignoring the following block, because PHPUnit's code coverage
        // ignores most of these lines, and this messes with our stats.
        //
        // @codeCoverageIgnoreStart
        unset(
            $event->RRULE,
            $event->EXDATE,
            $event->RDATE,
            $event->EXRULE,
            $event->{'RECURRENCE-ID'}
        );
        // @codeCoverageIgnoreEnd

        $event->DTSTART->setDateTime($this->getDtStart(), $event->DTSTART->isFloating());
        if (isset($event->DTEND)) {
            $event->DTEND->setDateTime($this->getDtEnd(), $event->DTEND->isFloating());
        }
        $recurid = clone $event->DTSTART;
        $recurid->name = 'RECURRENCE-ID';
        $event->add($recurid);

        return $event;
    }

    /**
     * Returns the current position of the iterator.
     *
     * This is for us simply a 0-based index.
     *
     * @return int
     */
    public function key()
    {
        // The counter is always 1 ahead.
        return $this->counter - 1;
    }

    /**
     * This is called after next, to see if the iterator is still at a valid
     * position, or if it's at the end.
     *
     * @return bool
     */
    public function valid()
    {
        if ($this->counter > Settings::$maxRecurrences && -1 !== Settings::$maxRecurrences) {
            throw new MaxInstancesExceededException('Recurring events are only allowed to generate '.Settings::$maxRecurrences);
        }

        return (bool) $this->currentDate;
    }

    /**
     * Sets the iterator back to the starting point.
     */
    public function rewind()
    {
        $this->recurIterator->rewind();
        // re-creating overridden event index.
        $index = [];
        foreach ($this->overriddenEvents as $key => $event) {
            $stamp = $event->DTSTART->getDateTime($this->timeZone)->getTimeStamp();
            $index[$stamp][] = $key;
        }
        krsort($index);
        $this->counter = 0;
        $this->overriddenEventsIndex = $index;
        $this->currentOverriddenEvent = null;

        $this->nextDate = null;
        $this->currentDate = clone $this->startDate;

        $this->next();
    }

    /**
     * Advances the iterator with one step.
     */
    public function next()
    {
        $this->currentOverriddenEvent = null;
        ++$this->counter;
        if ($this->nextDate) {
            // We had a stored value.
            $nextDate = $this->nextDate;
            $this->nextDate = null;
        } else {
            // We need to ask rruleparser for the next date.
            // We need to do this until we find a date that's not in the
            // exception list.
            do {
                if (!$this->recurIterator->valid()) {
                    $nextDate = null;
                    break;
                }
                $nextDate = $this->recurIterator->current();
                $this->recurIterator->next();
            } while (isset($this->exceptions[$nextDate->getTimeStamp()]));
        }

        // $nextDate now contains what rrule thinks is the next one, but an
        // overridden event may cut ahead.
        if ($this->overriddenEventsIndex) {
            $offsets = end($this->overriddenEventsIndex);
            $timestamp = key($this->overriddenEventsIndex);
            $offset = end($offsets);
            if (!$nextDate || $timestamp < $nextDate->getTimeStamp()) {
                // Overridden event comes first.
                $this->currentOverriddenEvent = $this->overriddenEvents[$offset];

                // Putting the rrule next date aside.
                $this->nextDate = $nextDate;
                $this->currentDate = $this->currentOverriddenEvent->DTSTART->getDateTime($this->timeZone);

                // Ensuring that this item will only be used once.
                array_pop($this->overriddenEventsIndex[$timestamp]);
                if (!$this->overriddenEventsIndex[$timestamp]) {
                    array_pop($this->overriddenEventsIndex);
                }

                // Exit point!
                return;
            }
        }

        $this->currentDate = $nextDate;
    }

    /**
     * Quickly jump to a date in the future.
     */
    public function fastForward(DateTimeInterface $dateTime)
    {
        while ($this->valid() && $this->getDtEnd() <= $dateTime) {
            $this->next();
        }
    }

    /**
     * Returns true if this recurring event never ends.
     *
     * @return bool
     */
    public function isInfinite()
    {
        return $this->recurIterator->isInfinite();
    }

    /**
     * RRULE parser.
     *
     * @var RRuleIterator
     */
    protected $recurIterator;

    /**
     * The duration, in seconds, of the master event.
     *
     * We use this to calculate the DTEND for subsequent events.
     */
    protected $eventDuration;

    /**
     * A reference to the main (master) event.
     *
     * @var VEVENT
     */
    protected $masterEvent;

    /**
     * List of overridden events.
     *
     * @var array
     */
    protected $overriddenEvents = [];

    /**
     * Overridden event index.
     *
     * Key is timestamp, value is the list of indexes of the item in the $overriddenEvent
     * property.
     *
     * @var array
     */
    protected $overriddenEventsIndex;

    /**
     * A list of recurrence-id's that are either part of EXDATE, or are
     * overridden.
     *
     * @var array
     */
    protected $exceptions = [];

    /**
     * Internal event counter.
     *
     * @var int
     */
    protected $counter;

    /**
     * The very start of the iteration process.
     *
     * @var DateTimeImmutable
     */
    protected $startDate;

    /**
     * Where we are currently in the iteration process.
     *
     * @var DateTimeImmutable
     */
    protected $currentDate;

    /**
     * The next date from the rrule parser.
     *
     * Sometimes we need to temporary store the next date, because an
     * overridden event came before.
     *
     * @var DateTimeImmutable
     */
    protected $nextDate;

    /**
     * The event that overwrites the current iteration.
     *
     * @var VEVENT
     */
    protected $currentOverriddenEvent;
}
