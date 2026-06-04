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
	) {
	}

	#[\Override]
	public function getTerm(): string {
		return $this->getFilter('term')?->get() ?? '';
	}

	#[\Override]
	public function getFilter(string $name): ?IFilter {
		return $this->filters->has($name)
			? $this->filters->get($name)
			: null;
	}

	#[\Override]
	public function getFilters(): IFilterCollection {
		return $this->filters;
	}

	#[\Override]
	public function getSortOrder(): int {
		return $this->sortOrder;
	}

	#[\Override]
	public function getLimit(): int {
		return $this->limit;
	}

	#[\Override]
	public function getCursor(): int|string|null {
		return $this->cursor;
	}

	#[\Override]
	public function getRoute(): string {
		return $this->route;
	}

	#[\Override]
	public function getRouteParameters(): array {
		return $this->routeParameters;
	}
}
