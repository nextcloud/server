<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

use DateTime;
use DateTimeInterface;
use DateTimeZone;

use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

class EventReader {

	protected VEvent $baseEvent;
	protected DateTimeInterface $baseEventStartDate;
	protected DateTimeZone $baseEventStartTimeZone;
	protected DateTimeInterface $baseEventEndDate;
	protected DateTimeZone $baseEventEndTimeZone;
	protected bool $baseEventStartDateFloating = false;
	protected bool $baseEventEndDateFloating = false;
	protected int $baseEventDuration;

	protected ?EventReaderRRule $rruleIterator = null;
	protected ?EventReaderRDate $rdateIterator = null;
	protected ?EventReaderRRule $eruleIterator = null;
	protected ?EventReaderRDate $edateIterator = null;

	protected array $recurrenceModified;
	protected ?DateTimeInterface $recurrenceCurrentDate;

	protected array $dayNamesMap = [
		'MO' => 'Monday', 'TU' => 'Tuesday', 'WE' => 'Wednesday', 'TH' => 'Thursday', 'FR' => 'Friday', 'SA' => 'Saturday', 'SU' => 'Sunday'
	];
	protected array $monthNamesMap = [
		1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
		7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
	];
	protected array $relativePositionNamesMap = [
		1 => 'First', 2 => 'Second', 3 => 'Third', 4 => 'Fourth', 5 => 'Fifty',
		-1 => 'Last', -2 => 'Second Last', -3 => 'Third Last', -4 => 'Fourth Last', -5 => 'Fifty Last'
	];

