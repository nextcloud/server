<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage\Wrapper;

use OC\Files\Filesystem;
use OC\SystemConfig;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\FileInfo;
use OCP\Files\Storage\IStorage;

class Quota extends Wrapper {
	/** @var callable|null */
	protected $quotaCallback;
	/** @var int|float|null int on 64bits, float on 32bits for bigint */
	protected int|float|null $quota;
	protected string $sizeRoot;
	private SystemConfig $config;
	private bool $quotaIncludeExternalStorage;
	private bool $enabled = true;

	/**
	 * @param array $parameters
	 */
	public function __construct(array $parameters) {
		parent::__construct($parameters);
		$this->quota = $parameters['quota'] ?? null;
		$this->quotaCallback = $parameters['quotaCallback'] ?? null;
		$this->sizeRoot = $parameters['root'] ?? '';
		$this->quotaIncludeExternalStorage = $parameters['include_external_storage'] ?? false;
	}

	public function getQuota(): int|float {
		if ($this->quota === null) {
			$quotaCallback = $this->quotaCallback;
			if ($quotaCallback === null) {
				throw new \Exception('No quota or quota callback provider');
			}
			$this->quota = $quotaCallback();
		}

		return $this->quota;
	}

	private function hasQuota(): bool {
		if (!$this->enabled) {
			return false;
		}
		return $this->getQuota() !== FileInfo::SPACE_UNLIMITED;
	}

	protected function getSize(string $path, ?IStorage $storage = null): int|float {
		if ($this->quotaIncludeExternalStorage) {
			$rootInfo = Filesystem::getFileInfo('', 'ext');
			if ($rootInfo) {
				return $rootInfo->getSize(true);
			}
			return FileInfo::SPACE_NOT_COMPUTED;
		} else {
			$cache = is_null($storage) ? $this->getCache() : $storage->getCache();
			$data = $cache->get($path);
			if ($data instanceof ICacheEntry && isset($data['size'])) {
				return $data['size'];
			} else {
				return FileInfo::SPACE_NOT_COMPUTED;
			}
		}
	}

	public function free_space(string $path): int|float|false {
		if (!$this->hasQuota()) {
			return $this->storage->free_space($path);
		}
		if ($this->getQuota() < 0 || str_starts_with($path, 'cache') || str_starts_with($path, 'uploads')) {
			return $this->storage->free_space($path);
		} else {
			$used = $this->getSize($this->sizeRoot);
			if ($used < 0) {
				return FileInfo::SPACE_NOT_COMPUTED;
			} else {
				$free = $this->storage->free_space($path);
				$quotaFree = max($this->getQuota() - $used, 0);
				// if free space is known
				$free = $free >= 0 ? min($free, $quotaFree) : $quotaFree;
				return $free;
			}
		}
	}

	public function file_put_contents(string $path, mixed $data): int|float|false {
		if (!$this->hasQuota()) {
			return $this->storage->file_put_contents($path, $data);
		}
		$free = $this->free_space($path);
		if ($free < 0 || strlen($data) < $free) {
			return $this->storage->file_put_contents($path, $data);
		} else {
			return false;
		}
	}

	public function copy(string $source, string $target): bool {
		if (!$this->hasQuota()) {
			return $this->storage->copy($source, $target);
		}
		$free = $this->free_space($target);
		if ($free < 0 || $this->getSize($source) < $free) {
			return $this->storage->copy($source, $target);
		} else {
			return false;
		}
	}

	public function fopen(string $path, string $mode) {
		if (!$this->hasQuota()) {
			return $this->storage->fopen($path, $mode);
		}
		$source = $this->storage->fopen($path, $mode);

		// don't apply quota for part files
		if (!$this->isPartFile($path)) {
			$free = $this->free_space($path);
			if ($source && (is_int($free) || is_float($free)) && $free >= 0 && $mode !== 'r' && $mode !== 'rb') {
				// only apply quota for files, not metadata, trash or others
				if ($this->shouldApplyQuota($path)) {
					return \OC\Files\Stream\Quota::wrap($source, $free);
				}
			}
		}

		return $source;
	}

	/**
	 * Checks whether the given path is a part file
	 *
	 * @param string $path Path that may identify a .part file
	 * @note this is needed for reusing keys
	 */
	private function isPartFile(string $path): bool {
		$extension = pathinfo($path, PATHINFO_EXTENSION);

		return ($extension === 'part');
	}

	/**
	 * Only apply quota for files, not metadata, trash or others
	 */
	protected function shouldApplyQuota(string $path): bool {
		return str_starts_with(ltrim($path, '/'), 'files/');
	}

	public function copyFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		if (!$this->hasQuota()) {
			return $this->storage->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		}
		$free = $this->free_space($targetInternalPath);
		if ($free < 0 || $this->getSize($sourceInternalPath, $sourceStorage) < $free) {
			return $this->storage->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} else {
			return false;
		}
	}

	public function moveFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		if (!$this->hasQuota()) {
			return $this->storage->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		}
		$free = $this->free_space($targetInternalPath);
		if ($free < 0 || $this->getSize($sourceInternalPath, $sourceStorage) < $free) {
			return $this->storage->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} else {
			return false;
		}
	}

	public function mkdir(string $path): bool {
		if (!$this->hasQuota()) {
			return $this->storage->mkdir($path);
		}
		$free = $this->free_space($path);
		if ($this->shouldApplyQuota($path) && $free == 0) {
			return false;
		}

		return parent::mkdir($path);
	}

	public function touch(string $path, ?int $mtime = null): bool {
		if (!$this->hasQuota()) {
			return $this->storage->touch($path, $mtime);
		}
		$free = $this->free_space($path);
		if ($free == 0) {
			return false;
		}

		return parent::touch($path, $mtime);
	}

	public function enableQuota(bool $enabled): void {
		$this->enabled = $enabled;
	}
}
