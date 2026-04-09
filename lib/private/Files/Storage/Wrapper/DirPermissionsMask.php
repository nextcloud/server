<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only AND (AGPL-3.0-or-later OR AGPL-3.0-only)
 */

namespace OC\Files\Storage\Wrapper;

use OC\Files\Cache\Wrapper\CacheDirPermissionsMask;
use OC\Files\Storage\Storage;
use OCP\Files\Cache\ICache;

/**
 * While PermissionMask can mask a whole storage this  can
 * mask a certain directory inside a storage
 */
class DirPermissionsMask extends PermissionsMask {

	/**
	 * @var string the dir that should be masked
	 */
	private readonly string $path;

	/**
	 * @var int remember length
	 */
	private readonly int $pathLength;

	/**
	 * @param array{storage: Storage, mask: int, path: string, ...} $parameters
	 * @psalm-suppress MoreSpecificImplementedParamType
	 *
	 * $storage: The storage the permissions mask should be applied on
	 * $mask: The permission bits that should be kept, a combination of the \OCP\Constant::PERMISSION_ constants
	 * $path: The path relative to the storage root that should be masked
	 */
	public function __construct($parameters) {
		parent::__construct($parameters);
		$this->path = rtrim((string)$parameters['path'], '/');
		$this->pathLength = strlen((string)$parameters['path']);
	}

	protected function checkPath(string $path): bool {
		return $path === $this->path || substr($path, 0, $this->pathLength + 1) === $this->path . '/';
	}

	public function isUpdatable($path): bool {
		if ($this->checkPath($path)) {
			return parent::isUpdatable($path);
		}

		return $this->storage->isUpdatable($path);
	}

	public function isCreatable($path): bool {
		if ($this->checkPath($path)) {
			return parent::isCreatable($path);
		}

		return $this->storage->isCreatable($path);
	}

	public function isDeletable($path): bool {
		if ($this->checkPath($path)) {
			return parent::isDeletable($path);
		}

		return $this->storage->isDeletable($path);
	}

	public function isSharable($path): bool {
		if ($this->checkPath($path)) {
			return parent::isSharable($path);
		}

		return $this->storage->isSharable($path);
	}

	public function getPermissions($path): int {
		if ($this->checkPath($path)) {
			return parent::getPermissions($path);
		}

		return $this->storage->getPermissions($path);
	}

	public function rename($source, $target): bool {
		if (!$this->isUpdatable($source)) {
			return false;
		}

		if ($this->file_exists($target)) {
			if ($this->isUpdatable($target)) {
				return $this->storage->rename($source, $target);
			}
		} else {
			$parent = dirname($target);
			if ($parent === '.') {
				$parent = '';
			}

			if ($this->isCreatable($parent)) {
				return $this->storage->rename($source, $target);
			}
		}

		return false;
	}

	public function copy($source, $target): bool {
		if (!$this->isReadable($source)) {
			return false;
		}

		if ($this->file_exists($target)) {
			if ($this->isUpdatable($target)) {
				return $this->storage->copy($source, $target);
			}
		} else {
			$parent = dirname($target);
			if ($parent === '.') {
				$parent = '';
			}

			if ($this->isCreatable($parent)) {
				return $this->storage->copy($source, $target);
			}
		}

		return false;
	}

	public function touch($path, $mtime = null): bool {
		if ($this->checkPath($path)) {
			return parent::touch($path);
		}

		return $this->storage->touch($path);
	}

	public function mkdir($path): bool {
		// Always allow creating the path of the dir mask.
		if ($path !== $this->path && $this->checkPath($path)) {
			return parent::mkdir($path);
		}

		return $this->storage->mkdir($path);
	}

	public function rmdir($path): bool {
		if ($this->checkPath($path)) {
			return parent::rmdir($path);
		}

		return $this->storage->rmdir($path);
	}

	public function unlink($path): bool {
		if ($this->checkPath($path)) {
			return parent::unlink($path);
		}

		return $this->storage->unlink($path);
	}

	public function file_put_contents($path, $data): int|float|false {
		if ($this->checkPath($path)) {
			return parent::file_put_contents($path, $data);
		}

		return $this->storage->file_put_contents($path, $data);
	}

	public function fopen($path, $mode) {
		if ($this->checkPath($path)) {
			return parent::fopen($path, $mode);
		}

		return $this->storage->fopen($path, $mode);
	}

	public function getCache($path = '', $storage = null): ICache {
		if (!$storage) {
			$storage = $this;
		}

		$sourceCache = $this->storage->getCache($path, $storage);
		return new CacheDirPermissionsMask($sourceCache, $this->mask, $this->checkPath(...));
	}
}
