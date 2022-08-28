<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
		private	array $routeParameters = [],
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

	public function getRoute(): string {
		return $this->route;
	}

	public function getRouteParameters(): array {
		return $this->routeParameters;
	}
}
