<?php

declare(strict_types=1);

/**
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 * @author Anna Larch <anna.larch@gmx.net>
 *
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


namespace OC\Group;

use OCP\Cache\CappedMemoryCache;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupChangedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IGroupManager;

/**
 * Class that cache the relation Group ID -> Display name
 *
 * This saves fetching the group from the backend for "just" the display name
 */
class DisplayNameCache implements IEventListener {
	private CappedMemoryCache $cache;
	private ICache $memCache;
	private IGroupManager $groupManager;

	public function __construct(ICacheFactory $cacheFactory, IGroupManager $groupManager) {
		$this->cache = new CappedMemoryCache();
		$this->memCache = $cacheFactory->createDistributed('groupDisplayNameMappingCache');
		$this->groupManager = $groupManager;
	}

	public function getDisplayName(string $groupId): ?string {
		if (isset($this->cache[$groupId])) {
			return $this->cache[$groupId];
		}
		$displayName = $this->memCache->get($groupId);
		if ($displayName) {
			$this->cache[$groupId] = $displayName;
			return $displayName;
		}

		$group = $this->groupManager->get($groupId);
		if ($group) {
			$displayName = $group->getDisplayName();
		} else {
			$displayName = null;
		}
		$this->cache[$groupId] = $displayName;
		$this->memCache->set($groupId, $displayName, 60 * 10); // 10 minutes

		return $displayName;
	}

	public function clear(): void {
		$this->cache = new CappedMemoryCache();
		$this->memCache->clear();
	}

	public function handle(Event $event): void {
		if ($event instanceof GroupChangedEvent && $event->getFeature() === 'displayName') {
			$groupId = $event->getGroup()->getGID();
			$newDisplayName = $event->getValue();
			$this->cache[$groupId] = $newDisplayName;
			$this->memCache->set($groupId, $newDisplayName, 60 * 10); // 10 minutes
		}
		if ($event instanceof GroupDeletedEvent) {
			$groupId = $event->getGroup()->getGID();
			unset($this->cache[$groupId]);
			$this->memCache->remove($groupId);
		}
	}
}
