<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Config;

use OC\Hooks\Emitter;
use OC\Hooks\EmitterTrait;
use OCP\Diagnostics\IEventLogger;
use OCP\Files\Config\IHomeMountProvider;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Config\IRootMountProvider;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;

class MountProviderCollection implements IMountProviderCollection, Emitter {
	use EmitterTrait;

	/**
	 * @var list<IHomeMountProvider>
	 */
	private array $homeProviders = [];

	/**
	 * @var list<IMountProvider>
	 */
	private array $providers = [];

	/** @var list<IRootMountProvider> */
	private array $rootProviders = [];

	/** @var list<callable> */
	private array $mountFilters = [];

	public function __construct(
		private IStorageFactory $loader,
		private IUserMountCache $mountCache,
		private IEventLogger $eventLogger,
	) {
	}

	/**
	 * @return list<IMountPoint>
	 */
	private function getMountsFromProvider(IMountProvider $provider, IUser $user, IStorageFactory $loader): array {
		$class = str_replace('\\', '_', get_class($provider));
		$uid = $user->getUID();
		$this->eventLogger->start('fs:setup:provider:' . $class, "Getting mounts from $class for $uid");
		$mounts = $provider->getMountsForUser($user, $loader) ?? [];
		$this->eventLogger->end('fs:setup:provider:' . $class);
		return array_values($mounts);
	}

	/**
	 * @param list<IMountProvider> $providers
	 * @return list<IMountPoint>
	 */
	private function getUserMountsForProviders(IUser $user, array $providers): array {
		$loader = $this->loader;
		$mounts = array_map(function (IMountProvider $provider) use ($user, $loader) {
			return $this->getMountsFromProvider($provider, $user, $loader);
		}, $providers);
		$mounts = array_reduce($mounts, function (array $mounts, array $providerMounts) {
			return array_merge($mounts, $providerMounts);
		}, []);
		return $this->filterMounts($user, $mounts);
	}

	/**
	 * @return list<IMountPoint>
	 */
	public function getMountsForUser(IUser $user): array {
		return $this->getUserMountsForProviders($user, $this->providers);
	}

	/**
	 * @return list<IMountPoint>
	 */
	public function getUserMountsForProviderClasses(IUser $user, array $mountProviderClasses): array {
		$providers = array_filter(
			$this->providers,
			fn (IMountProvider $mountProvider) => (in_array(get_class($mountProvider), $mountProviderClasses))
		);
		return $this->getUserMountsForProviders($user, $providers);
	}

	/**
	 * @return list<IMountPoint>
	 */
	public function addMountForUser(IUser $user, IMountManager $mountManager, ?callable $providerFilter = null): array {
		// shared mount provider gets to go last since it needs to know existing files
		// to check for name collisions
		$firstMounts = [];
		if ($providerFilter) {
			$providers = array_filter($this->providers, $providerFilter);
		} else {
			$providers = $this->providers;
		}
		$firstProviders = array_filter($providers, function (IMountProvider $provider) {
			return (get_class($provider) !== 'OCA\Files_Sharing\MountProvider');
		});
		$lastProviders = array_filter($providers, function (IMountProvider $provider) {
			return (get_class($provider) === 'OCA\Files_Sharing\MountProvider');
		});
		foreach ($firstProviders as $provider) {
			$mounts = $this->getMountsFromProvider($provider, $user, $this->loader);
			$firstMounts = array_merge($firstMounts, $mounts);
		}
		$firstMounts = $this->filterMounts($user, $firstMounts);
		array_walk($firstMounts, [$mountManager, 'addMount']);

		$lateMounts = [];
		foreach ($lastProviders as $provider) {
			$mounts = $this->getMountsFromProvider($provider, $user, $this->loader);
			$lateMounts = array_merge($lateMounts, $mounts);
		}

		$lateMounts = $this->filterMounts($user, $lateMounts);
		$this->eventLogger->start('fs:setup:add-mounts', 'Add mounts to the filesystem');
		array_walk($lateMounts, [$mountManager, 'addMount']);
		$this->eventLogger->end('fs:setup:add-mounts');

		return array_values(array_merge($lateMounts, $firstMounts));
	}

	/**
	 * Get the configured home mount for this user
	 *
	 * @since 9.1.0
	 */
	public function getHomeMountForUser(IUser $user): IMountPoint {
		$providers = array_reverse($this->homeProviders); // call the latest registered provider first to give apps an opportunity to overwrite builtin
		foreach ($providers as $homeProvider) {
			if ($mount = $homeProvider->getHomeMountForUser($user, $this->loader)) {
				$mount->setMountPoint('/' . $user->getUID()); //make sure the mountpoint is what we expect
				return $mount;
			}
		}
		throw new \Exception('No home storage configured for user ' . $user);
	}

	/**
	 * Add a provider for mount points
	 */
	public function registerProvider(IMountProvider $provider): void {
		$this->providers[] = $provider;

		$this->emit('\OC\Files\Config', 'registerMountProvider', [$provider]);
	}

	public function registerMountFilter(callable $filter): void {
		$this->mountFilters[] = $filter;
	}

	/**
	 * @param list<IMountPoint> $mountPoints
	 * @return list<IMountPoint>
	 */
	private function filterMounts(IUser $user, array $mountPoints): array {
		return array_values(array_filter($mountPoints, function (IMountPoint $mountPoint) use ($user) {
			foreach ($this->mountFilters as $filter) {
				if ($filter($mountPoint, $user) === false) {
					return false;
				}
			}
			return true;
		}));
	}

	/**
	 * Add a provider for home mount points
	 *
	 * @param IHomeMountProvider $provider
	 * @since 9.1.0
	 */
	public function registerHomeProvider(IHomeMountProvider $provider) {
		$this->homeProviders[] = $provider;
		$this->emit('\OC\Files\Config', 'registerHomeMountProvider', [$provider]);
	}

	/**
	 * Get the mount cache which can be used to search for mounts without setting up the filesystem
	 */
	public function getMountCache(): IUserMountCache {
		return $this->mountCache;
	}

	public function registerRootProvider(IRootMountProvider $provider): void {
		$this->rootProviders[] = $provider;
	}

	/**
	 * Get all root mountpoints
	 *
	 * @return list<IMountPoint>
	 * @since 20.0.0
	 */
	public function getRootMounts(): array {
		$loader = $this->loader;
		$mounts = array_map(function (IRootMountProvider $provider) use ($loader) {
			return $provider->getRootMounts($loader);
		}, $this->rootProviders);
		$mounts = array_reduce($mounts, function (array $mounts, array $providerMounts) {
			return array_merge($mounts, $providerMounts);
		}, []);

		if (count($mounts) === 0) {
			throw new \Exception('No root mounts provided by any provider');
		}

		return array_values($mounts);
	}

	public function clearProviders(): void {
		$this->providers = [];
		$this->homeProviders = [];
		$this->rootProviders = [];
	}

	/**
	 * @return list<IMountProvider>
	 */
	public function getProviders(): array {
		return $this->providers;
	}

	/**
	 * @return list<IHomeMountProvider>
	 */
	public function getHomeProviders(): array {
		return $this->homeProviders;
	}

	/**
	 * @return list<IRootMountProvider>
	 */
	public function getRootProviders(): array {
		return $this->rootProviders;
	}
}
