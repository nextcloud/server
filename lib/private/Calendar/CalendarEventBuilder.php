<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Calendar;

use DateTimeInterface;
use InvalidArgumentException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\CalendarEventStatus;
use OCP\Calendar\ICalendarEventBuilder;
use OCP\Calendar\ICreateFromString;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

class CalendarEventBuilder implements ICalendarEventBuilder {
	private ?DateTimeInterface $startDate = null;
	private ?DateTimeInterface $endDate = null;
	private ?string $summary = null;
	private ?string $description = null;
	private ?string $location = null;
	private ?CalendarEventStatus $status = null;
	private ?array $organizer = null;
	private array $attendees = [];

	public function __construct(
		private readonly string $uid,
		private readonly ITimeFactory $timeFactory,
	) {
	}

	#[\Override]
	public function setStartDate(DateTimeInterface $start): ICalendarEventBuilder {
		$this->startDate = $start;
		return $this;
	}

	#[\Override]
	public function setEndDate(DateTimeInterface $end): ICalendarEventBuilder {
		$this->endDate = $end;
		return $this;
	}

	#[\Override]
	public function setSummary(string $summary): ICalendarEventBuilder {
		$this->summary = $summary;
		return $this;
	}

	#[\Override]
	public function setDescription(string $description): ICalendarEventBuilder {
		$this->description = $description;
		return $this;
	}

	#[\Override]
	public function setLocation(string $location): ICalendarEventBuilder {
		$this->location = $location;
		return $this;
	}

	#[\Override]
	public function setStatus(CalendarEventStatus $status): static {
		$this->status = $status;
		return $this;
	}

	#[\Override]
	public function setOrganizer(string $email, ?string $commonName = null): ICalendarEventBuilder {
		$this->organizer = [$email, $commonName];
		return $this;
	}

	#[\Override]
	public function addAttendee(string $email, ?string $commonName = null): ICalendarEventBuilder {
		$this->attendees[] = [$email, $commonName];
		return $this;
	}

	#[\Override]
	public function toIcs(): string {
		if ($this->startDate === null) {
			throw new InvalidArgumentException('Event is missing a start date');
		}

		if ($this->endDate === null) {
			throw new InvalidArgumentException('Event is missing an end date');
		}

		if ($this->summary === null) {
			throw new InvalidArgumentException('Event is missing a summary');
		}

		if ($this->organizer === null && $this->attendees !== []) {
			throw new InvalidArgumentException('Event has attendees but is missing an organizer');
		}

		$vcalendar = new VCalendar();
		$props = [
			'UID' => $this->uid,
			'DTSTAMP' => $this->timeFactory->now(),
			'SUMMARY' => $this->summary,
			'DTSTART' => $this->startDate,
			'DTEND' => $this->endDate,
			'STATUS' => $this->status->value,
		];
		if ($this->description !== null) {
			$props['DESCRIPTION'] = $this->description;
		}
		if ($this->location !== null) {
			$props['LOCATION'] = $this->location;
		}
		/** @var VEvent $vevent */
		$vevent = $vcalendar->add('VEVENT', $props);
		if ($this->organizer !== null) {
			self::addAttendeeToVEvent($vevent, 'ORGANIZER', $this->organizer);
		}
		foreach ($this->attendees as $attendee) {
			self::addAttendeeToVEvent($vevent, 'ATTENDEE', $attendee);
		}
		return $vcalendar->serialize();
	}

	#[\Override]
	public function createInCalendar(ICreateFromString $calendar): string {
		$fileName = $this->uid . '.ics';
		$calendar->createFromString($fileName, $this->toIcs());
		return $fileName;
	}

	/**
	 * @param array{0: string, 1: ?string} $tuple A tuple of [$email, $commonName] where $commonName may be null.
	 */
	private static function addAttendeeToVEvent(VEvent $vevent, string $name, array $tuple): void {
		[$email, $cn] = $tuple;
		if (!str_starts_with($email, 'mailto:')) {
			$email = "mailto:$email";
		}
		$params = [];
		if ($cn !== null) {
			$params['CN'] = $cn;
			if ($name === 'ORGANIZER') {
				$params['ROLE'] = 'CHAIR';
				$params['PARTSTAT'] = 'ACCEPTED';
			} else {
				$params['ROLE'] = 'REQ-PARTICIPANT';
				$params['PARTSTAT'] = 'NEEDS-ACTION';
			}
		}
		$vevent->add($name, $email, $params);
	}
}
