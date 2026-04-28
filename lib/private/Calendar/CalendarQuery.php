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

	#[\Override]
	public function setSearchPattern(string $pattern): void {
		$this->searchPattern = $pattern;
	}

	public function getSearchPattern(): ?string {
		return $this->searchPattern;
	}

	#[\Override]
	public function addSearchProperty(string $value): void {
		$this->searchProperties[] = $value;
	}

	public function getSearchProperties(): array {
		return $this->searchProperties;
	}

	#[\Override]
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

	#[\Override]
	public function setLimit(int $limit): void {
		$this->limit = $limit;
	}

	public function getOffset(): ?int {
		return $this->offset;
	}

	#[\Override]
	public function setOffset(int $offset): void {
		$this->offset = $offset;
	}

	#[\Override]
	public function addType(string $value): void {
		$this->options['types'][] = $value;
	}

	#[\Override]
	public function setTimerangeStart(\DateTimeImmutable $startTime): void {
		$this->options['timerange']['start'] = $startTime;
	}

	#[\Override]
	public function setTimerangeEnd(\DateTimeImmutable $endTime): void {
		$this->options['timerange']['end'] = $endTime;
	}

	public function getOptions(): array {
		return $this->options;
	}
}
