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
use OC\Files\Mount\HomeMountPoint;
use OC\Files\Mount\MountPoint;
use OC\Files\Storage\Common;
use OC\Files\Storage\Home;
use OC\Files\Storage\Storage;
use OC\Files\Storage\Wrapper\Availability;
use OC\Files\Storage\Wrapper\Encoding;
use OC\Files\Storage\Wrapper\PermissionsMask;
use OC\Files\Storage\Wrapper\Quota;
use OC\Lockdown\Filesystem\NullStorage;
use OC\Share\Share;
use OC\Share20\ShareDisableChecker;
use OC_App;
use OC_Hook;
use OC_Util;
use OCA\Files_External\Config\ConfigAdapter;
use OCA\Files_Sharing\External\Mount;
use OCA\Files_Sharing\ISharedMountPoint;
use OCA\Files_Sharing\SharedMount;
use OCP\Constants;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IHomeMountProvider;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Events\InvalidateMountCacheEvent;
use OCP\Files\Events\Node\FilesystemTornDownEvent;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Lockdown\ILockdownManager;
use OCP\Share\Events\ShareCreatedEvent;
use Psr\Log\LoggerInterface;

class SetupManager {
	private bool $rootSetup = false;
	// List of users for which at least one mount is setup
	private array $setupUsers = [];
	// List of users for which all mounts are setup
	private array $setupUsersComplete = [];
	/** @var array<string, string[]> */
	private array $setupUserMountProviders = [];
	private ICache $cache;
	private bool $listeningForProviders;
	private array $fullSetupRequired = [];
	private bool $setupBuiltinWrappersDone = false;

	public function __construct(
		private IEventLogger $eventLogger,
		private MountProviderCollection $mountProviderCollection,
		private IMountManager $mountManager,
		private IUserManager $userManager,
		private IEventDispatcher $eventDispatcher,
		private IUserMountCache $userMountCache,
		private ILockdownManager $lockdownManager,
		private IUserSession $userSession,
		ICacheFactory $cacheFactory,
		private LoggerInterface $logger,
		private IConfig $config,
		private ShareDisableChecker $shareDisableChecker,
	) {
		$this->cache = $cacheFactory->createDistributed('setupmanager::');
		$this->listeningForProviders = false;

		$this->setupListeners();
	}

	private function isSetupStarted(IUser $user): bool {
		return in_array($user->getUID(), $this->setupUsers, true);
	}

	public function isSetupComplete(IUser $user): bool {
		return in_array($user->getUID(), $this->setupUsersComplete, true);
	}

