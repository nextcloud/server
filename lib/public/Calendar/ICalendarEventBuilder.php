<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Calendar;

use DateTimeInterface;
use InvalidArgumentException;
use OCP\Calendar\Exceptions\CalendarException;

/**
 * The calendar event builder can be used to conveniently build a calendar event and then serialize
 * it to a ICS string. The ICS string can be submitted to calendar instances implementing the
 * {@see \OCP\Calendar\ICreateFromString} interface.
 *
 * Also note this class can not be injected directly with dependency injection.
 * Instead, inject {@see \OCP\Calendar\IManager} and use
 * {@see \OCP\Calendar\IManager::createEventBuilder()} afterwards.
 *
 * All setters return self to allow chaining method calls.
 *
 * @since 31.0.0
 */
interface ICalendarEventBuilder {
	/**
	 * Set the start date, time and time zone.
	 * This property is required!
	 *
	 * @since 31.0.0
	 */
	public function setStartDate(DateTimeInterface $start): self;

	/**
	 * Set the end date, time and time zone.
	 * This property is required!
	 *
	 * @since 31.0.0
	 */
	public function setEndDate(DateTimeInterface $end): self;

	/**
	 * Set the event summary or title.
	 * This property is required!
	 *
	 * @since 31.0.0
	 */
	public function setSummary(string $summary): self;

	/**
	 * Set the event description.
	 *
	 * @since 31.0.0
	 */
	public function setDescription(string $description): self;

	/**
	 * Set the event location. It can either be a physical address or a URL.
	 *
	 * @since 31.0.0
	 */
	public function setLocation(string $location): self;

	/**
	 * Set the event organizer.
	 * This property is required if attendees are added!
	 *
	 * The "mailto:" prefix is optional and will be added automatically if it is missing.
	 *
	 * @since 31.0.0
	 */
	public function setOrganizer(string $email, ?string $commonName = null): self;

	/**
	 * Add a new attendee to the event.
	 * Adding at least one attendee requires also setting the organizer!
	 *
	 * The "mailto:" prefix is optional and will be added automatically if it is missing.
	 *
	 * @since 31.0.0
	 */
	public function addAttendee(string $email, ?string $commonName = null): self;

	/**
	 * Serialize the built event to an ICS string if all required properties set.
	 *
	 * @since 31.0.0
	 *
	 * @return string The serialized ICS string
	 *
	 * @throws InvalidArgumentException If required properties were not set
	 */
	public function toIcs(): string;

	/**
	 * Create the event in the given calendar.
	 *
	 * @since 31.0.0
	 *
	 * @return string The filename of the created event
	 *
	 * @throws InvalidArgumentException If required properties were not set
	 * @throws CalendarException If writing the event to the calendar fails
	 */
	public function createInCalendar(ICreateFromString $calendar): string;
}
