<?php

declare(strict_types=1);

/**
 * @copyright 2024 Sebastian Krupinski <krupinski01@gmail.com>
 *
 * @author Sebastian Krupinski <krupinski01@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
	protected int $baseEventDuration;

	protected EventReaderRRule $rruleIterator;
	protected EventReaderRDate $rdateIterator;
	protected EventReaderRRule $eruleIterator;
	protected EventReaderRDate $edateIterator;

	protected array $recurrenceModified;
	protected DateTimeInterface $recurrenceCurrentDate;

	protected array $dayNamesMap = [
		'MO' => 'Monday', 'TU' => 'Tuesday', 'WE' => 'Wednesday', 'TH' => 'Thursday', 'FR' => 'Friday', 'SA' => 'Saturday', 'SU' => 'Sunday'
	];
	protected array $monthNamesMap = [
		1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
		7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
	];
	protected array $relativePositionNamesMap = [
		1 => 'First', 2 => 'Second', 3 => 'Third', 4 => 'Fourth', 5 => 'Fifty', -1 => 'Last', -2 => 'Second Last'
	];

	/**
	 * Initilizes the Event Reader
	 *
	 * There's three ways to set up the iterator.
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
	 * @param VCalendar|VEvent|Array|String $input
	 * @param string|null     				$uid
	 * @param DateTimeZone    				$timeZone reference timezone for floating dates and times
	 */
	public function __construct(VCalendar|VEvent|array|String $input, ?string $uid = null, ?DateTimeZone $timeZone = null) {

		// evaluate if the input is a string and convert it to and vobject if required
		if (is_string($input)) {
			$input = \Sabre\VObject\Reader::read($input);
		}
		
		// evaluate if input is a single event vobject and convert it to a collection
		if ($input instanceof VEvent) {
			$events = [$input];
			// evaluate if input is a calendar vobject
		} elseif ($input instanceof VCalendar) {
			// Calendar + UID mode.
			if (!$uid) {
				throw new InvalidArgumentException('The UID argument is required when a VCALENDAR object is used');
			}
			// extract events from calendar
			$events = $input->getByUID($uid);
			// evaluate if any event where found
			if (count($events) === 0) {
				throw new InvalidArgumentException('This VCALENDAR did not have an event with UID: '.$uid);
			}
			// evaluate if input is a collection of event vobjects
		} elseif (is_array($input)) {
			$events = $input;
		} else {
			throw new InvalidArgumentException('Invalid input data type');
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
		if (!$this->baseEvent && count($events) > 0) {
			$this->baseEvent = array_shift($events);
		}

		// determain the event starting time zone
		// we require this to align all other dates times
		// evaluate if timezone paramater was used (treat this as a override)
		if (!is_null($timeZone)) {
			$this->baseEventStartTimeZone = $timezone;
			// evaluate if start date has a timezone parameter
		} elseif (isset($this->baseEvent->DTSTART->parameters['TZID'])) {
			$this->baseEventStartTimeZone = new DateTimeZone($this->baseEvent->DTSTART->parameters['TZID']->getValue());
		} elseif (isset($input->VTIMEZONE[0]) && isset($input->VTIMEZONE[0]->TZID)) {
			$this->baseEventStartTimeZone = new DateTimeZone($input->VTIMEZONE[0]->TZID);
		} else {
			$this->baseEventStartTimeZone = new DateTimeZone('UTC');
		}

		// determain the event end time zone
		// we require this to align all other dates and times
		// evaluate if timezone paramater was used (treat this as a override)
		if (!is_null($timeZone)) {
			$this->baseEventEndTimeZone = $timezone;
			// evaluate if end date has a timezone parameter
		} elseif (isset($this->baseEvent->DTEND->parameters['TZID'])) {
			$this->baseEventEndTimeZone = new DateTimeZone($this->baseEvent->DTEND->parameters['TZID']->getValue());
		} elseif (isset($input->VTIMEZONE[1]) && isset($input->VTIMEZONE[1]->TZID)) {
			$this->baseEventEndTimeZone = new DateTimeZone($input->VTIMEZONE[1]->TZID);
		} else {
			$this->baseEventEndTimeZone = new DateTimeZone('UTC');
		}

		$this->baseEventStartDate = $this->baseEvent->DTSTART->getDateTime($this->baseEventStartTimeZone);
		$this->baseEventEndDate = $this->baseEvent->DTEND->getDateTime($this->baseEventEndTimeZone);
		$this->allDay = !$this->baseEvent->DTSTART->hasTime();

		// determain event duration
		if (isset($this->baseEvent->DTEND)) {
			$this->eventDuration =
				$this->baseEvent->DTEND->getDateTime($this->baseEventTimeZone)->getTimeStamp() -
				$this->baseEventStartDate->getTimeStamp();
		} elseif (isset($this->baseEvent->DURATION)) {
			$duration = $this->baseEvent->DURATION->getDateInterval();
			$end = clone $this->baseEventStartDate;
			$end = $end->add($duration);
			$this->eventDuration = $end->getTimeStamp() - $this->baseEventStartDate->getTimeStamp();
		} elseif ($this->allDay) {
			$this->eventDuration = 3600 * 24;
		} else {
			$this->eventDuration = 0;
		}

		if (isset($this->baseEvent->RRULE)) {
			$this->rruleIterator = new EventReaderRRule(
				$this->baseEvent->RRULE->getParts(),
				$this->baseEventStartDate
			);
		}
		if (isset($this->baseEvent->RDATE)) {
			$this->rdateIterator = new EventReaderRDate(
				$this->baseEvent->RDATE->getParts(),
				$this->baseEventStartDate
			);
		}
		if (isset($this->baseEvent->EXRULE)) {
			$this->eruleIterator = new EventReaderRRule(
				$this->baseEvent->EXRULE->getParts(),
				$this->baseEventStartDate
			);
		}
		if (isset($this->baseEvent->EXDATE)) {
			$this->edateIterator = new EventReaderRDate(
				$this->baseEvent->EXDATE->getParts(),
				$this->baseEventStartDate
			);
		}
		// construct collection of modified events with recurrance id as hash
		foreach ($events as $vevent) {
			$this->recurringAltered[$vevent->{'RECURRENCE-ID'}->getDateTime($this->baseEventTimeZone)->getTimeStamp()] = $vevent;
		}
		
		$this->recurrenceCurrentDate = clone $this->baseEventStartDate;
	}

	public function startDate(?string $format = null): string {
		if (isset($format)) {
			return $this->baseEventStartDate->format($format);
		} else {
			return $this->baseEventStartDate->format('Y-m-d');
		}
	}

	public function startTime(?string $format = null): string {
		if (isset($format)) {
			return $this->baseEventStartDate->format($format);
		} else {
			return $this->baseEventStartDate->format('H:ia');
		}
	}

	public function startTimeZone(): string {
		return $this->baseEventStartTimeZone->getName();
	}

	public function endDate(?string $format = null): string {
		if (isset($format)) {
			return $this->baseEventEndDate->format($format);
		} else {
			return $this->baseEventEndDate->format('Y-m-d');
		}
	}

	public function endTime(?string $format = null): string {
		if (isset($format)) {
			return $this->baseEventEndDate->format($format);
		} else {
			return $this->baseEventEndDate->format('H:ia');
		}
	}

	public function endTimeZone(): string {
		return $this->baseEventEndTimeZone->getName();
	}

	public function isAllDay(): string {
		return $this->frequency;
	}

	public function recurring(): bool {
		return ($this->rruleIterator || $this->rdateIterator);
	}

	public function recurringPattern(): string {
		return ($this->rruleIterator->isRelative()) ? 'R' : 'A';
	}

	public function recurringPrecision(): string | null {
		return $this->rruleIterator->precision();
	}

	public function recurringInterval(): int | null {
		return $this->rruleIterator->interval();
	}

	public function recurringConcludes(): \DateTime | null {
		return $this->rruleIterator->concludes();
	}

	public function recurringConcludesAfter(): int | null {
		return $this->rruleIterator->concludesAfter();
	}

	public function recurringConcludesOn(): \DateTime | null {
		return  $this->rruleIterator->concludesOn();
	}

	public function recurringDaysOfWeek(): array | null {
		return $this->rruleIterator->daysOfWeek();
	}

	public function recurringDaysOfWeekNamed(): array {
		// extract day(s) of the month
		$days = $this->rruleIterator->daysOfWeek();
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

	public function recurringDaysOfMonth(): array {
		return $this->rruleIterator->daysOfMonth();
	}

	public function recurringDaysOfYear(): array {
		return $this->rruleIterator->daysOfYear();
	}

	public function recurringWeeksOfMonth(): array {
		return $this->rruleIterator->weeksOfMonth();
	}

	public function recurringWeeksOfYear(): array {
		return $this->rruleIterator->weeksOfYear();
	}

	public function recurringMonthsOfYear(): array {
		return $this->rruleIterator->monthsOfYear();
	}

	public function recurringMonthsOfYearNamed(): array {
		// extract months of the year
		$months = $this->rruleIterator->monthsOfYear();
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

	public function recurringRelativePosition(): array {
		return $this->bySetPos;
	}

	public function recurringRelativePositionNamed(): array {
		// extract relative position(S)
		$days = $this->bySetPos;
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

	public function recurrenceDate(): DateTimeInterface {
		if ($this->recurrenceCurrentDate) {
			return DateTime::createFromImmutable($this->recurrenceCurrentDate);
		}
	}

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
		$this->recurrenceDate = clone $this->baseEventStartDate;
	}

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
		// if the nextDate is part of exrule or exdate find another date
		if (isset($nextOccurrenceDate) && isset($nextExceptionDate) && $nextOccurrenceDate == $this->nextExceptionDate) {
			$this->recurrenceCurrentDate = $nextOccurrenceDate;
			$this->recurrenceAdvance();
		} else {
			$this->recurrenceCurrentDate = $nextOccurrenceDate;
		}
	}

	public function recurrenceAdvanceTo(DateTimeInterface $dt): void {
		while ($this->recurrenceCurrentDate !== null && $this->recurrenceCurrentDate < $dt) {
			$this->recurrenceAdvance();
		}
	}

}
