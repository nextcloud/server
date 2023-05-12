<?php

declare(strict_types=1);

/**
 * @copyright Copyright 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 * @author Robin Appelman <robin@icewind.nl>
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
namespace OC\User;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IUserManager;
use OCP\User\Events\UserChangedEvent;

/**
 * Class that cache the relation UserId -> Display name
 *
 * This saves fetching the user from a user backend and later on fetching
 * their preferences. It's generally not an issue if this data is slightly
 * outdated.
 */
class DisplayNameCache implements IEventListener {
	private array $cache = [];
	private ICache $memCache;
	private IUserManager $userManager;

	public function __construct(ICacheFactory $cacheFactory, IUserManager $userManager) {
		$this->memCache = $cacheFactory->createDistributed('displayNameMappingCache');
		$this->userManager = $userManager;
	}

	public function getDisplayName(string $userId): ?string {
		if (isset($this->cache[$userId])) {
			return $this->cache[$userId];
		}
		$displayName = $this->memCache->get($userId);
		if ($displayName) {
			$this->cache[$userId] = $displayName;
			return $displayName;
		}

		$user = $this->userManager->get($userId);
		if ($user) {
			$displayName = $user->getDisplayName();
		} else {
			$displayName = null;
		}
		$this->cache[$userId] = $displayName;
		$this->memCache->set($userId, $displayName, 60 * 10); // 10 minutes

		return $displayName;
	}

	public function clear(): void {
		$this->cache = [];
		$this->memCache->clear();
	}

	public function handle(Event $event): void {
		if ($event instanceof UserChangedEvent && $event->getFeature() === 'displayName') {
			$userId = $event->getUser()->getUID();
			$newDisplayName = $event->getValue();
			$this->cache[$userId] = $newDisplayName;
			$this->memCache->set($userId, $newDisplayName, 60 * 10); // 10 minutes
		}
	}
}