	private function setupBuiltinWrappers() {
		if ($this->setupBuiltinWrappersDone) {
			return;
		}
		$this->setupBuiltinWrappersDone = true;

		// load all filesystem apps before, so no setup-hook gets lost
		OC_App::loadApps(['filesystem']);
		$prevLogging = Filesystem::logWarningWhenAddingStorageWrapper(false);

		Filesystem::addStorageWrapper('mount_options', function ($mountPoint, IStorage $storage, IMountPoint $mount) {
			if ($storage->instanceOfStorage(Common::class)) {
				$options = array_merge($mount->getOptions(), ['mount_point' => $mountPoint]);
				$storage->setMountOptions($options);
			}
			return $storage;
		});

		$reSharingEnabled = Share::isResharingAllowed();
		$user = $this->userSession->getUser();
		$sharingEnabledForUser = $user ? !$this->shareDisableChecker->sharingDisabledForUser($user->getUID()) : true;
		Filesystem::addStorageWrapper(
			'sharing_mask',
			function ($mountPoint, IStorage $storage, IMountPoint $mount) use ($reSharingEnabled, $sharingEnabledForUser) {
				$sharingEnabledForMount = $mount->getOption('enable_sharing', true);
				$isShared = $mount instanceof ISharedMountPoint;
				if (!$sharingEnabledForMount || !$sharingEnabledForUser || (!$reSharingEnabled && $isShared)) {
					return new PermissionsMask([
						'storage' => $storage,
						'mask' => Constants::PERMISSION_ALL - Constants::PERMISSION_SHARE,
					]);
				}
				return $storage;
			}
		);

		// install storage availability wrapper, before most other wrappers
		Filesystem::addStorageWrapper('oc_availability', function ($mountPoint, IStorage $storage, IMountPoint $mount) {
			$externalMount = $mount instanceof ConfigAdapter || $mount instanceof Mount;
			if ($externalMount && !$storage->isLocal()) {
				return new Availability(['storage' => $storage]);
			}
			return $storage;
		});

		Filesystem::addStorageWrapper('oc_encoding', function ($mountPoint, IStorage $storage, IMountPoint $mount) {
			if ($mount->getOption('encoding_compatibility', false) && !$mount instanceof SharedMount) {
				return new Encoding(['storage' => $storage]);
			}
			return $storage;
		});

		$quotaIncludeExternal = $this->config->getSystemValue('quota_include_external_storage', false);
		Filesystem::addStorageWrapper('oc_quota', function ($mountPoint, $storage, IMountPoint $mount) use ($quotaIncludeExternal) {
			// set up quota for home storages, even for other users
			// which can happen when using sharing
			if ($mount instanceof HomeMountPoint) {
				$user = $mount->getUser();
				return new Quota(['storage' => $storage, 'quotaCallback' => function () use ($user) {
					return OC_Util::getUserQuota($user);
				}, 'root' => 'files', 'include_external_storage' => $quotaIncludeExternal]);
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

		Filesystem::logWarningWhenAddingStorageWrapper($prevLogging);
	}

	/**
	 * Setup the full filesystem for the specified user
	 */
	public function setupForUser(IUser $user): void {
		if ($this->isSetupComplete($user)) {
			return;
		}
		$this->setupUsersComplete[] = $user->getUID();

		$this->eventLogger->start('fs:setup:user:full', 'Setup full filesystem for user');

		if (!isset($this->setupUserMountProviders[$user->getUID()])) {
			$this->setupUserMountProviders[$user->getUID()] = [];
		}

		$previouslySetupProviders = $this->setupUserMountProviders[$user->getUID()];

		$this->setupForUserWith($user, function () use ($user) {
			$this->mountProviderCollection->addMountForUser($user, $this->mountManager, function (
				IMountProvider $provider
			) use ($user) {
				return !in_array(get_class($provider), $this->setupUserMountProviders[$user->getUID()]);
			});
		});
		$this->afterUserFullySetup($user, $previouslySetupProviders);
		$this->eventLogger->end('fs:setup:user:full');
	}

	/**
	 * part of the user setup that is run only once per user
	 */
	private function oneTimeUserSetup(IUser $user) {
		if ($this->isSetupStarted($user)) {
			return;
		}
		$this->setupUsers[] = $user->getUID();

		$this->setupRoot();

		$this->eventLogger->start('fs:setup:user:onetime', 'Onetime filesystem for user');

		$this->setupBuiltinWrappers();

		$prevLogging = Filesystem::logWarningWhenAddingStorageWrapper(false);

		OC_Hook::emit('OC_Filesystem', 'preSetup', ['user' => $user->getUID()]);

		Filesystem::logWarningWhenAddingStorageWrapper($prevLogging);

		$userDir = '/' . $user->getUID() . '/files';

		Filesystem::initInternal($userDir);

		if ($this->lockdownManager->canAccessFilesystem()) {
			$this->eventLogger->start('fs:setup:user:home', 'Setup home filesystem for user');
			// home mounts are handled separate since we need to ensure this is mounted before we call the other mount providers
			$homeMount = $this->mountProviderCollection->getHomeMountForUser($user);
			$this->mountManager->addMount($homeMount);

			if ($homeMount->getStorageRootId() === -1) {
				$this->eventLogger->start('fs:setup:user:home:scan', 'Scan home filesystem for user');
				$homeMount->getStorage()->mkdir('');
				$homeMount->getStorage()->getScanner()->scan('');
				$this->eventLogger->end('fs:setup:user:home:scan');
			}
			$this->eventLogger->end('fs:setup:user:home');
		} else {
			$this->mountManager->addMount(new MountPoint(
				new NullStorage([]),
				'/' . $user->getUID()
			));
			$this->mountManager->addMount(new MountPoint(
				new NullStorage([]),
				'/' . $user->getUID() . '/files'
			));
			$this->setupUsersComplete[] = $user->getUID();
		}

		$this->listenForNewMountProviders();

		$this->eventLogger->end('fs:setup:user:onetime');
	}

	/**
	 * Final housekeeping after a user has been fully setup
	 */
	private function afterUserFullySetup(IUser $user, array $previouslySetupProviders): void {
		$this->eventLogger->start('fs:setup:user:full:post', 'Housekeeping after user is setup');
		$userRoot = '/' . $user->getUID() . '/';
		$mounts = $this->mountManager->getAll();
		$mounts = array_filter($mounts, function (IMountPoint $mount) use ($userRoot) {
			return str_starts_with($mount->getMountPoint(), $userRoot);
		});
		$allProviders = array_map(function (IMountProvider $provider) {
			return get_class($provider);
		}, $this->mountProviderCollection->getProviders());
		$newProviders = array_diff($allProviders, $previouslySetupProviders);
		$mounts = array_filter($mounts, function (IMountPoint $mount) use ($previouslySetupProviders) {
			return !in_array($mount->getMountProvider(), $previouslySetupProviders);
		});
		$this->userMountCache->registerMounts($user, $mounts, $newProviders);

		$cacheDuration = $this->config->getSystemValueInt('fs_mount_cache_duration', 5 * 60);
		if ($cacheDuration > 0) {
			$this->cache->set($user->getUID(), true, $cacheDuration);
			$this->fullSetupRequired[$user->getUID()] = false;
		}
		$this->eventLogger->end('fs:setup:user:full:post');
	}

	/**
	 * @param IUser $user
	 * @param IMountPoint $mounts
	 * @return void
	 * @throws \OCP\HintException
	 * @throws \OC\ServerNotAvailableException
	 */
	private function setupForUserWith(IUser $user, callable $mountCallback): void {
		$this->oneTimeUserSetup($user);

		if ($this->lockdownManager->canAccessFilesystem()) {
			$mountCallback();
		}
		$this->eventLogger->start('fs:setup:user:post-init-mountpoint', 'post_initMountPoints legacy hook');
		\OC_Hook::emit('OC_Filesystem', 'post_initMountPoints', ['user' => $user->getUID()]);
		$this->eventLogger->end('fs:setup:user:post-init-mountpoint');

		$userDir = '/' . $user->getUID() . '/files';
		$this->eventLogger->start('fs:setup:user:setup-hook', 'setup legacy hook');
		OC_Hook::emit('OC_Filesystem', 'setup', ['user' => $user->getUID(), 'user_dir' => $userDir]);
		$this->eventLogger->end('fs:setup:user:setup-hook');
	}

	/**
	 * Set up the root filesystem
	 */
	public function setupRoot(): void {
		//setting up the filesystem twice can only lead to trouble
		if ($this->rootSetup) {
			return;
		}

		$this->setupBuiltinWrappers();

		$this->rootSetup = true;

		$this->eventLogger->start('fs:setup:root', 'Setup root filesystem');

		$rootMounts = $this->mountProviderCollection->getRootMounts();
		foreach ($rootMounts as $rootMountProvider) {
			$this->mountManager->addMount($rootMountProvider);
		}

		$this->eventLogger->end('fs:setup:root');
	}

	/**
	 * Get the user to setup for a path or `null` if the root needs to be setup
	 *
	 * @param string $path
	 * @return IUser|null
	 */
	private function getUserForPath(string $path) {
		if (str_starts_with($path, '/__groupfolders')) {
			return null;
		} elseif (substr_count($path, '/') < 2) {
			if ($user = $this->userSession->getUser()) {
				return $user;
			} else {
				return null;
			}
		} elseif (str_starts_with($path, '/appdata_' . \OC_Util::getInstanceId()) || str_starts_with($path, '/files_external/')) {
			return null;
		} else {
			[, $userId] = explode('/', $path);
		}

		return $this->userManager->get($userId);
	}

	/**
	 * Set up the filesystem for the specified path
	 */
	public function setupForPath(string $path, bool $includeChildren = false): void {
		$user = $this->getUserForPath($path);
		if (!$user) {
			$this->setupRoot();
			return;
		}

		if ($this->isSetupComplete($user)) {
			return;
		}

		if ($this->fullSetupRequired($user)) {
			$this->setupForUser($user);
			return;
		}

		// for the user's home folder, and includes children we need everything always
		if (rtrim($path) === "/" . $user->getUID() . "/files" && $includeChildren) {
			$this->setupForUser($user);
			return;
		}

		if (!isset($this->setupUserMountProviders[$user->getUID()])) {
			$this->setupUserMountProviders[$user->getUID()] = [];
		}
		$setupProviders = &$this->setupUserMountProviders[$user->getUID()];
		$currentProviders = [];

		try {
			$cachedMount = $this->userMountCache->getMountForPath($user, $path);
		} catch (NotFoundException $e) {
			$this->setupForUser($user);
			return;
		}

		$this->oneTimeUserSetup($user);

		$this->eventLogger->start('fs:setup:user:path', "Setup $path filesystem for user");
		$this->eventLogger->start('fs:setup:user:path:find', "Find mountpoint for $path");

		$mounts = [];
		if (!in_array($cachedMount->getMountProvider(), $setupProviders)) {
			$currentProviders[] = $cachedMount->getMountProvider();
			if ($cachedMount->getMountProvider()) {
				$setupProviders[] = $cachedMount->getMountProvider();
				$mounts = $this->mountProviderCollection->getUserMountsForProviderClasses($user, [$cachedMount->getMountProvider()]);
			} else {
				$this->logger->debug("mount at " . $cachedMount->getMountPoint() . " has no provider set, performing full setup");
				$this->eventLogger->end('fs:setup:user:path:find');
				$this->setupForUser($user);
				$this->eventLogger->end('fs:setup:user:path');
				return;
			}
		}

		if ($includeChildren) {
			$subCachedMounts = $this->userMountCache->getMountsInPath($user, $path);
			$this->eventLogger->end('fs:setup:user:path:find');

			$needsFullSetup = array_reduce($subCachedMounts, function (bool $needsFullSetup, ICachedMountInfo $cachedMountInfo) {
				return $needsFullSetup || $cachedMountInfo->getMountProvider() === '';
			}, false);

			if ($needsFullSetup) {
				$this->logger->debug("mount has no provider set, performing full setup");
				$this->setupForUser($user);
				$this->eventLogger->end('fs:setup:user:path');
				return;
			} else {
				foreach ($subCachedMounts as $cachedMount) {
					if (!in_array($cachedMount->getMountProvider(), $setupProviders)) {
						$currentProviders[] = $cachedMount->getMountProvider();
						$setupProviders[] = $cachedMount->getMountProvider();
						$mounts = array_merge($mounts, $this->mountProviderCollection->getUserMountsForProviderClasses($user, [$cachedMount->getMountProvider()]));
					}
				}
			}
		} else {
			$this->eventLogger->end('fs:setup:user:path:find');
		}

		if (count($mounts)) {
			$this->userMountCache->registerMounts($user, $mounts, $currentProviders);
			$this->setupForUserWith($user, function () use ($mounts) {
				array_walk($mounts, [$this->mountManager, 'addMount']);
			});
		} elseif (!$this->isSetupStarted($user)) {
			$this->oneTimeUserSetup($user);
		}
		$this->eventLogger->end('fs:setup:user:path');
	}

	private function fullSetupRequired(IUser $user): bool {
		// we perform a "cached" setup only after having done the full setup recently
		// this is also used to trigger a full setup after handling events that are likely
		// to change the available mounts
		if (!isset($this->fullSetupRequired[$user->getUID()])) {
			$this->fullSetupRequired[$user->getUID()] = !$this->cache->get($user->getUID());
		}
		return $this->fullSetupRequired[$user->getUID()];
	}

	/**
	 * @param string $path
	 * @param string[] $providers
	 */
	public function setupForProvider(string $path, array $providers): void {
		$user = $this->getUserForPath($path);
		if (!$user) {
			$this->setupRoot();
			return;
		}

		if ($this->isSetupComplete($user)) {
			return;
		}

		if ($this->fullSetupRequired($user)) {
			$this->setupForUser($user);
			return;
		}

		$this->eventLogger->start('fs:setup:user:providers', "Setup filesystem for " . implode(', ', $providers));

		$this->oneTimeUserSetup($user);

		// home providers are always used
		$providers = array_filter($providers, function (string $provider) {
			return !is_subclass_of($provider, IHomeMountProvider::class);
		});

		if (in_array('', $providers)) {
			$this->setupForUser($user);
			return;
		}
		$setupProviders = $this->setupUserMountProviders[$user->getUID()] ?? [];

		$providers = array_diff($providers, $setupProviders);
		if (count($providers) === 0) {
			if (!$this->isSetupStarted($user)) {
				$this->oneTimeUserSetup($user);
			}
			$this->eventLogger->end('fs:setup:user:providers');
			return;
		} else {
			$this->setupUserMountProviders[$user->getUID()] = array_merge($setupProviders, $providers);
			$mounts = $this->mountProviderCollection->getUserMountsForProviderClasses($user, $providers);
		}

		$this->userMountCache->registerMounts($user, $mounts, $providers);
		$this->setupForUserWith($user, function () use ($mounts) {
			array_walk($mounts, [$this->mountManager, 'addMount']);
		});
		$this->eventLogger->end('fs:setup:user:providers');
	}

	public function tearDown() {
		$this->setupUsers = [];
		$this->setupUsersComplete = [];
		$this->setupUserMountProviders = [];
		$this->fullSetupRequired = [];
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
			$this->mountProviderCollection->listen('\OC\Files\Config', 'registerMountProvider', function (
				IMountProvider $provider
			) {
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

	private function setupListeners() {
		// note that this event handling is intentionally pessimistic
		// clearing the cache to often is better than not enough

		$this->eventDispatcher->addListener(UserAddedEvent::class, function (UserAddedEvent $event) {
			$this->cache->remove($event->getUser()->getUID());
		});
		$this->eventDispatcher->addListener(UserRemovedEvent::class, function (UserRemovedEvent $event) {
			$this->cache->remove($event->getUser()->getUID());
		});
		$this->eventDispatcher->addListener(ShareCreatedEvent::class, function (ShareCreatedEvent $event) {
			$this->cache->remove($event->getShare()->getSharedWith());
		});
		$this->eventDispatcher->addListener(InvalidateMountCacheEvent::class, function (InvalidateMountCacheEvent $event
		) {
			if ($user = $event->getUser()) {
				$this->cache->remove($user->getUID());
			} else {
				$this->cache->clear();
			}
		});

		$genericEvents = [
			'OCA\Circles\Events\CreatingCircleEvent',
			'OCA\Circles\Events\DestroyingCircleEvent',
			'OCA\Circles\Events\AddingCircleMemberEvent',
			'OCA\Circles\Events\RemovingCircleMemberEvent',
		];

		foreach ($genericEvents as $genericEvent) {
			$this->eventDispatcher->addListener($genericEvent, function ($event) {
				$this->cache->clear();
			});
		}
	}
}
