<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Jonas <jonas@freesources.org>
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

namespace OC\Files\Mount;

use OCP\Cache\CappedMemoryCache;
use OC\Files\Filesystem;
use OC\Files\SetupManager;
use OC\Files\SetupManagerFactory;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;

class Manager implements IMountManager {
	/** @var MountPoint[] */
	private array $mounts = [];
	/** @var CappedMemoryCache<IMountPoint> */
	private CappedMemoryCache $pathCache;
	/** @var CappedMemoryCache<IMountPoint[]> */
	private CappedMemoryCache $inPathCache;
	private SetupManager $setupManager;

	public function __construct(SetupManagerFactory $setupManagerFactory) {
		$this->pathCache = new CappedMemoryCache();
		$this->inPathCache = new CappedMemoryCache();
		$this->setupManager = $setupManagerFactory->create($this);
	}

	/**
	 * @param IMountPoint $mount
	 */
	public function addMount(IMountPoint $mount) {
		$this->mounts[$mount->getMountPoint()] = $mount;
		$this->pathCache->clear();
		$this->inPathCache->clear();
	}

	/**
	 * @param string $mountPoint
	 */
	public function removeMount(string $mountPoint) {
		$mountPoint = Filesystem::normalizePath($mountPoint);
		if (\strlen($mountPoint) > 1) {
			$mountPoint .= '/';
		}
		unset($this->mounts[$mountPoint]);
		$this->pathCache->clear();
		$this->inPathCache->clear();
	}

	/**
	 * @param string $mountPoint
	 * @param string $target
	 */
	public function moveMount(string $mountPoint, string $target) {
		$this->mounts[$target] = $this->mounts[$mountPoint];
		unset($this->mounts[$mountPoint]);
		$this->pathCache->clear();
		$this->inPathCache->clear();
	}

	/**
	 * Find the mount for $path
	 *
	 * @param string $path
	 * @return IMountPoint
	 */
	public function find(string $path): IMountPoint {
		$this->setupManager->setupForPath($path);
		$path = Filesystem::normalizePath($path);

		if (isset($this->pathCache[$path])) {
			return $this->pathCache[$path];
		}



		if (count($this->mounts) === 0) {
			$this->setupManager->setupRoot();
			if (count($this->mounts) === 0) {
				throw new \Exception("No mounts even after explicitly setting up the root mounts");
			}
		}

		$current = $path;
		while (true) {
			$mountPoint = $current . '/';
			if (isset($this->mounts[$mountPoint])) {
				$this->pathCache[$path] = $this->mounts[$mountPoint];
				return $this->mounts[$mountPoint];
			} elseif ($current === '') {
				break;
			}

			$current = dirname($current);
			if ($current === '.' || $current === '/') {
				$current = '';
			}
		}

		throw new NotFoundException("No mount for path " . $path . " existing mounts (" . count($this->mounts) ."): " . implode(",", array_keys($this->mounts)));
	}

	/**
	 * Find all mounts in $path
	 *
	 * @param string $path
	 * @return IMountPoint[]
	 */
	public function findIn(string $path): array {
		$this->setupManager->setupForPath($path, true);
		$path = $this->formatPath($path);

		if (isset($this->inPathCache[$path])) {
			return $this->inPathCache[$path];
		}

		$result = [];
		$pathLength = \strlen($path);
		$mountPoints = array_keys($this->mounts);
		foreach ($mountPoints as $mountPoint) {
			if (substr($mountPoint, 0, $pathLength) === $path && \strlen($mountPoint) > $pathLength) {
				$result[] = $this->mounts[$mountPoint];
			}
		}

		$this->inPathCache[$path] = $result;
		return $result;
	}

	public function clear() {
		$this->mounts = [];
		$this->pathCache->clear();
		$this->inPathCache->clear();
	}

	/**
	 * Find mounts by storage id
	 *
	 * @param string $id
	 * @return IMountPoint[]
	 */
	public function findByStorageId(string $id): array {
		if (\strlen($id) > 64) {
			$id = md5($id);
		}
		$result = [];
		foreach ($this->mounts as $mount) {
			if ($mount->getStorageId() === $id) {
				$result[] = $mount;
			}
		}
		return $result;
	}

	/**
	 * @return IMountPoint[]
	 */
	public function getAll(): array {
		return $this->mounts;
	}

	/**
	 * Find mounts by numeric storage id
	 *
	 * @param int $id
	 * @return IMountPoint[]
	 */
	public function findByNumericId(int $id): array {
		$result = [];
		foreach ($this->mounts as $mount) {
			if ($mount->getNumericStorageId() === $id) {
				$result[] = $mount;
			}
		}
		return $result;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	private function formatPath(string $path): string {
		$path = Filesystem::normalizePath($path);
		if (\strlen($path) > 1) {
			$path .= '/';
		}
		return $path;
	}

	public function getSetupManager(): SetupManager {
		return $this->setupManager;
	}

	/**
	 * Return all mounts in a path from a specific mount provider
	 *
	 * @param string $path
	 * @param string[] $mountProviders
	 * @return MountPoint[]
	 */
	public function getMountsByMountProvider(string $path, array $mountProviders) {
		$this->getSetupManager()->setupForProvider($path, $mountProviders);
		if (in_array('', $mountProviders)) {
			return $this->mounts;
		} else {
			return array_filter($this->mounts, function ($mount) use ($mountProviders) {
				return in_array($mount->getMountProvider(), $mountProviders);
			});
		}
	}

	/**
	 * Return the mount matching a cached mount info (or mount file info)
	 *
	 * @param ICachedMountInfo $info
	 *
	 * @return IMountPoint|null
	 */
	public function getMountFromMountInfo(ICachedMountInfo $info): ?IMountPoint {
		$this->setupManager->setupForPath($info->getMountPoint());
		foreach ($this->mounts as $mount) {
			if ($mount->getMountPoint() === $info->getMountPoint()) {
				return $mount;
			}
		}
		return null;
	}
}
