<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Mount;

use OC\Files\Filesystem;
use OC\Files\Storage\Storage;
use OC\Files\Storage\StorageFactory;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IStorageFactory;
use OCP\Server;
use Psr\Log\LoggerInterface;

class MountPoint implements IMountPoint {
	/** @var IStorage|null $storage */
	protected $storage = null;
	/** @var class-string<IStorage> */
	protected string $class;
	protected ?string $storageId = null;
	protected ?int $numericStorageId = null;
	protected ?int $rootId = null;

	/**
	 * Configuration options for the storage backend
	 */
	protected array $arguments = [];
	protected string $mountPoint;

	/**
	 * Mount specific options
	 */
	protected array $mountOptions = [];
	private IStorageFactory $loader;

	/**
	 * Specified whether the storage is invalid after failing to
	 * instantiate it.
	 */
	private bool $invalidStorage = false;
	protected string $mountProvider;

	/**
	 * @param IStorage|class-string<IStorage> $storage
	 * @param array $arguments (optional) configuration for the storage backend
	 * @param ?array $mountOptions mount specific options
	 * @param ?int $mountId
	 * @param ?string $mountProvider
	 * @throws \Exception
	 */
	public function __construct(
		string|IStorage $storage,
		string $mountpoint,
		?array $arguments = null,
		?IStorageFactory $loader = null,
		?array $mountOptions = null,
		protected ?int $mountId = null,
		?string $mountProvider = null,
	) {
		if (is_null($arguments)) {
			$arguments = [];
		}
		if (is_null($loader)) {
			$this->loader = new StorageFactory();
		} else {
			$this->loader = $loader;
		}

		if (!is_null($mountOptions)) {
			$this->mountOptions = $mountOptions;
		}

		$this->mountPoint = $this->formatPath($mountpoint);
		if ($storage instanceof Storage) {
			$this->class = get_class($storage);
			$this->storage = $this->loader->wrap($this, $storage);
		} else {
			// Update old classes to new namespace
			if (str_contains($storage, 'OC_Filestorage_')) {
				$storage = '\OC\Files\Storage\\' . substr($storage, 15);
			}
			$this->class = $storage;
			$this->arguments = $arguments;
		}
		if ($mountProvider) {
			if (strlen($mountProvider) > 128) {
				throw new \Exception("Mount provider $mountProvider name exceeds the limit of 128 characters");
			}
		}
		$this->mountProvider = $mountProvider ?? '';
	}

	/**
	 * get complete path to the mount point, relative to data/
	 *
	 * @return string
	 */
	public function getMountPoint() {
		return $this->mountPoint;
	}

	/**
	 * Sets the mount point path, relative to data/
	 *
	 * @param string $mountPoint new mount point
	 */
	public function setMountPoint($mountPoint) {
		$this->mountPoint = $this->formatPath($mountPoint);
	}

	/**
	 * create the storage that is mounted
	 */
	private function createStorage() {
		if ($this->invalidStorage) {
			return;
		}

		if (class_exists($this->class)) {
			try {
				$class = $this->class;
				// prevent recursion by setting the storage before applying wrappers
				$this->storage = new $class($this->arguments);
				/** @psalm-suppress UndefinedInterfaceMethod This is a StorageFactory */
				$this->storage = $this->loader->wrap($this, $this->storage);
			} catch (\Exception $exception) {
				$this->storage = null;
				$this->invalidStorage = true;
				if ($this->mountPoint === '/') {
					// the root storage could not be initialized, show the user!
					throw new \Exception('The root storage could not be initialized. Please contact your local administrator.', $exception->getCode(), $exception);
				} else {
					Server::get(LoggerInterface::class)->error($exception->getMessage(), ['exception' => $exception]);
				}
				return;
			}
		} else {
			Server::get(LoggerInterface::class)->error('Storage backend ' . $this->class . ' not found', ['app' => 'core']);
			$this->invalidStorage = true;
		}
	}

	/**
	 * @return IStorage|null
	 */
	public function getStorage() {
		if (is_null($this->storage)) {
			$this->createStorage();
		}
		return $this->storage;
	}

	/**
	 * @return string|null
	 */
	public function getStorageId() {
		if (!$this->storageId) {
			$storage = $this->getStorage();
			if (is_null($storage)) {
				return null;
			}
			$this->storageId = $storage->getId();
			if (strlen($this->storageId) > 64) {
				$this->storageId = md5($this->storageId);
			}
		}
		return $this->storageId;
	}

	/**
	 * @return int
	 */
	public function getNumericStorageId() {
		if (is_null($this->numericStorageId)) {
			$storage = $this->getStorage();
			if (is_null($storage)) {
				return -1;
			}
			$this->numericStorageId = $storage->getCache()->getNumericStorageId();
		}
		return $this->numericStorageId;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function getInternalPath($path) {
		$path = Filesystem::normalizePath($path, true, false, true);
		if ($this->mountPoint === $path || $this->mountPoint . '/' === $path) {
			$internalPath = '';
		} else {
			$internalPath = substr($path, strlen($this->mountPoint));
		}
		// substr returns false instead of an empty string, we always want a string
		return (string)$internalPath;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	private function formatPath($path) {
		$path = Filesystem::normalizePath($path);
		if (strlen($path) > 1) {
			$path .= '/';
		}
		return $path;
	}

	/**
	 * @param callable $wrapper
	 */
	public function wrapStorage($wrapper) {
		$storage = $this->getStorage();
		// storage can be null if it couldn't be initialized
		if ($storage != null) {
			$this->storage = $wrapper($this->mountPoint, $storage, $this);
		}
	}

	/**
	 * Get a mount option
	 *
	 * @param string $name Name of the mount option to get
	 * @param mixed $default Default value for the mount option
	 * @return mixed
	 */
	public function getOption($name, $default) {
		return $this->mountOptions[$name] ?? $default;
	}

	/**
	 * Get all options for the mount
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->mountOptions;
	}

	/**
	 * Get the file id of the root of the storage
	 *
	 * @return int
	 */
	public function getStorageRootId() {
		if (is_null($this->rootId) || $this->rootId === -1) {
			$storage = $this->getStorage();
			// if we can't create the storage return -1 as root id, this is then handled the same as if the root isn't scanned yet
			if ($storage === null) {
				$this->rootId = -1;
			} else {
				$this->rootId = (int)$storage->getCache()->getId('');
			}
		}
		return $this->rootId;
	}

	public function getMountId() {
		return $this->mountId;
	}

	public function getMountType() {
		return '';
	}

	public function getMountProvider(): string {
		return $this->mountProvider;
	}
}
