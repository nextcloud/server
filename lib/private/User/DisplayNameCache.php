<?php

declare(strict_types=1);

/**
 * @copyright Copyright 2022 Carl Schwan <carl@carlschwan.eu>
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */


namespace OC\User;

use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IUserManager;

/**
 * Class that cache the relation UserId -> Display name
 *
 * This saves fetching the user from a user backend and later on fetching
 * their preferences. It's generally not an issue if this data is slightly
 * outdated.
 */
class DisplayNameCache {
	private ICache $internalCache;
	private IUserManager $userManager;

	public function __construct(ICacheFactory $cacheFactory, IUserManager $userManager) {
		$this->internalCache = $cacheFactory->createDistributed('displayNameMappingCache');
		$this->userManager = $userManager;
	}

	public function getDisplayName(string $userId) {
		$displayName = $this->internalCache->get($userId);
		if ($displayName) {
			return $displayName;
		}

		$user = $this->userManager->get($userId);
		if ($user) {
			$displayName = $user->getDisplayName();
		} else {
			$displayName = $userId;
		}
		$this->internalCache->set($userId, $displayName, 60 * 10); // 10 minutes

		return $displayName;
	}

	public function clear(): void {
		$this->internalCache->clear();
	}
}
