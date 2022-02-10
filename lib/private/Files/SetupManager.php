<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Files;

use OC\Files\Storage\Common;
use OC\Files\Storage\Storage;
use OC\Files\Storage\Wrapper\Availability;
use OC\Files\Storage\Wrapper\Encoding;
use OC\Files\Storage\Wrapper\PermissionsMask;
use OC\Files\Storage\Wrapper\Quota;
use OC_App;
use OC_Hook;
use OC_Util;
use OCP\Constants;
use OCP\Diagnostics\IEventLogger;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\IHomeStorage;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\IUserSession;

class SetupManager {
	private bool $rootSetup = false;
	private IEventLogger $eventLogger;
	private IMountProviderCollection $mountProviderCollection;
	private IMountManager $mountManager;
	private IUserSession $userSession;
	private array $setupUsers = [];

	public function __construct(
		IEventLogger $eventLogger,
		IMountProviderCollection $mountProviderCollection,
		IMountManager $mountManager,
		IUserSession $userSession
	) {
		$this->eventLogger = $eventLogger;
		$this->mountProviderCollection = $mountProviderCollection;
		$this->mountManager = $mountManager;
		$this->userSession = $userSession;
	}

	private function setupBuiltinWrappers() {
		Filesystem::addStorageWrapper('mount_options', function ($mountPoint, IStorage $storage, IMountPoint $mount) {
			if ($storage->instanceOfStorage(Common::class)) {
				$storage->setMountOptions($mount->getOptions());
			}
			return $storage;
		});

		Filesystem::addStorageWrapper('enable_sharing', function ($mountPoint, IStorage $storage, IMountPoint $mount) {
			if (!$mount->getOption('enable_sharing', true)) {
				return new PermissionsMask([
					'storage' => $storage,
					'mask' => Constants::PERMISSION_ALL - Constants::PERMISSION_SHARE,
				]);
			}
			return $storage;
		});

		// install storage availability wrapper, before most other wrappers
		Filesystem::addStorageWrapper('oc_availability', function ($mountPoint, IStorage $storage) {
			if (!$storage->instanceOfStorage('\OCA\Files_Sharing\SharedStorage') && !$storage->isLocal()) {
				return new Availability(['storage' => $storage]);
			}
			return $storage;
		});

		Filesystem::addStorageWrapper('oc_encoding', function ($mountPoint, IStorage $storage, IMountPoint $mount) {
			if ($mount->getOption('encoding_compatibility', false) && !$storage->instanceOfStorage('\OCA\Files_Sharing\SharedStorage') && !$storage->isLocal()) {
				return new Encoding(['storage' => $storage]);
			}
			return $storage;
		});

		Filesystem::addStorageWrapper('oc_quota', function ($mountPoint, $storage) {
			// set up quota for home storages, even for other users
			// which can happen when using sharing

			/**
			 * @var Storage $storage
			 */
			if ($storage->instanceOfStorage(IHomeStorage::class)) {
				if (is_object($storage->getUser())) {
					$quota = OC_Util::getUserQuota($storage->getUser());
					if ($quota !== \OCP\Files\FileInfo::SPACE_UNLIMITED) {
						return new Quota(['storage' => $storage, 'quota' => $quota, 'root' => 'files']);
					}
				}
			}

			return $storage;
		});

		Filesystem::addStorageWrapper('readonly', function ($mountPoint, IStorage $storage, IMountPoint $mount) {
			/*
			 * Do not allow any operations that modify the storage
			 */
			if ($mount->getOption('readonly', false)) {
				return new PermissionsMask([
					'storage' => $storage,
					'mask' => Constants::PERMISSION_ALL & ~(
							Constants::PERMISSION_UPDATE |
							Constants::PERMISSION_CREATE |
							Constants::PERMISSION_DELETE
						),
				]);
			}
			return $storage;
		});
	}

	public function setupForCurrentUser() {
		$user = $this->userSession->getUser();
		if ($user) {
			$this->setupForUser($user);
		} else {
			$this->setupRoot();
		}
	}

	public function setupForUser(IUser $user) {
		$this->setupRoot();

		if (in_array($user->getUID(), $this->setupUsers, true)) {
			return;
		}
		$this->setupUsers[] = $user->getUID();

		$this->eventLogger->start('setup_fs', 'Setup filesystem');

		$prevLogging = Filesystem::logWarningWhenAddingStorageWrapper(false);

		OC_Hook::emit('OC_Filesystem', 'preSetup', ['user' => $user->getUID()]);

		Filesystem::logWarningWhenAddingStorageWrapper($prevLogging);

		$userDir = '/' . $user->getUID() . '/files';

		Filesystem::init($user, $userDir);

		OC_Hook::emit('OC_Filesystem', 'setup', ['user' => $user->getUID(), 'user_dir' => $userDir]);

		$this->eventLogger->end('setup_fs');
	}

	public function setupRoot() {
		//setting up the filesystem twice can only lead to trouble
		if ($this->rootSetup) {
			return;
		}

		$this->eventLogger->start('setup_root_fs', 'Setup root filesystem');

		// load all filesystem apps before, so no setup-hook gets lost
		OC_App::loadApps(['filesystem']);

		$this->rootSetup = true;
		$prevLogging = Filesystem::logWarningWhenAddingStorageWrapper(false);

		$this->setupBuiltinWrappers();

		Filesystem::logWarningWhenAddingStorageWrapper($prevLogging);

		$rootMountProviders = $this->mountProviderCollection->getRootMounts();
		foreach ($rootMountProviders as $rootMountProvider) {
			$this->mountManager->addMount($rootMountProvider);
		}

		$this->eventLogger->end('setup_root_fs');
	}
}