	/**
	 * Initilizes the Event Reader
	 *
	 * There is several ways to set up the iterator.
	 *
	 * 1. You can pass a VCALENDAR component (as object or string) and a UID.
	 * 2. You can pass an array of VEVENTs (all UIDS should match).
	 * 3. You can pass a single VEVENT component (as object or string).
	 *
	 * Only the second method is recommended. The other 1 and 3 will be removed
	 * at some point in the future.
	 *
	 * The $uid parameter is only required for the first method.
	 *
	 * @since 30.0.0
	 *
	 * @param VCalendar|VEvent|Array|String $input
	 * @param string|null     				$uid
	 * @param DateTimeZone|null    				$timeZone reference timezone for floating dates and times
	 */
	public function __construct(VCalendar|VEvent|array|string $input, ?string $uid = null, ?DateTimeZone $timeZone = null) {

		// evaluate if the input is a string and convert it to and vobject if required
		if (is_string($input)) {
			$input = \Sabre\VObject\Reader::read($input);
		}
		// evaluate if input is a single event vobject and convert it to a collection
		if ($input instanceof VEvent) {
			$events = [$input];
		}
		// evaluate if input is a calendar vobject
		elseif ($input instanceof VCalendar) {
			// Calendar + UID mode.
			if (!isset($uid)) {
				throw new \InvalidArgumentException('The UID argument is required when a VCALENDAR object is used');
			}
			// extract events from calendar
			$events = $input->getByUID($uid);
			// evaluate if any event where found
			if (count($events) === 0) {
				throw new \InvalidArgumentException('This VCALENDAR did not have an event with UID: '.$uid);
			}
		}
		// evaluate if input is a collection of event vobjects
		elseif (is_array($input)) {
			$events = $input;
		} else {
			throw new \InvalidArgumentException('Invalid input data type');
		}
		// find base event instance and remove it from events collection
		foreach ($events as $key => $vevent) {
			if (!isset($vevent->{'RECURRENCE-ID'})) {
				$this->baseEvent = $vevent;
				unset($events[$key]);
			}
		}
		
		// No base event was found. CalDAV does allow cases where only
		// overridden instances are stored.
		//
		// In this particular case, we're just going to grab the first
		// event and use that instead. This may not always give the
		// desired result.
		if (!isset($this->baseEvent) && count($events) > 0) {
			$this->baseEvent = array_shift($events);
		}

		// determain the event starting time zone
		// we require this to align all other dates times
		// evaluate if timezone paramater was used (treat this as a override)
		if (!is_null($timeZone)) {
			$this->baseEventStartTimeZone = $timeZone;
		}
		// evaluate if event start date has a timezone parameter
		elseif (isset($this->baseEvent->DTSTART->parameters['TZID'])) {
			$this->baseEventStartTimeZone = new DateTimeZone($this->baseEvent->DTSTART->parameters['TZID']->getValue());
		}
		// evaluate if event parent calendar has a time zone
		elseif (isset($this->baseEvent->parent->VTIMEZONE) && isset($this->baseEvent->parent->VTIMEZONE->TZID)) {
			$this->baseEventStartTimeZone = new DateTimeZone($this->baseEvent->parent->VTIMEZONE->TZID->getValue());
		}
		// otherwise, as a last resort use the UTC timezone
		else {
			$this->baseEventStartTimeZone = new DateTimeZone('UTC');
		}

		// determain the event end time zone
		// we require this to align all other dates and times
		// evaluate if timezone paramater was used (treat this as a override)
		if (!is_null($timeZone)) {
			$this->baseEventEndTimeZone = $timeZone;
		}
		// evaluate if event end date has a timezone parameter
		elseif (isset($this->baseEvent->DTEND->parameters['TZID'])) {
			$this->baseEventEndTimeZone = new DateTimeZone($this->baseEvent->DTEND->parameters['TZID']->getValue());
		}
		// evaluate if event parent calendar has a time zone
		elseif (isset($this->baseEvent->parent->VTIMEZONE) && isset($this->baseEvent->parent->VTIMEZONE->TZID)) {
			$this->baseEventEndTimeZone = new DateTimeZone($this->baseEvent->parent->VTIMEZONE->TZID->getValue());
		}
		// otherwise, as a last resort use the start date time zone
		else {
			$this->baseEventEndTimeZone = clone $this->baseEventStartTimeZone;
		}
		// extract start date and time
		$this->baseEventStartDate = $this->baseEvent->DTSTART->getDateTime($this->baseEventStartTimeZone);
		$this->baseEventStartDateFloating = $this->baseEvent->DTSTART->isFloating();
		// determain event end date and duration
		// evaluate if end date exists
		// extract end date and calculate duration
		if (isset($this->baseEvent->DTEND)) {
			$this->baseEventEndDate = $this->baseEvent->DTEND->getDateTime($this->baseEventEndTimeZone);
			$this->baseEventEndDateFloating = $this->baseEvent->DTEND->isFloating();
			$this->baseEventDuration =
				$this->baseEvent->DTEND->getDateTime($this->baseEventEndTimeZone)->getTimeStamp() -
				$this->baseEventStartDate->getTimeStamp();
		}
		// evaluate if duration exists
		// extract duration and calculate end date
		elseif (isset($this->baseEvent->DURATION)) {
			$this->baseEventDuration = $this->baseEvent->DURATION->getDateInterval();
			$this->baseEventEndDate = ((clone $this->baseEventStartDate)->add($this->baseEventDuration));
		}
		// evaluate if start date is floating
		// set duration to 24 hours and calculate the end date
		// according to the rfc any event without a end date or duration is a complete day
		elseif ($this->baseEventStartDateFloating == true) {
			$this->baseEventDuration = 86400;
			$this->baseEventEndDate = ((clone $this->baseEventStartDate)->add($this->baseEventDuration));
		}
		// otherwise, set duration to zero this should never happen
		else {
			$this->baseEventDuration = 0;
			$this->baseEventEndDate = $this->baseEventStartDate;
		}
		// evaluate if RRULE exist and construct iterator
		if (isset($this->baseEvent->RRULE)) {
			$this->rruleIterator = new EventReaderRRule(
				$this->baseEvent->RRULE->getParts(),
				$this->baseEventStartDate
			);
		}
		// evaluate if RDATE exist and construct iterator
		if (isset($this->baseEvent->RDATE)) {
			$this->rdateIterator = new EventReaderRDate(
				$this->baseEvent->RDATE->getValue(),
				$this->baseEventStartDate
			);
		}
		// evaluate if EXRULE exist and construct iterator
		if (isset($this->baseEvent->EXRULE)) {
			$this->eruleIterator = new EventReaderRRule(
				$this->baseEvent->EXRULE->getParts(),
				$this->baseEventStartDate
			);
		}
		// evaluate if EXDATE exist and construct iterator
		if (isset($this->baseEvent->EXDATE)) {
			$this->edateIterator = new EventReaderRDate(
				$this->baseEvent->EXDATE->getValue(),
				$this->baseEventStartDate
			);
		}
		// construct collection of modified events with recurrance id as hash
		foreach ($events as $vevent) {
			$this->recurrenceModified[$vevent->{'RECURRENCE-ID'}->getDateTime($this->baseEventStartTimeZone)->getTimeStamp()] = $vevent;
		}
		
		$this->recurrenceCurrentDate = clone $this->baseEventStartDate;
	}

