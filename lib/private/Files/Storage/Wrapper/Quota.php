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

	#[\Override]
	public function free_space(string $path): int|float|false {
		if (!$this->hasQuota()) {
			return $this->getWrapperStorage()->free_space($path);
		}
		if ($this->getQuota() < 0 || str_starts_with($path, 'cache') || str_starts_with($path, 'uploads')) {
			return $this->getWrapperStorage()->free_space($path);
		} else {
			$used = $this->getSize($this->sizeRoot);
			if ($used < 0) {
				return FileInfo::SPACE_NOT_COMPUTED;
			} else {
				$free = $this->getWrapperStorage()->free_space($path);
				$quotaFree = max($this->getQuota() - $used, 0);
				// if free space is known
				$free = $free >= 0 ? min($free, $quotaFree) : $quotaFree;
				return $free;
			}
		}
	}

	#[\Override]
	public function file_put_contents(string $path, mixed $data): int|float|false {
		if (!$this->hasQuota()) {
			return $this->getWrapperStorage()->file_put_contents($path, $data);
		}
		$free = $this->free_space($path);
		// Only apply quota for files under the user's "files/" tree.
		// Writes to metadata locations (files_trashbin/, files_versions/, ...)
		// must not be blocked, otherwise features like the trashbin break
		// for users whose quota happens to be exhausted (notably quota=0).
		if ($free < 0 || !$this->shouldApplyQuota($path) || strlen($data) < $free) {
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
		$free = $this->free_space($target);
		if ($free < 0 || !$this->shouldApplyQuota($target) || $this->getSize($source) < $free) {
			return $this->getWrapperStorage()->copy($source, $target);
		} else {
			return false;
		}
	}

	#[\Override]
	public function fopen(string $path, string $mode) {
		if (!$this->hasQuota() || $this->isPartFile($path)) {
			return $this->getWrapperStorage()->fopen($path, $mode);
		}

		$free = $this->free_space($path);
		if ($this->shouldApplyQuota($path) && $free == 0) {
			return false;
		}

		$source = $this->getWrapperStorage()->fopen($path, $mode);
		if ($source && (is_int($free) || is_float($free)) && $free >= 0 && $mode !== 'r' && $mode !== 'rb') {
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
		$free = $this->free_space($targetInternalPath);
		// Skip the quota check when the target lives outside of "files/"
		// (e.g. files_trashbin/, files_versions/). This is essential so that
		// the trashbin can store deleted items even when the user's quota is
		// fully consumed: otherwise DELETE operations on external mounts fail
		// with HTTP 403 because the move-to-trash copy returns false.
		if ($free < 0 || !$this->shouldApplyQuota($targetInternalPath) || $this->getSize($sourceInternalPath, $sourceStorage) < $free) {
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
		$free = $this->free_space($targetInternalPath);
		if ($free < 0 || !$this->shouldApplyQuota($targetInternalPath) || $this->getSize($sourceInternalPath, $sourceStorage) < $free) {
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
		if ($this->shouldApplyQuota($path) && $free == 0) {
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
		// Same rule as the other write paths: only block when the target is
		// actually under the user-quota controlled "files/" tree.
		if ($free == 0 && $this->shouldApplyQuota($path)) {
			return false;
		}

		return parent::touch($path, $mtime);
	}

	public function enableQuota(bool $enabled): void {
		$this->enabled = $enabled;
	}

	#[\Override]
	public function writeStream(string $path, $stream, ?int $size = null): int {
		if (!$this->hasQuota() || !$this->shouldApplyQuota($path)) {
			return parent::writeStream($path, $stream, $size);
		}

		$free = $this->free_space($path);
		if ($free == 0) {
			throw new NotEnoughSpaceException();
		}

		if ($size !== null) {
			if ($size < $free) {
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
