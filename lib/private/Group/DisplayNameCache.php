<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
 * @template-implements IEventListener<GroupChangedEvent|GroupDeletedEvent>
 */
class DisplayNameCache implements IEventListener {
	private CappedMemoryCache $cache;
	private ICache $memCache;

	public function __construct(
		ICacheFactory $cacheFactory,
		private IGroupManager $groupManager,
	) {
		$this->cache = new CappedMemoryCache();
		$this->memCache = $cacheFactory->createDistributed('groupDisplayNameMappingCache');
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

	/**
	 * @param list<string> $groupIds
	 * @return array<string, ?string>
	 */
	public function getDisplayNames(array $groupIds): array {
		$result = [];
		$missing = [];
		foreach ($groupIds as $groupId) {
			if (isset($this->cache[$groupId])) {
				$result[$groupId] = $this->cache[$groupId];
			} else {
				$displayName = $this->memCache->get($groupId);
				if ($displayName) {
					$this->cache[$groupId] = $displayName;
					$result[$groupId] = $displayName;
				} else {
					$missing[] = $groupId;
				}
			}
		}

		/** @var Manager $groupManager */
		$groupManager = $this->groupManager;
		$groups = $groupManager->getGroupsObjects($missing);
		$stillMissingGroups = array_diff($missing, array_keys($groups));
		foreach ($groups as $groupId => $group) {
			$displayName = $group->getDisplayName();
			$this->cache[$groupId] = $displayName;
			$this->memCache->set($groupId, $displayName, 60 * 10); // 10 minutes
			$result[$groupId] = $displayName;
		}

		foreach ($stillMissingGroups as $groupId) {
			$result[$groupId] = null;
		}

		return $result;
	}

	public function clear(): void {
		$this->cache = new CappedMemoryCache();
		$this->memCache->clear();
	}

	#[\Override]
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
