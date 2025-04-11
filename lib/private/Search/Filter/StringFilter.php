<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Search\Filter;

use InvalidArgumentException;
use OCP\Search\IFilter;

class StringFilter implements IFilter {
	public function __construct(
		private string $value,
	) {
		if ($value === '') {
			throw new InvalidArgumentException('String filter can’t be empty');
		}
	}

	public function get(): string {
		return $this->value;
	}
}
