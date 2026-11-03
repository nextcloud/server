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
use OCP\Files\GenericFileException;
use OCP\Files\NotEnoughSpaceException;
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

	public function getSize(string $path, ?IStorage $storage = null): int|float {
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

	#[\Override]
	public function free_space(string $path): int|float|false {
		return $this->calculateFreeSpace($path);
	}

	/**
	 * Calculate free space constrained by quota and physical storage capacity.
	 *
	 * This helper exists so the available space calculation can, optionally, be
	 * influenced for an operation that may replace a file.
	 *
	 * When specified, credit the size of the pre-existing file against quota usage,
	 * but not against the wrapped storage's physical free space. This is conservative
	 * because overwriting a file may not immediately reclaim physical space on every
	 * storage backend. Targets outside the quota scope or with unknown sizes are not
	 * credited.
	 *
	 * @param string $path Path to check
	 * @param bool $creditExistingTarget Whether to credit an eligible existing file at the path
	 */
	private function calculateFreeSpace(string $path, bool $creditExistingTarget = false): int|float|false {
		$storage = $this->getWrapperStorage();

		if (!$this->hasQuota()
			|| $this->getQuota() < 0
			|| str_starts_with($path, 'cache')
			|| str_starts_with($path, 'uploads')
		) {
			return $storage->free_space($path);
		}

		$used = $this->getSize($this->sizeRoot);
		if ($used < 0) {
			return FileInfo::SPACE_NOT_COMPUTED;
		}

		if ($creditExistingTarget
			&& !$this->quotaIncludeExternalStorage
			&& $this->shouldApplyQuota($path)
			&& $storage->is_file($path)
		) {
			$root = trim($this->sizeRoot, '/');
			$target = trim($path, '/');
			$isWithinRoot = $root === ''
				|| $target === $root
				|| str_starts_with($target, $root . '/');

			if ($isWithinRoot) {
				$existingSize = $this->getSize($path);
				if ($existingSize >= 0) {
					$used = max(0, $used - $existingSize);
				}
			}
		}

		$quotaFree = max($this->getQuota() - $used, 0);
		$diskFree = $storage->free_space($path);
		return $diskFree >= 0 ? min($diskFree, $quotaFree) : $quotaFree;
	}

	#[\Override]
	public function file_put_contents(string $path, mixed $data): int|float|false {
		if (!$this->hasQuota()) {
			return $this->getWrapperStorage()->file_put_contents($path, $data);
		}

		$free = $this->calculateFreeSpace($path, creditExistingTarget: true);
		if ($free < 0 || strlen($data) <= $free) {
			return $this->getWrapperStorage()->file_put_contents($path, $data);
		} else {
			return false;
		}
	}

	#[\Override]
	public function copy(string $source, string $target): bool {
		if (!$this->hasQuota()) {
			return $this->getWrapperStorage()->copy($source, $target);
		}

		$free = $this->calculateFreeSpace($target, creditExistingTarget: true);
		if ($free < 0 || $this->getSize($source) <= $free) {
			return $this->getWrapperStorage()->copy($source, $target);
		} else {
			return false;
		}
	}

	#[\Override]
	public function fopen(string $path, string $mode) {
		// reading doesn't consume quota, so it should not be restricted by it
		$isReadMode = $mode === 'r' || $mode === 'rb';
		if ($isReadMode || !$this->hasQuota() || $this->isPartFile($path)) {
			return $this->getWrapperStorage()->fopen($path, $mode);
		}

		$free = $this->calculateFreeSpace($path, creditExistingTarget: str_starts_with($mode, 'w'));
		// treat a failed free_space() the same as no free space
		if ($this->shouldApplyQuota($path) && ($free === 0 || $free === 0.0 || $free === false)) {
			return false;
		}

		$source = $this->getWrapperStorage()->fopen($path, $mode);
		// writing past the remaining free space fails. Negative values mean the free
		// space is unknown or unlimited (FileInfo::SPACE_*), so no limit is applied
		if ($source && (is_int($free) || is_float($free)) && $free >= 0) {
			// only apply quota for files, not metadata, trash or others
			if ($this->shouldApplyQuota($path)) {
				return \OC\Files\Stream\Quota::wrap($source, $free);
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

	#[\Override]
	public function copyFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		if (!$this->hasQuota()) {
			return $this->getWrapperStorage()->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		}

		$free = $this->calculateFreeSpace($targetInternalPath, creditExistingTarget: true);
		if ($free < 0 || $this->getSize($sourceInternalPath, $sourceStorage) <= $free) {
			return $this->getWrapperStorage()->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} else {
			return false;
		}
	}

	#[\Override]
	public function moveFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		if (!$this->hasQuota()) {
			return $this->getWrapperStorage()->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		}

		// Credit a known, in-scope target, but require room for the full source because it
		// may be outside this quota's scope.
		// Note: This can reject moves when the source is already included in this quota's usage.
		// TODO: Account for the source's removal when it is known to be included in this quota.
		$free = $this->calculateFreeSpace($targetInternalPath, creditExistingTarget: true);
		if ($free < 0 || $this->getSize($sourceInternalPath, $sourceStorage) <= $free) {
			return $this->getWrapperStorage()->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} else {
			return false;
		}
	}

	#[\Override]
	public function mkdir(string $path): bool {
		if (!$this->hasQuota()) {
			return $this->getWrapperStorage()->mkdir($path);
		}

		$free = $this->free_space($path);
		// treat a failed free_space() the same as no free space
		if ($this->shouldApplyQuota($path) && ($free === 0 || $free === 0.0 || $free === false)) {
			return false;
		}

		return parent::mkdir($path);
	}

	#[\Override]
	public function touch(string $path, ?int $mtime = null): bool {
		if (!$this->hasQuota()) {
			return $this->getWrapperStorage()->touch($path, $mtime);
		}

		$free = $this->free_space($path);
		// treat a failed free_space() the same as no free space
		if ($free === 0 || $free === 0.0 || $free === false) {
			return false;
		}

		return parent::touch($path, $mtime);
	}

	public function enableQuota(bool $enabled): void {
		$this->enabled = $enabled;
	}

	#[\Override]
	public function writeStream(string $path, $stream, ?int $size = null): int {
		if (!$this->hasQuota()) {
			return parent::writeStream($path, $stream, $size);
		}

		$free = $this->calculateFreeSpace($path, creditExistingTarget: true);
		// treat a failed free_space() the same as no free space
		if ($this->shouldApplyQuota($path) && ($free === 0 || $free === 0.0 || $free === false)) {
			throw new NotEnoughSpaceException();
		}

		if ($size !== null) {
			if ($free < 0 || $size <= $free) {
				return parent::writeStream($path, $stream, $size);
			} else {
				throw new NotEnoughSpaceException();
			}
		} else {
			// force fallback through `fopen` to handle the quota
			try {
				return parent::writeStreamFallback($path, $stream);
			} catch (GenericFileException) {
				throw new NotEnoughSpaceException();
			}
		}
	}
}
