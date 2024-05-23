<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Calendar;

use OCP\Calendar\ICalendarQuery;

class CalendarQuery implements ICalendarQuery {
	public array $searchProperties = [];

	private ?string $searchPattern = null;

	private array $options = [
		'types' => [],
	];

	private ?int $offset = null;

	private ?int $limit = null;

	/** @var string[] */
	private array $calendarUris = [];

	public function __construct(
		private string $principalUri,
	) {
	}

	public function getPrincipalUri(): string {
		return $this->principalUri;
	}

	public function setPrincipalUri(string $principalUri): void {
		$this->principalUri = $principalUri;
	}

	public function setSearchPattern(string $pattern): void {
		$this->searchPattern = $pattern;
	}

	public function getSearchPattern(): ?string {
		return $this->searchPattern;
	}

	public function addSearchProperty(string $value): void {
		$this->searchProperties[] = $value;
	}

	public function getSearchProperties(): array {
		return $this->searchProperties;
	}

	public function addSearchCalendar(string $calendarUri): void {
		$this->calendarUris[] = $calendarUri;
	}

	/**
	 * @return string[]
	 */
	public function getCalendarUris(): array {
		return $this->calendarUris;
	}

	public function getLimit(): ?int {
		return $this->limit;
	}

	public function setLimit(int $limit): void {
		$this->limit = $limit;
	}

	public function getOffset(): ?int {
		return $this->offset;
	}

	public function setOffset(int $offset): void {
		$this->offset = $offset;
	}

	public function addType(string $value): void {
		$this->options['types'][] = $value;
	}

	public function setTimerangeStart(\DateTimeImmutable $startTime): void {
		$this->options['timerange']['start'] = $startTime;
	}

	public function setTimerangeEnd(\DateTimeImmutable $endTime): void {
		$this->options['timerange']['end'] = $endTime;
	}

	public function getOptions(): array {
		return $this->options;
	}
}
