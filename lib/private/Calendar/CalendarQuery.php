<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
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
namespace OC\Calendar;

use OCP\Calendar\ICalendarQuery;

class CalendarQuery implements ICalendarQuery {
	/** @var string */
	private $principalUri;

	/** @var array */
	public $searchProperties;

	/** @var string|null */
	private $searchPattern;

	/** @var array */
	private $options;

	/** @var int|null */
	private $offset;

	/** @var int|null */
	private $limit;

	/** @var string[] */
	private $calendarUris = [];

	public function __construct(string $principalUri) {
		$this->principalUri = $principalUri;
		$this->searchProperties = [];
		$this->options = [
			'types' => [],
		];
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
