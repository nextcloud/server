<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCP\Group\Backend;

use OC\User\DisplayNameCache;
use OC\User\LazyUser;
use OCP\GroupInterface;
use OCP\IUserManager;

/**
 * @since 14.0.0
 */
abstract class ABackend implements GroupInterface {

	/**
	 * @deprecated 14.0.0
	 *
	 * @param int $actions The action to check for
	 * @return bool
	 */
	public function implementsActions($actions): bool {
		$implements = 0;

		if ($this instanceof IAddToGroupBackend) {
			$implements |= GroupInterface::ADD_TO_GROUP;
		}
		if ($this instanceof ICountUsersBackend) {
			$implements |= GroupInterface::COUNT_USERS;
		}
		if ($this instanceof ICreateGroupBackend) {
			$implements |= GroupInterface::CREATE_GROUP;
		}
		if ($this instanceof IDeleteGroupBackend) {
			$implements |= GroupInterface::DELETE_GROUP;
		}
		if ($this instanceof IGroupDetailsBackend) {
			$implements |= GroupInterface::GROUP_DETAILS;
		}
		if ($this instanceof IIsAdminBackend) {
			$implements |= GroupInterface::IS_ADMIN;
		}
		if ($this instanceof IRemoveFromGroupBackend) {
			$implements |= GroupInterface::REMOVE_FROM_GOUP;
		}

		return (bool)($actions & $implements);
	}

	public function searchInGroup(string $gid, string $search = '', int $limit = -1, int $offset = 0): array {
		// Default implementation for compatibility reasons
		$displayNameCache = \OC::$server->get(DisplayNameCache::class);
		$userManager = \OC::$server->get(IUserManager::class);
		$users = [];
		foreach ($this->usersInGroup($gid, $search, $limit, $offset) as $userId) {
			$users[$userId] = new LazyUser($userId, $displayNameCache, $userManager);
		}
		return $users;
	}
}
