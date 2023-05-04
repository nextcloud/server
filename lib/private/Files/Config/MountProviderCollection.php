<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
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
	 * @var \OCP\Files\Config\IHomeMountProvider[]
	 */
	private $homeProviders = [];

	/**
	 * @var \OCP\Files\Config\IMountProvider[]
	 */
	private $providers = [];

	/** @var \OCP\Files\Config\IRootMountProvider[] */
	private $rootProviders = [];

	/**
	 * @var \OCP\Files\Storage\IStorageFactory
	 */
	private $loader;

	/**
	 * @var \OCP\Files\Config\IUserMountCache
	 */
	private $mountCache;

	/** @var callable[] */
	private $mountFilters = [];

	private IEventLogger $eventLogger;

	/**
	 * @param \OCP\Files\Storage\IStorageFactory $loader
	 * @param IUserMountCache $mountCache
	 */
	public function __construct(
		IStorageFactory $loader,
		IUserMountCache $mountCache,
		IEventLogger $eventLogger
	) {
		$this->loader = $loader;
		$this->mountCache = $mountCache;
		$this->eventLogger = $eventLogger;
	}

	private function getMountsFromProvider(IMountProvider $provider, IUser $user, IStorageFactory $loader): array {
		$class = str_replace('\\', '_', get_class($provider));
		$uid = $user->getUID();
		$this->eventLogger->start('fs:setup:provider:' . $class, "Getting mounts from $class for $uid");
		$mounts = $provider->getMountsForUser($user, $loader) ?? [];
		$this->eventLogger->end('fs:setup:provider:' . $class);
		return $mounts;
	}

	/**
	 * @param IUser $user
	 * @param IMountProvider[] $providers
	 * @return IMountPoint[]
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

	public function getMountsForUser(IUser $user): array {
		return $this->getUserMountsForProviders($user, $this->providers);
	}

	public function getUserMountsForProviderClasses(IUser $user, array $mountProviderClasses): array {
		$providers = array_filter(
			$this->providers,
			fn (IMountProvider $mountProvider) => (in_array(get_class($mountProvider), $mountProviderClasses))
		);
		return $this->getUserMountsForProviders($user, $providers);
	}

	public function addMountForUser(IUser $user, IMountManager $mountManager, callable $providerFilter = null) {
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
		$this->eventLogger->start("fs:setup:add-mounts", "Add mounts to the filesystem");
		array_walk($lateMounts, [$mountManager, 'addMount']);
		$this->eventLogger->end("fs:setup:add-mounts");

		return array_merge($lateMounts, $firstMounts);
	}

	/**
	 * Get the configured home mount for this user
	 *
	 * @param \OCP\IUser $user
	 * @return \OCP\Files\Mount\IMountPoint
	 * @since 9.1.0
	 */
	public function getHomeMountForUser(IUser $user) {
		/** @var \OCP\Files\Config\IHomeMountProvider[] $providers */
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
	 *
	 * @param \OCP\Files\Config\IMountProvider $provider
	 */
	public function registerProvider(IMountProvider $provider) {
		$this->providers[] = $provider;

		$this->emit('\OC\Files\Config', 'registerMountProvider', [$provider]);
	}

	public function registerMountFilter(callable $filter) {
		$this->mountFilters[] = $filter;
	}

	private function filterMounts(IUser $user, array $mountPoints) {
		return array_filter($mountPoints, function (IMountPoint $mountPoint) use ($user) {
			foreach ($this->mountFilters as $filter) {
				if ($filter($mountPoint, $user) === false) {
					return false;
				}
			}
			return true;
		});
	}

	/**
	 * Add a provider for home mount points
	 *
	 * @param \OCP\Files\Config\IHomeMountProvider $provider
	 * @since 9.1.0
	 */
	public function registerHomeProvider(IHomeMountProvider $provider) {
		$this->homeProviders[] = $provider;
		$this->emit('\OC\Files\Config', 'registerHomeMountProvider', [$provider]);
	}

	/**
	 * Get the mount cache which can be used to search for mounts without setting up the filesystem
	 *
	 * @return IUserMountCache
	 */
	public function getMountCache() {
		return $this->mountCache;
	}

	public function registerRootProvider(IRootMountProvider $provider) {
		$this->rootProviders[] = $provider;
	}

	/**
	 * Get all root mountpoints
	 *
	 * @return \OCP\Files\Mount\IMountPoint[]
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
		return $mounts;
	}

	public function clearProviders() {
		$this->providers = [];
		$this->homeProviders = [];
		$this->rootProviders = [];
	}

	public function getProviders(): array {
		return $this->providers;
	}
}