	/**
	 * retrieve date and time of event start
	 *
	 * @since 30.0.0
	 *
	 * @return DateTime
	 */
	public function startDateTime(): DateTime {
		return DateTime::createFromImmutable($this->baseEventStartDate);
	}

	/**
	 * retrieve time zone of event start
	 *
	 * @since 30.0.0
	 *
	 * @return DateTimeZone
	 */
	public function startTimeZone(): DateTimeZone {
		return $this->baseEventStartTimeZone;
	}

	/**
	 * retrieve date and time of event end
	 *
	 * @since 30.0.0
	 *
	 * @return DateTime
	 */
	public function endDateTime(): DateTime {
		return DateTime::createFromImmutable($this->baseEventEndDate);
	}

	/**
	 * retrieve time zone of event end
	 *
	 * @since 30.0.0
	 *
	 * @return DateTimeZone
	 */
	public function endTimeZone(): DateTimeZone {
		return $this->baseEventEndTimeZone;
	}

	/**
	 * is this an all day event
	 *
	 * @since 30.0.0
	 *
	 * @return bool
	 */
	public function entireDay(): bool {
		return $this->baseEventStartDateFloating;
	}

	/**
	 * is this a recurring event
	 *
	 * @since 30.0.0
	 *
	 * @return bool
	 */
	public function recurs(): bool {
		return (isset($this->rruleIterator) || isset($this->rdateIterator));
	}

	/**
	 * event recurrance pattern
	 *
	 * @since 30.0.0
	 *
	 * @return string|null				R - Relative or A - Absolute
	 */
	public function recurringPattern(): string | null {
		if (!isset($this->rruleIterator) && !isset($this->rdateIterator)) {
			return null;
		}
		if (isset($this->rruleIterator) && $this->rruleIterator->isRelative()) {
			return 'R';
		}
		return 'A';
	}

	/**
	 * event recurrance precision
	 *
	 * @since 30.0.0
	 *
	 * @return string|null			daily, weekly, monthly, yearly, fixed
	 */
	public function recurringPrecision(): string | null {
		if (isset($this->rruleIterator)) {
			return $this->rruleIterator->precision();
		}
		if (isset($this->rdateIterator)) {
			return 'fixed';
		}
		return null;
	}

	/**
	 * event recurrance interval
	 *
	 * @since 30.0.0
	 *
	 * @return int|null
	 */
	public function recurringInterval(): int | null {
		return isset($this->rruleIterator) ? $this->rruleIterator->interval() : null;
	}

	/**
	 * event recurrance conclusion
	 *
	 * returns true if RRULE with UNTIL or COUNT (calculated) is used
	 * returns true RDATE is used
	 * returns false if RRULE or RDATE are absent, or RRRULE is infinite
	 *
	 * @since 30.0.0
	 *
	 * @return bool
	 */
	public function recurringConcludes(): bool {

		// retrieve rrule conclusions
		if (isset($this->rruleIterator) && (
			!empty($this->rruleIterator->concludesOn()) ||
			!empty($this->rruleIterator->concludesAfter())
		)) {
			return true;
		}
		// retrieve rdate conclusions
		if (isset($this->rdateIterator) &&
			$this->rdateIterator->concludesAfter()
		) {
			return true;
		}

		return false;

	}

	/**
	 * event recurrance conclusion iterations
	 *
	 * returns the COUNT value if RRULE is used
	 * returns the collection count if RDATE is used
	 * returns combined count of RRULE COUNT and RDATE if both are used
	 * returns null if RRULE and RDATE are absent
	 *
	 * @since 30.0.0
	 *
	 * @return int|null
	 */
	public function recurringConcludesAfter(): int | null {
		
		// construct count place holder
		$count = 0;
		// retrieve and add RRULE iterations count
		$count += isset($this->rruleIterator) ? (int) $this->rruleIterator->concludesAfter() : 0;
		// retrieve and add RDATE iterations count
		$count += isset($this->rdateIterator) ? (int) $this->rdateIterator->concludesAfter() : 0;
		// return count
		return !empty($count) ? $count : null;

	}

