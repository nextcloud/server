<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Search;

use Generator;
use OCP\Search\IFilter;
use OCP\Search\IFilterCollection;

/**
 * Interface for search filters
 *
 * @since 28.0.0
 */
class FilterCollection implements IFilterCollection {
	/**
	 * @var IFilter[]
	 */
	private array $filters;

	public function __construct(IFilter ...$filters) {
		$this->filters = $filters;
	}

	public function has(string $name): bool {
		return isset($this->filters[$name]);
	}

	public function get(string $name): ?IFilter {
		return $this->filters[$name] ?? null;
	}

	public function getIterator(): Generator {
		foreach ($this->filters as $k => $v) {
			yield $k => $v;
		}
	}

	public function count(): int {
		return count($this->filters);
	}
}
