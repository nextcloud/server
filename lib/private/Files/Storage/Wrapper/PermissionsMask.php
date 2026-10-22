<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Files\Storage\Wrapper;

use OC\Files\Cache\Wrapper\CachePermissionsMask;
use OCP\Constants;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\IScanner;
use OCP\Files\Storage\IStorage;

/**
 * Mask the permissions of a storage
 *
 * This can be used to restrict update, create, delete and/or share permissions of a storage
 *
 * Note that the read permissions can't be masked
 */
class PermissionsMask extends Wrapper {
	/**
	 * @var int the permissions bits we want to keep
	 */
	protected readonly int $mask;

	/**
	 * @param array{storage: IStorage, mask: int, ...} $parameters
	 *
	 * $storage: The storage the permissions mask should be applied on
	 * $mask: The permission bits that should be kept, a combination of the \OCP\Constant::PERMISSION_ constants
	 */
	public function __construct(array $parameters) {
		parent::__construct($parameters);
		$this->mask = $parameters['mask'];
	}

	private function checkMask(int $permissions): bool {
		return ($this->mask & $permissions) === $permissions;
	}

	#[\Override]
	public function isUpdatable(string $path): bool {
		return $this->checkMask(Constants::PERMISSION_UPDATE) && parent::isUpdatable($path);
	}

	#[\Override]
	public function isCreatable(string $path): bool {
		return $this->checkMask(Constants::PERMISSION_CREATE) && parent::isCreatable($path);
	}

	#[\Override]
	public function isDeletable(string $path): bool {
		return $this->checkMask(Constants::PERMISSION_DELETE) && parent::isDeletable($path);
	}

	#[\Override]
	public function isSharable(string $path): bool {
		return $this->checkMask(Constants::PERMISSION_SHARE) && parent::isSharable($path);
	}

	#[\Override]
	public function getPermissions(string $path): int {
		return $this->getWrapperStorage()->getPermissions($path) & $this->mask;
	}

	#[\Override]
	public function rename(string $source, string $target): bool {
		$required = Constants::PERMISSION_UPDATE;
		if ($this->isPartialFileMove($source, $target) && !$this->file_exists($target)) {
			// This is a move/rename of a partial file to the final file
			$required = Constants::PERMISSION_CREATE;
		}

		return $this->checkMask($required) && parent::rename($source, $target);
	}

	#[\Override]
	public function copy(string $source, string $target): bool {
		return $this->checkMask(Constants::PERMISSION_CREATE) && parent::copy($source, $target);
	}

	#[\Override]
	public function touch(string $path, ?int $mtime = null): bool {
		$permissions = $this->file_exists($path) ? Constants::PERMISSION_UPDATE : Constants::PERMISSION_CREATE;
		return $this->checkMask($permissions) && parent::touch($path, $mtime);
	}

	#[\Override]
	public function mkdir(string $path): bool {
		return $this->checkMask(Constants::PERMISSION_CREATE) && parent::mkdir($path);
	}

	#[\Override]
	public function rmdir(string $path): bool {
		return $this->checkMask(Constants::PERMISSION_DELETE) && parent::rmdir($path);
	}

	#[\Override]
	public function unlink(string $path): bool {
		return $this->checkMask(Constants::PERMISSION_DELETE) && parent::unlink($path);
	}

	#[\Override]
	public function file_put_contents(string $path, mixed $data): int|float|false {
		$permissions = $this->file_exists($path) ? Constants::PERMISSION_UPDATE : Constants::PERMISSION_CREATE;
		return $this->checkMask($permissions) ? parent::file_put_contents($path, $data) : false;
	}

	#[\Override]
	public function fopen(string $path, string $mode) {
		if ($mode === 'r' || $mode === 'rb') {
			return parent::fopen($path, $mode);
		} else {
			$permissions = $this->file_exists($path) ? Constants::PERMISSION_UPDATE : Constants::PERMISSION_CREATE;
			return $this->checkMask($permissions) ? parent::fopen($path, $mode) : false;
		}
	}

	#[\Override]
	public function getCache(string $path = '', ?IStorage $storage = null): ICache {
		if (!$storage) {
			$storage = $this;
		}
		$sourceCache = parent::getCache($path, $storage);
		return new CachePermissionsMask($sourceCache, $this->mask);
	}

	#[\Override]
	public function getMetaData(string $path): ?array {
		$data = parent::getMetaData($path);

		if ($data && isset($data['permissions'])) {
			$data['scan_permissions'] ??= $data['permissions'];
			$data['permissions'] &= $this->mask;
		}
		return $data;
	}

	#[\Override]
	public function getScanner(string $path = '', ?IStorage $storage = null): IScanner {
		if (!$storage) {
			$storage = $this->getWrapperStorage();
		}
		return parent::getScanner($path, $storage);
	}

	#[\Override]
	public function getDirectoryContent(string $directory): \Traversable {
		foreach ($this->getWrapperStorage()->getDirectoryContent($directory) as $data) {
			$data['scan_permissions'] ??= $data['permissions'];
			$data['permissions'] &= $this->mask;

			yield $data;
		}
	}

	/**
	 * If source and target are compatible with a rename/move operation of a partial file into its final location
	 */
	protected function isPartialFileMove(string $source, string $target): bool {
		return str_starts_with(dirname($target), dirname($source)) && preg_match('/\.ocTransferId\d+\.part$/i', $source) === 1;
	}
}
