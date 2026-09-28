<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

use DateTime;

class EventReaderRDate extends \Sabre\VObject\Recur\RDateIterator {

	public function concludes(): ?DateTime {
		return $this->concludesOn();
	}

	public function concludesAfter(): ?int {
		return !empty($this->dates) ? count($this->dates) : null;
	}

	public function concludesOn(): ?DateTime {
		if (count($this->dates) > 0) {
			return new DateTime(
				$this->dates[array_key_last($this->dates)],
				$this->startDate->getTimezone()
			);
		}

		return null;
	}

}
