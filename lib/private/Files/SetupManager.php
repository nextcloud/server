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

use OC\Files\Config\MountProviderCollection;
use OC\Files\Mount\MountPoint;
use OC\Files\ObjectStore\HomeObjectStoreStorage;
use OC\Files\Storage\Common;
use OC\Files\Storage\Home;
use OC\Files\Storage\Storage;
use OC\Files\Storage\Wrapper\Availability;
use OC\Files\Storage\Wrapper\Encoding;
use OC\Files\Storage\Wrapper\PermissionsMask;
use OC\Files\Storage\Wrapper\Quota;
use OC\Lockdown\Filesystem\NullStorage;
use OC_App;
use OC_Hook;
use OC_Util;
use OCP\Constants;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Events\Node\FilesystemTornDownEvent;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Lockdown\ILockdownManager;

class SetupManager {
	private bool $rootSetup = false;
	private IEventLogger $eventLogger;
	private MountProviderCollection $mountProviderCollection;
	private IMountManager $mountManager;
	private IUserManager $userManager;
	private array $setupUsers = [];
	private IEventDispatcher $eventDispatcher;
	private IUserMountCache $userMountCache;
	private ILockdownManager $lockdownManager;
	private IUserSession $userSession;
	private bool $listeningForProviders;

	public function __construct(
		IEventLogger $eventLogger,
		MountProviderCollection $mountProviderCollection,
		IMountManager $mountManager,
		IUserManager $userManager,
		IEventDispatcher $eventDispatcher,
		IUserMountCache $userMountCache,
		ILockdownManager $lockdownManager,
		IUserSession $userSession
	) {
		$this->eventLogger = $eventLogger;
		$this->mountProviderCollection = $mountProviderCollection;
		$this->mountManager = $mountManager;
		$this->userManager = $userManager;
		$this->eventDispatcher = $eventDispatcher;
		$this->userMountCache = $userMountCache;
		$this->lockdownManager = $lockdownManager;
		$this->userSession = $userSession;
		$this->listeningForProviders = false;
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
			if ($storage->instanceOfStorage(HomeObjectStoreStorage::class) || $storage->instanceOfStorage(Home::class)) {
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

	/**
	 * Setup the full filesystem for the specified user
	 */
	public function setupForUser(IUser $user): void {
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

		if ($this->lockdownManager->canAccessFilesystem()) {
			// home mounts are handled separate since we need to ensure this is mounted before we call the other mount providers
			$homeMount = $this->mountProviderCollection->getHomeMountForUser($user);
			$this->mountManager->addMount($homeMount);

			if ($homeMount->getStorageRootId() === -1) {
				$homeMount->getStorage()->mkdir('');
				$homeMount->getStorage()->getScanner()->scan('');
			}

			// Chance to mount for other storages
			$mounts = $this->mountProviderCollection->addMountForUser($user, $this->mountManager);
			$mounts[] = $homeMount;
			$this->userMountCache->registerMounts($user, $mounts);

			$this->listenForNewMountProviders();
		} else {
			$this->mountManager->addMount(new MountPoint(
				new NullStorage([]),
				'/' . $user->getUID()
			));
			$this->mountManager->addMount(new MountPoint(
				new NullStorage([]),
				'/' . $user->getUID() . '/files'
			));
		}
		\OC_Hook::emit('OC_Filesystem', 'post_initMountPoints', ['user' => $user->getUID()]);

		OC_Hook::emit('OC_Filesystem', 'setup', ['user' => $user->getUID(), 'user_dir' => $userDir]);

		$this->eventLogger->end('setup_fs');
	}

	/**
	 * Set up the root filesystem
	 */
	public function setupRoot(): void {
		//setting up the filesystem twice can only lead to trouble
		if ($this->rootSetup) {
			return;
		}
		$this->rootSetup = true;

		$this->eventLogger->start('setup_root_fs', 'Setup root filesystem');

		// load all filesystem apps before, so no setup-hook gets lost
		OC_App::loadApps(['filesystem']);
		$prevLogging = Filesystem::logWarningWhenAddingStorageWrapper(false);

		$this->setupBuiltinWrappers();

		Filesystem::logWarningWhenAddingStorageWrapper($prevLogging);

		$rootMounts = $this->mountProviderCollection->getRootMounts();
		foreach ($rootMounts as $rootMountProvider) {
			$this->mountManager->addMount($rootMountProvider);
		}

		$this->eventLogger->end('setup_root_fs');
	}

	/**
	 * Set up the filesystem for the specified path
	 */
	public function setupForPath(string $path): void {
		if (substr_count($path, '/') < 2) {
			if ($user = $this->userSession->getUser()) {
				$this->setupForUser($user);
			} else {
				$this->setupRoot();
			}
			return;
		} elseif (strpos($path, '/appdata_' . \OC_Util::getInstanceId()) === 0 || strpos($path, '/files_external/') === 0) {
			$this->setupRoot();
			return;
		} else {
			[, $userId] = explode('/', $path);
		}

		$user = $this->userManager->get($userId);

		if (!$user) {
			$this->setupRoot();
			return;
		}

		$this->setupForUser($user);
	}

	public function tearDown() {
		$this->setupUsers = [];
		$this->rootSetup = false;
		$this->mountManager->clear();
		$this->eventDispatcher->dispatchTyped(new FilesystemTornDownEvent());
	}

	/**
	 * Get mounts from mount providers that are registered after setup
	 */
	private function listenForNewMountProviders() {
		if (!$this->listeningForProviders) {
			$this->listeningForProviders = true;
			$this->mountProviderCollection->listen('\OC\Files\Config', 'registerMountProvider', function (IMountProvider $provider) {
				foreach ($this->setupUsers as $userId) {
					$user = $this->userManager->get($userId);
					if ($user) {
						$mounts = $provider->getMountsForUser($user, Filesystem::getLoader());
						array_walk($mounts, [$this->mountManager, 'addMount']);
					}
				}
			});
		}
	}
}
