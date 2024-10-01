<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Search\Filter;

use InvalidArgumentException;
use OCP\Search\IFilter;

class IntegerFilter implements IFilter {
	private int $value;

	public function __construct(string $value) {
		$this->value = filter_var($value, FILTER_VALIDATE_INT);
		if ($this->value === false) {
			throw new InvalidArgumentException('Invalid integer value ' . $value);
		}
	}

	public function get(): int {
		return $this->value;
	}
}
