<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCA\Settings\Search;

use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

class AppSearch implements IProvider {
	public function __construct(
		protected INavigationManager $navigationManager,
		protected IL10N $l,
	) {
	}

	public function getId(): string {
		return 'settings_apps';
	}

	public function getName(): string {
		return $this->l->t('Apps');
	}

	public function getOrder(string $route, array $routeParameters): int {
		return $route === 'settings.AppSettings.viewApps' ? -50 : 100;
	}

	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$entries = $this->navigationManager->getAll('all');

		$searchTitle = $this->l->t('Apps');
		$term = $query->getFilter('term')?->get();
		if (empty($term)) {
			return SearchResult::complete($searchTitle, []);
		}

		$result = [];
		foreach ($entries as $entry) {
			if (
				stripos($entry['name'], $term) === false &&
				stripos($entry['id'], $term) === false
			) {
				continue;
			}

			if (str_starts_with($query->getRoute(), $entry['id'] . '.')) {
				// Skip the current app, unlikely this is intended
				continue;
			}

			if ($entry['href'] === '') {
				// Nothing we can open, so ignore
				continue;
			}

			$result[] = new SearchResultEntry(
				'',
				$entry['name'],
				'',
				$entry['href'],
				'icon-confirm'
			);
		}

		return SearchResult::complete($searchTitle, $result);
	}
}
