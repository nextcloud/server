<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Search\Filter;

use DateTimeImmutable;
use OCP\Search\IFilter;

class DateTimeFilter implements IFilter {
	private DateTimeImmutable $value;

	public function __construct(string $value) {
		if (filter_var($value, FILTER_VALIDATE_INT)) {
			$value = '@' . $value;
		}

		$this->value = new DateTimeImmutable($value);
	}

	public function get(): DateTimeImmutable {
		return $this->value;
	}
}
