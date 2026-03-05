<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

use DateTime;
use DateTimeInterface;

class EventReaderRRule extends \Sabre\VObject\Recur\RRuleIterator {

	public function precision(): string {
		return $this->frequency;
	}

	public function interval(): int {
		return $this->interval;
	}

	public function concludes(): ?DateTime {
		// evaluate if until value is a date
		if ($this->until instanceof DateTimeInterface) {
			return DateTime::createFromInterface($this->until);
		}
		// evaluate if count value is higher than 0
		if ($this->count > 0) {
			// temporarily store current recurrence date and counter
			$currentReccuranceDate = $this->currentDate;
			$currentCounter = $this->counter;
			// iterate over occurrences until last one (subtract 2 from count for start and end occurrence)
			while ($this->counter <= ($this->count - 2)) {
				$this->next();
			}
			// temporarly store last reccurance date
			$lastReccuranceDate = $this->currentDate;
			// restore current recurrence date and counter
			$this->currentDate = $currentReccuranceDate;
			$this->counter = $currentCounter;
			// return last recurrence date
			return DateTime::createFromInterface($lastReccuranceDate);
		}

		return null;
	}

	public function concludesAfter(): ?int {
		return !empty($this->count) ? $this->count : null;
	}

	public function concludesOn(): ?DateTime {
		return isset($this->until) ? DateTime::createFromInterface($this->until) : null;
	}

	public function daysOfWeek(): array {
		return is_array($this->byDay) ? $this->byDay : [];
	}

	public function daysOfMonth(): array {
		return is_array($this->byMonthDay) ? $this->byMonthDay : [];
	}

	public function daysOfYear(): array {
		return is_array($this->byYearDay) ? $this->byYearDay : [];
	}

	public function weeksOfYear(): array {
		return is_array($this->byWeekNo) ? $this->byWeekNo : [];
	}

	public function monthsOfYear(): array {
		return is_array($this->byMonth) ? $this->byMonth : [];
	}

	public function isRelative(): bool {
		return isset($this->bySetPos);
	}

	public function relativePosition(): array {
		return is_array($this->bySetPos) ? $this->bySetPos : [];
	}

}
