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
	private ?array $organizer = null;
	private array $attendees = [];

	public function __construct(
		private readonly string $uid,
		private readonly ITimeFactory $timeFactory,
	) {
	}

	public function setStartDate(DateTimeInterface $start): ICalendarEventBuilder {
		$this->startDate = $start;
		return $this;
	}

	public function setEndDate(DateTimeInterface $end): ICalendarEventBuilder {
		$this->endDate = $end;
		return $this;
	}

	public function setSummary(string $summary): ICalendarEventBuilder {
		$this->summary = $summary;
		return $this;
	}

	public function setDescription(string $description): ICalendarEventBuilder {
		$this->description = $description;
		return $this;
	}

	public function setLocation(string $location): ICalendarEventBuilder {
		$this->location = $location;
		return $this;
	}

	public function setOrganizer(string $email, ?string $commonName = null): ICalendarEventBuilder {
		$this->organizer = [$email, $commonName];
		return $this;
	}

	public function addAttendee(string $email, ?string $commonName = null): ICalendarEventBuilder {
		$this->attendees[] = [$email, $commonName];
		return $this;
	}

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
		}
		$vevent->add($name, $email, $params);
	}
}
