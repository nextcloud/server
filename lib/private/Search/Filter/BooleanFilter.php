<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Search\Filter;

use InvalidArgumentException;
use OCP\Search\IFilter;

class BooleanFilter implements IFilter {
	private bool $value;

	public function __construct(string $value) {
		$this->value = match ($value) {
			'true', 'yes', 'y', '1' => true,
			'false', 'no', 'n', '0', '' => false,
			default => throw new InvalidArgumentException('Invalid boolean value ' . $value),
		};
	}

	public function get(): bool {
		return $this->value;
	}
}
