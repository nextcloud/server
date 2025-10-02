<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Search;

use OCP\Search\IFilter;
use OCP\Search\IFilterCollection;
use OCP\Search\ISearchQuery;

class SearchQuery implements ISearchQuery {
	public const LIMIT_DEFAULT = 5;

	/**
	 * @param string[] $params Request query
	 * @param string[] $routeParameters
	 */
	public function __construct(
		private IFilterCollection $filters,
		private int $sortOrder = ISearchQuery::SORT_DATE_DESC,
		private int $limit = self::LIMIT_DEFAULT,
		private int|string|null $cursor = null,
		private string $route = '',
		private array $routeParameters = [],
		private ?int $offset = null,
	) {
	}

	public function getTerm(): string {
		return $this->getFilter('term')?->get() ?? '';
	}

	public function getFilter(string $name): ?IFilter {
		return $this->filters->has($name)
			? $this->filters->get($name)
			: null;
	}

	public function getFilters(): IFilterCollection {
		return $this->filters;
	}

	public function getSortOrder(): int {
		return $this->sortOrder;
	}

	public function getLimit(): int {
		return $this->limit;
	}

	public function getCursor(): int|string|null {
		return $this->cursor;
	}

	public function getOffset(): ?int {
		return $this->offset;
	}

	public function getEffectiveOffset(): int {
		// Prefer explicit offset, fall back to cursor if numeric, otherwise 0
		if ($this->offset !== null) {
			return $this->offset;
		}
		if (is_numeric($this->cursor)) {
			return (int)$this->cursor;
		}
		return 0;
	}

	public function getRoute(): string {
		return $this->route;
	}

	public function getRouteParameters(): array {
		return $this->routeParameters;
	}
}
