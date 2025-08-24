<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\User;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IUser;
use OCP\IUserManager;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserDeletedEvent;

/**
 * Class that cache the relation UserId -> Display name
 *
 * This saves fetching the user from a user backend and later on fetching
 * their preferences. It's generally not an issue if this data is slightly
 * outdated.
 * @template-implements IEventListener<UserChangedEvent|UserDeletedEvent>
 */
class DisplayNameCache implements IEventListener {
	private const CACHE_TTL = 24 * 60 * 60; // 1 day

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

		if (strlen($userId) > IUser::MAX_USERID_LENGTH) {
			return null;
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
		$this->memCache->set($userId, $displayName, self::CACHE_TTL);

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
			$this->memCache->set($userId, $newDisplayName, self::CACHE_TTL);
		}
		if ($event instanceof UserDeletedEvent) {
			$userId = $event->getUser()->getUID();
			unset($this->cache[$userId]);
			$this->memCache->remove($userId);
		}
	}
}
