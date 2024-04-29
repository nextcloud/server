<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Stephan Orbaugh <stephan.orbaugh@nextcloud.com>
 *
 * @author Stephan Orbaugh <stephan.orbaugh@nextcloud.com>
 * @author Benjamin Gaussorgues <benjamin.gaussorgues@nextcloud.com>
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