	/**
	 * event recurrance conclusion date
	 *
	 * returns the last date of UNTIL or COUNT (calculated) if RRULE is used
	 * returns the last date in the collection if RDATE is used
	 * returns the highest date if both RRULE and RDATE are used
	 * returns null if RRULE and RDATE are absent or RRULE is infinite
	 *
	 * @since 30.0.0
	 *
	 * @return DateTime|null
	 */
	public function recurringConcludesOn(): \DateTime | null {

		// retrieve rrule conclusion date
		if (isset($this->rruleIterator)) {
			$rrule = $this->rruleIterator->concludes();
			// evaluate if rrule conclusion is null
			// if this is null that means the recurrance is infinate
			if ($rrule === null) {
				return null;
			}
		}
		// retrieve rdate conclusion date
		if (isset($this->rdateIterator)) {
			$rdate = $this->rdateIterator->concludes();
		}
		// evaluate if both rrule and rdate have date
		if (isset($rdate) && isset($rrule)) {
			// return the highest date
			return (($rdate > $rrule) ? $rdate : $rrule);
		} elseif (isset($rrule)) {
			return $rrule;
		} elseif (isset($rdate)) {
			return $rdate;
		}

		return null;

	}

	/**
	 * event recurrance days of the week
	 *
	 * returns collection of RRULE BYDAY day(s) ['MO','WE','FR']
	 * returns blank collection if RRULE is absent, RDATE presents or absents has no affect
	 *
	 * @since 30.0.0
	 *
	 * @return array
	 */
	public function recurringDaysOfWeek(): array {
		// evaluate if RRULE exists and return day(s) of the week
		return isset($this->rruleIterator) ? $this->rruleIterator->daysOfWeek() : [];
	}

	/**
	 * event recurrance days of the week (named)
	 *
	 * returns collection of RRULE BYDAY day(s) ['Monday','Wednesday','Friday']
	 * returns blank collection if RRULE is absent, RDATE presents or absents has no affect
	 *
	 * @since 30.0.0
	 *
	 * @return array
	 */
	public function recurringDaysOfWeekNamed(): array {
		// evaluate if RRULE exists and extract day(s) of the week
		$days = isset($this->rruleIterator) ? $this->rruleIterator->daysOfWeek() : null;
		// evaluate if months array is set
		if (is_array($days)) {
			// convert numberic month to month name
			foreach ($days as $key => $value) {
				$days[$key] = $this->dayNamesMap[$value];
			}
			return $days;
		}
		// return empty array if evaluation failed
		return [];
	}

	/**
	 * event recurrance days of the month
	 *
	 * returns collection of RRULE BYMONTHDAY day(s) [7, 15, 31]
	 * returns blank collection if RRULE is absent, RDATE presents or absents has no affect
	 *
	 * @since 30.0.0
	 *
	 * @return array
	 */
	public function recurringDaysOfMonth(): array {
		// evaluate if RRULE exists and return day(s) of the month
		return isset($this->rruleIterator) ? $this->rruleIterator->daysOfMonth() : [];
	}

	/**
	 * event recurrance days of the year
	 *
	 * returns collection of RRULE BYYEARDAY day(s) [57, 205, 365]
	 * returns blank collection if RRULE is absent, RDATE presents or absents has no affect
	 *
	 * @since 30.0.0
	 *
	 * @return array
	 */
	public function recurringDaysOfYear(): array {
		// evaluate if RRULE exists and return day(s) of the year
		return isset($this->rruleIterator) ? $this->rruleIterator->daysOfYear() : [];
	}

	/**
	 * event recurrance weeks of the month
	 *
	 * returns collection of RRULE SETPOS weeks(s) [1, 3, -1]
	 * returns blank collection if RRULE is absent or SETPOS is absent, RDATE presents or absents has no affect
	 *
	 * @since 30.0.0
	 *
	 * @return array
	 */
	public function recurringWeeksOfMonth(): array {
		// evaluate if RRULE exists and RRULE is relative return relative position(s)
		return (isset($this->rruleIterator) && $this->rruleIterator->isRelative()) ? $this->rruleIterator->relativePosition() : [];
	}

