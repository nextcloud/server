<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Search\Filter;

use InvalidArgumentException;
use OCP\Search\IFilter;

class StringsFilter implements IFilter {
	/**
	 * @var string[]
	 */
	private array $values;

	public function __construct(string ...$values) {
		$this->values = array_unique(array_filter($values));
		if (empty($this->values)) {
			throw new InvalidArgumentException('Strings filter can’t be empty');
		}
	}

	/**
	 * @return string[]
	 */
	public function get(): array {
		return $this->values;
	}
}
