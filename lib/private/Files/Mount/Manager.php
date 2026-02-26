<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Mount;

use OC\Files\Filesystem;
use OC\Files\SetupManager;
use OC\Files\SetupManagerFactory;
use OCP\Cache\CappedMemoryCache;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;

class Manager implements IMountManager {
	/** @var array<string, IMountPoint> */
	private array $mounts = [];
	private array $mountsByProvider = [];
	private bool $areMountsSorted = false;
	/** @var list<string>|null $mountKeys */
	private ?array $mountKeys = null;
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

	public function addMount(IMountPoint $mount): void {
		$mountPoint = $mount->getMountPoint();
		$mountProvider = $mount->getMountProvider();
		$this->mounts[$mountPoint] = $mount;
		$this->mountsByProvider[$mountProvider] ??= [];
		$this->mountsByProvider[$mountProvider][$mountPoint] = $mount;
		$this->pathCache->clear();
		$this->inPathCache->clear();
		$this->areMountsSorted = false;
	}

	public function removeMount(string $mountPoint): void {
		$mountPoint = Filesystem::normalizePath($mountPoint);
		if (\strlen($mountPoint) > 1) {
			$mountPoint .= '/';
		}
		unset($this->mounts[$mountPoint]);
		$this->pathCache->clear();
		$this->inPathCache->clear();
		$this->areMountsSorted = false;
	}

	public function moveMount(string $mountPoint, string $target): void {
		$this->mounts[$target] = $this->mounts[$mountPoint];
		unset($this->mounts[$mountPoint]);
		$this->pathCache->clear();
		$this->inPathCache->clear();
		$this->areMountsSorted = false;
	}

	/**
	 * Find the mount for $path
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
				throw new \Exception('No mounts even after explicitly setting up the root mounts');
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

		throw new NotFoundException('No mount for path ' . $path . ' existing mounts (' . count($this->mounts) . '): ' . implode(',', array_keys($this->mounts)));
	}

	/**
	 * Find all mounts in $path
	 *
	 * @return IMountPoint[]
	 */
	public function findIn(string $path): array {
		$this->setupManager->setupForPath($path, true);
		$path = $this->formatPath($path);

		if (isset($this->inPathCache[$path])) {
			return $this->inPathCache[$path];
		}

		if (!$this->areMountsSorted) {
			ksort($this->mounts, SORT_STRING);
			$this->mountKeys = array_keys($this->mounts);
			$this->areMountsSorted = true;
		}

		$result = $this->binarySearch($this->mounts, $this->mountKeys, $path);

		$this->inPathCache[$path] = $result;
		return $result;
	}

	/**
	 * Search for all entries in $sortedArray where $prefix is a prefix but not equal to their key.
	 *
	 * @template T
	 * @param array<string, T> $sortedArray
	 * @param list<string> $sortedKeys
	 * @param string $prefix
	 * @return list<T>
	 */
	private function binarySearch(array $sortedArray, array $sortedKeys, string $prefix): array {
		$low = 0;
		$high = count($sortedArray) - 1;
		$start = null;

		// binary search
		while ($low <= $high) {
			$mid = ($low + $high) >> 1;
			if ($sortedKeys[$mid] < $prefix) {
				$low = $mid + 1;
			} else {
				$start = $mid;
				$high = $mid - 1;
			}
		}

		$result = [];
		if ($start !== null) {
			for ($i = $start, $n = count($sortedKeys); $i < $n; $i++) {
				$key = $sortedKeys[$i];
				if (!str_starts_with($key, $prefix)) {
					break;
				}

				if ($key !== $prefix) {
					$result[] = $sortedArray[$key];
				}
			}
		}

		return $result;
	}

	public function clear(): void {
		$this->mounts = [];
		$this->mountsByProvider = [];
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
	 * Return all mounts in a path from a specific mount provider, indexed by mount point
	 *
	 * @param string $path
	 * @param string[] $mountProviders
	 * @return array<string, IMountPoint>
	 */
	public function getMountsByMountProvider(string $path, array $mountProviders): array {
		$this->getSetupManager()->setupForProvider($path, $mountProviders);
		if (\in_array('', $mountProviders)) {
			return $this->mounts;
		}

		$mounts = [];
		foreach ($mountProviders as $mountProvider) {
			$mounts[] = $this->mountsByProvider[$mountProvider] ?? [];
		}

		return array_merge(...$mounts);
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
