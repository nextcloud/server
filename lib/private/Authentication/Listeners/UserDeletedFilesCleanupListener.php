<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Listeners;

use OC\Files\Cache\Cache;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Storage\IStorage;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\UserDeletedEvent;

/** @template-implements IEventListener<BeforeUserDeletedEvent|UserDeletedEvent> */
class UserDeletedFilesCleanupListener implements IEventListener {
	/** @var array<string,IStorage> */
	private $homeStorageCache = [];

	/** @var IMountProviderCollection */
	private $mountProviderCollection;

	public function __construct(IMountProviderCollection $mountProviderCollection) {
		$this->mountProviderCollection = $mountProviderCollection;
	}

	public function handle(Event $event): void {
		// since we can't reliably get the user home storage after the user is deleted
		// but the user deletion might get canceled during the before event
		// we only cache the user home storage during the before event and then do the
		// action deletion during the after event

		if ($event instanceof BeforeUserDeletedEvent) {
			$userHome = $this->mountProviderCollection->getHomeMountForUser($event->getUser());
			$storage = $userHome->getStorage();
			if (!$storage) {
				throw new \Exception("Account has no home storage");
			}

			// remove all wrappers, so we do the delete directly on the home storage bypassing any wrapper
			while ($storage->instanceOfStorage(Wrapper::class)) {
				/** @var Wrapper $storage */
				$storage = $storage->getWrapperStorage();
			}

			$this->homeStorageCache[$event->getUser()->getUID()] = $storage;
		}
		if ($event instanceof UserDeletedEvent) {
			if (!isset($this->homeStorageCache[$event->getUser()->getUID()])) {
				throw new \Exception("UserDeletedEvent fired without matching BeforeUserDeletedEvent");
			}
			$storage = $this->homeStorageCache[$event->getUser()->getUID()];
			$cache = $storage->getCache();
			$storage->rmdir('');
			if ($cache instanceof Cache) {
				$cache->clear();
			} else {
				throw new \Exception("Home storage has invalid cache");
			}
		}
	}
}
