<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
				stripos($entry['name'], $term) === false
				&& stripos($entry['id'], $term) === false
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
