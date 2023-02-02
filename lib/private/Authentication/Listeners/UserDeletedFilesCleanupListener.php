<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Robin Appelman <robin@icewind.nl>
 *
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
namespace OC\Authentication\Listeners;

use OC\Files\Cache\Cache;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Storage\IStorage;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\UserDeletedEvent;

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
				throw new \Exception("User has no home storage");
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
