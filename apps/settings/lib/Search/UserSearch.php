<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Search;

use OCP\IL10N;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;

class UserSearch implements IProvider {
	public function __construct(
		private IL10N $l,
	) {
	}

	public function getId(): string {
		return 'users';
	}

	public function getName(): string {
		return $this->l->t('Users');
	}

	public function getOrder(string $route, array $routeParameters): ?int {
		return str_starts_with($route, 'settings.Users.usersList')
			? 300
			: null;
	}

	public function search(IUser $user, ISearchQuery $query): SearchResult {
		return SearchResult::complete($this->l->t('Users'), []);
	}
}