	/**
	 * event recurrance weeks of the month (named)
	 *
	 * returns collection of RRULE SETPOS weeks(s) [1, 3, -1]
	 * returns blank collection if RRULE is absent or SETPOS is absent, RDATE presents or absents has no affect
	 *
	 * @since 30.0.0
	 *
	 * @return array
	 */
	public function recurringWeeksOfMonthNamed(): array {
		// evaluate if RRULE exists and extract relative position(s)
		$days = (isset($this->rruleIterator) && $this->rruleIterator->isRelative()) ? $this->rruleIterator->relativePosition() : null;
		// evaluate if relative position is set
		if (is_array($days)) {
			// convert numberic relative position to relative label
			foreach ($days as $key => $value) {
				$days[$key] = $this->relativePositionNamesMap[$value];
			}
			return $days;
		}
		// return empty array if evaluation failed
		return [];
	}

	/**
	 * event recurrance weeks of the year
	 *
	 * returns collection of RRULE BYWEEKNO weeks(s) [12, 32, 52]
	 * returns blank collection if RRULE is absent, RDATE presents or absents has no affect
	 *
	 * @since 30.0.0
	 *
	 * @return array
	 */
	public function recurringWeeksOfYear(): array {
		// evaluate if RRULE exists and return weeks(s) of the year
		return isset($this->rruleIterator) ? $this->rruleIterator->weeksOfYear() : [];
	}

	/**
	 * event recurrance months of the year
	 *
	 * returns collection of RRULE BYMONTH month(s) [3, 7, 12]
	 * returns blank collection if RRULE is absent, RDATE presents or absents has no affect
	 *
	 * @since 30.0.0
	 *
	 * @return array
	 */
	public function recurringMonthsOfYear(): array {
		// evaluate if RRULE exists and return month(s) of the year
		return isset($this->rruleIterator) ? $this->rruleIterator->monthsOfYear() : [];
	}

	/**
	 * event recurrance months of the year (named)
	 *
	 * returns collection of RRULE BYMONTH month(s) [3, 7, 12]
	 * returns blank collection if RRULE is absent, RDATE presents or absents has no affect
	 *
	 * @since 30.0.0
	 *
	 * @return array
	 */
	public function recurringMonthsOfYearNamed(): array {
		// evaluate if RRULE exists and extract month(s) of the year
		$months = isset($this->rruleIterator) ? $this->rruleIterator->monthsOfYear() : null;
		// evaluate if months array is set
		if (is_array($months)) {
			// convert numberic month to month name
			foreach ($months as $key => $value) {
				$months[$key] = $this->monthNamesMap[$value];
			}
			return $months;
		}
		// return empty array if evaluation failed
		return [];
	}

	/**
	 * event recurrance relative positions
	 *
	 * returns collection of RRULE SETPOS value(s) [1, 5, -3]
	 * returns blank collection if RRULE is absent, RDATE presents or absents has no affect
	 *
	 * @since 30.0.0
	 *
	 * @return array
	 */
	public function recurringRelativePosition(): array {
		// evaluate if RRULE exists and return relative position(s)
		return isset($this->rruleIterator) ? $this->rruleIterator->relativePosition() : [];
	}

	/**
	 * event recurrance relative positions (named)
	 *
	 * returns collection of RRULE SETPOS [1, 3, -1]
	 * returns blank collection if RRULE is absent or SETPOS is absent, RDATE presents or absents has no affect
	 *
	 * @since 30.0.0
	 *
	 * @return array
	 */
	public function recurringRelativePositionNamed(): array {
		// evaluate if RRULE exists and extract relative position(s)
		$positions = (isset($this->rruleIterator) && $this->rruleIterator->isRelative()) ? $this->rruleIterator->relativePosition() : null;
		// evaluate if relative position is set
		if (is_array($positions)) {
			// convert numberic relative position to relative label
			foreach ($positions as $key => $value) {
				$positions[$key] = $this->relativePositionNamesMap[$value];
			}
			return $positions;
		}
		// return empty array if evaluation failed
		return [];
	}

	/**
	 * event recurrance date
	 *
	 * returns date of currently selected recurrance
	 *
	 * @since 30.0.0
	 *
	 * @return DateTime
	 */
	public function recurrenceDate(): DateTime | null {
		if ($this->recurrenceCurrentDate !== null) {
			return DateTime::createFromImmutable($this->recurrenceCurrentDate);
		} else {
			return null;
		}
	}

