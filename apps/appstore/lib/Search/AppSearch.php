<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Appstore\Search;

use OCA\Appstore\AppInfo\Application;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

final readonly class AppSearch implements IProvider {
	public function __construct(
		private INavigationManager $navigationManager,
		private IL10N $l,
	) {
	}

	#[\Override]
	public function getId(): string {
		return Application::APP_ID;
	}

	#[\Override]
	public function getName(): string {
		return $this->l->t('Apps');
	}

	#[\Override]
	public function getOrder(string $route, array $routeParameters): int {
		return $route === 'appstore.Page.viewApps' ? -50 : 100;
	}

	#[\Override]
	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$entries = $this->navigationManager->getAll('all');

		$searchTitle = $this->l->t('Apps');
		$term = (string)$query->getFilter('term')?->get();
		if ($term === '') {
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
