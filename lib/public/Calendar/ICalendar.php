<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

/**
 * Interface ICalendar
 *
 * @since 13.0.0
 */
interface ICalendar {
	/**
	 * @return string defining the technical unique key
	 * @since 13.0.0
	 */
	public function getKey(): string;

	/**
	 * In comparison to getKey() this function returns a unique uri within the scope of the principal
	 * @since 24.0.0
	 */
	public function getUri(): string;

	/**
	 * In comparison to getKey() this function returns a human readable (maybe translated) name
	 * @return null|string
	 * @since 13.0.0
	 */
	public function getDisplayName(): ?string;

	/**
	 * Calendar color
	 * @return null|string
	 * @since 13.0.0
	 */
	public function getDisplayColor(): ?string;

	/**
	 * @param string $pattern which should match within the $searchProperties
	 * @param array $searchProperties defines the properties within the query pattern should match
	 * @param array $options - optional parameters:
	 *                       ['timerange' => ['start' => new DateTime(...), 'end' => new DateTime(...)]]
	 * @param int|null $limit - limit number of search results
	 * @param int|null $offset - offset for paging of search results
	 * @return array an array of events/journals/todos which are arrays of key-value-pairs. the events are sorted by start date (closest first, furthest last)
	 * @since 13.0.0
	 */
	public function search(string $pattern, array $searchProperties = [], array $options = [], ?int $limit = null, ?int $offset = null): array;

	/**
	 * @return int build up using {@see \OCP\Constants}
	 * @since 13.0.0
	 */
	public function getPermissions(): int;

	/**
	 * Indicates whether the calendar is in the trash bin
	 *
	 * @since 26.0.0
	 */
	public function isDeleted(): bool;
}