	/**
	 * event recurrance rewind
	 *
	 * sets the current recurrance to the first recurrance in the collection
	 *
	 * @since 30.0.0
	 *
	 * @return void
	 */
	public function recurrenceRewind(): void {
		// rewind and increment rrule
		if (isset($this->rruleIterator)) {
			$this->rruleIterator->rewind();
		}
		// rewind and increment rdate
		if (isset($this->rdateIterator)) {
			$this->rdateIterator->rewind();
		}
		// rewind and increment exrule
		if (isset($this->eruleIterator)) {
			$this->eruleIterator->rewind();
		}
		// rewind and increment exdate
		if (isset($this->edateIterator)) {
			$this->edateIterator->rewind();
		}
		// set current date to event start date
		$this->recurrenceCurrentDate = clone $this->baseEventStartDate;
	}

	/**
	 * event recurrance advance
	 *
	 * sets the current recurrance to the next recurrance in the collection
	 *
	 * @since 30.0.0
	 *
	 * @return void
	 */
	public function recurrenceAdvance(): void {
		// place holders
		$nextOccurrenceDate = null;
		$nextExceptionDate = null;
		$rruleDate = null;
		$rdateDate = null;
		$eruleDate = null;
		$edateDate = null;
		// evaludate if rrule is set and advance one interation past current date
		if (isset($this->rruleIterator)) {
			// forward rrule to the next future date
			while ($this->rruleIterator->valid() && $this->rruleIterator->current() <= $this->recurrenceCurrentDate) {
				$this->rruleIterator->next();
			}
			$rruleDate = $this->rruleIterator->current();
		}
		// evaludate if rdate is set and advance one interation past current date
		if (isset($this->rdateIterator)) {
			// forward rdate to the next future date
			while ($this->rdateIterator->valid() && $this->rdateIterator->current() <= $this->recurrenceCurrentDate) {
				$this->rdateIterator->next();
			}
			$rdateDate = $this->rdateIterator->current();
		}
		if (isset($rruleDate) && isset($rdateDate)) {
			$nextOccurrenceDate = ($rruleDate <= $rdateDate) ? $rruleDate : $rdateDate;
		} elseif (isset($rruleDate)) {
			$nextOccurrenceDate = $rruleDate;
		} elseif (isset($rdateDate)) {
			$nextOccurrenceDate = $rdateDate;
		}

		// evaludate if exrule is set and advance one interation past current date
		if (isset($this->eruleIterator)) {
			// forward exrule to the next future date
			while ($this->eruleIterator->valid() && $this->eruleIterator->current() <= $this->recurrenceCurrentDate) {
				$this->eruleIterator->next();
			}
			$eruleDate = $this->eruleIterator->current();
		}
		// evaludate if exdate is set and advance one interation past current date
		if (isset($this->edateIterator)) {
			// forward exdate to the next future date
			while ($this->edateIterator->valid() && $this->edateIterator->current() <= $this->recurrenceCurrentDate) {
				$this->edateIterator->next();
			}
			$edateDate = $this->edateIterator->current();
		}
		// evaludate if exrule and exdate are set and set nextExDate to the first next date
		if (isset($eruleDate) && isset($edateDate)) {
			$nextExceptionDate = ($eruleDate <= $edateDate) ? $eruleDate : $edateDate;
		} elseif (isset($eruleDate)) {
			$nextExceptionDate = $eruleDate;
		} elseif (isset($edateDate)) {
			$nextExceptionDate = $edateDate;
		}
		// if the next date is part of exrule or exdate find another date
		if (isset($nextOccurrenceDate) && isset($nextExceptionDate) && $nextOccurrenceDate == $nextExceptionDate) {
			$this->recurrenceCurrentDate = $nextOccurrenceDate;
			$this->recurrenceAdvance();
		} else {
			$this->recurrenceCurrentDate = $nextOccurrenceDate;
		}
	}

	/**
	 * event recurrance advance
	 *
	 * sets the current recurrance to the next recurrance in the collection after the specific date
	 *
	 * @since 30.0.0
	 *
	 * @param DateTimeInterface $dt			date and time to advance
	 *
	 * @return void
	 */
	public function recurrenceAdvanceTo(DateTimeInterface $dt): void {
		while ($this->recurrenceCurrentDate !== null && $this->recurrenceCurrentDate < $dt) {
			$this->recurrenceAdvance();
		}
	}

}
