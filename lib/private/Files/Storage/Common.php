<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage;

use OC\Files\Cache\Cache;
use OC\Files\Cache\CacheDependencies;
use OC\Files\Cache\Propagator;
use OC\Files\Cache\Scanner;
use OC\Files\Cache\Updater;
use OC\Files\Cache\Watcher;
use OC\Files\FilenameValidator;
use OC\Files\Filesystem;
use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\Storage\Wrapper\Encryption;
use OC\Files\Storage\Wrapper\Jail;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Files;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\IPropagator;
use OCP\Files\Cache\IScanner;
use OCP\Files\Cache\IUpdater;
use OCP\Files\Cache\IWatcher;
use OCP\Files\ForbiddenException;
use OCP\Files\GenericFileException;
use OCP\Files\IFilenameValidator;
use OCP\Files\InvalidPathException;
use OCP\Files\Storage\IConstructableStorage;
use OCP\Files\Storage\ILockingStorage;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IWriteStreamStorage;
use OCP\Files\StorageNotAvailableException;
use OCP\IConfig;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Storage backend class for providing common filesystem operation methods
 * which are not storage-backend specific.
 *
 * \OC\Files\Storage\Common is never used directly; it is extended by all other
 * storage backends, where its methods may be overridden, and additional
 * (backend-specific) methods are defined.
 *
 * Some \OC\Files\Storage\Common methods call functions which are first defined
 * in classes which extend it, e.g. $this->stat() .
 */
abstract class Common implements Storage, ILockingStorage, IWriteStreamStorage, IConstructableStorage {
	use LocalTempFileTrait;

	protected ?Cache $cache = null;
	protected ?Scanner $scanner = null;
	protected ?Watcher $watcher = null;
	protected ?Propagator $propagator = null;
	protected $storageCache;
	protected ?Updater $updater = null;

	protected array $mountOptions = [];
	protected $owner = null;

	private ?bool $shouldLogLocks = null;
	private ?LoggerInterface $logger = null;
	private ?IFilenameValidator $filenameValidator = null;

	public function __construct(array $parameters) {
	}

	protected function remove(string $path): bool {
		if ($this->file_exists($path)) {
			if ($this->is_dir($path)) {
				return $this->rmdir($path);
			} elseif ($this->is_file($path)) {
				return $this->unlink($path);
			}
		}
		return false;
	}

	public function is_dir(string $path): bool {
		return $this->filetype($path) === 'dir';
	}

	public function is_file(string $path): bool {
		return $this->filetype($path) === 'file';
	}

	public function filesize(string $path): int|float|false {
		if ($this->is_dir($path)) {
			return 0; //by definition
		} else {
			$stat = $this->stat($path);
			return isset($stat['size']) ? $stat['size'] : 0;
		}
	}

	public function isReadable(string $path): bool {
		// at least check whether it exists
		// subclasses might want to implement this more thoroughly
		return $this->file_exists($path);
	}

	public function isUpdatable(string $path): bool {
		// at least check whether it exists
		// subclasses might want to implement this more thoroughly
		// a non-existing file/folder isn't updatable
		return $this->file_exists($path);
	}

	public function isCreatable(string $path): bool {
		if ($this->is_dir($path) && $this->isUpdatable($path)) {
			return true;
		}
		return false;
	}

	public function isDeletable(string $path): bool {
		if ($path === '' || $path === '/') {
			return $this->isUpdatable($path);
		}
		$parent = dirname($path);
		return $this->isUpdatable($parent) && $this->isUpdatable($path);
	}

	public function isSharable(string $path): bool {
		return $this->isReadable($path);
	}

	public function getPermissions(string $path): int {
		$permissions = 0;
		if ($this->isCreatable($path)) {
			$permissions |= \OCP\Constants::PERMISSION_CREATE;
		}
		if ($this->isReadable($path)) {
			$permissions |= \OCP\Constants::PERMISSION_READ;
		}
		if ($this->isUpdatable($path)) {
			$permissions |= \OCP\Constants::PERMISSION_UPDATE;
		}
		if ($this->isDeletable($path)) {
			$permissions |= \OCP\Constants::PERMISSION_DELETE;
		}
		if ($this->isSharable($path)) {
			$permissions |= \OCP\Constants::PERMISSION_SHARE;
		}
		return $permissions;
	}

	public function filemtime(string $path): int|false {
		$stat = $this->stat($path);
		if (isset($stat['mtime']) && $stat['mtime'] > 0) {
			return $stat['mtime'];
		} else {
			return 0;
		}
	}

	public function file_get_contents(string $path): string|false {
		$handle = $this->fopen($path, 'r');
		if (!$handle) {
			return false;
		}
		$data = stream_get_contents($handle);
		fclose($handle);
		return $data;
	}

	public function file_put_contents(string $path, mixed $data): int|float|false {
		$handle = $this->fopen($path, 'w');
		if (!$handle) {
			return false;
		}
		$this->removeCachedFile($path);
		$count = fwrite($handle, $data);
		fclose($handle);
		return $count;
	}

	public function rename(string $source, string $target): bool {
		$this->remove($target);

		$this->removeCachedFile($source);
		return $this->copy($source, $target) and $this->remove($source);
	}

	public function copy(string $source, string $target): bool {
		if ($this->is_dir($source)) {
			$this->remove($target);
			$dir = $this->opendir($source);
			$this->mkdir($target);
			while (($file = readdir($dir)) !== false) {
				if (!Filesystem::isIgnoredDir($file)) {
					if (!$this->copy($source . '/' . $file, $target . '/' . $file)) {
						closedir($dir);
						return false;
					}
				}
			}
			closedir($dir);
			return true;
		} else {
			$sourceStream = $this->fopen($source, 'r');
			$targetStream = $this->fopen($target, 'w');
			[, $result] = Files::streamCopy($sourceStream, $targetStream, true);
			if (!$result) {
				Server::get(LoggerInterface::class)->warning("Failed to write data while copying $source to $target");
			}
			$this->removeCachedFile($target);
			return $result;
		}
	}

	public function getMimeType(string $path): string|false {
		if ($this->is_dir($path)) {
			return 'httpd/unix-directory';
		} elseif ($this->file_exists($path)) {
			return \OC::$server->getMimeTypeDetector()->detectPath($path);
		} else {
			return false;
		}
	}

	public function hash(string $type, string $path, bool $raw = false): string|false {
		$fh = $this->fopen($path, 'rb');
		if (!$fh) {
			return false;
		}
		$ctx = hash_init($type);
		hash_update_stream($ctx, $fh);
		fclose($fh);
		return hash_final($ctx, $raw);
	}

	public function getLocalFile(string $path): string|false {
		return $this->getCachedFile($path);
	}

	private function addLocalFolder(string $path, string $target): void {
		$dh = $this->opendir($path);
		if (is_resource($dh)) {
			while (($file = readdir($dh)) !== false) {
				if (!Filesystem::isIgnoredDir($file)) {
					if ($this->is_dir($path . '/' . $file)) {
						mkdir($target . '/' . $file);
						$this->addLocalFolder($path . '/' . $file, $target . '/' . $file);
					} else {
						$tmp = $this->toTmpFile($path . '/' . $file);
						rename($tmp, $target . '/' . $file);
					}
				}
			}
		}
	}

	protected function searchInDir(string $query, string $dir = ''): array {
		$files = [];
		$dh = $this->opendir($dir);
		if (is_resource($dh)) {
			while (($item = readdir($dh)) !== false) {
				if (Filesystem::isIgnoredDir($item)) {
					continue;
				}
				if (strstr(strtolower($item), strtolower($query)) !== false) {
					$files[] = $dir . '/' . $item;
				}
				if ($this->is_dir($dir . '/' . $item)) {
					$files = array_merge($files, $this->searchInDir($query, $dir . '/' . $item));
				}
			}
		}
		closedir($dh);
		return $files;
	}

	/**
	 * @inheritDoc
	 * Check if a file or folder has been updated since $time
	 *
	 * The method is only used to check if the cache needs to be updated. Storage backends that don't support checking
	 * the mtime should always return false here. As a result storage implementations that always return false expect
	 * exclusive access to the backend and will not pick up files that have been added in a way that circumvents
	 * Nextcloud filesystem.
	 */
	public function hasUpdated(string $path, int $time): bool {
		return $this->filemtime($path) > $time;
	}

	protected function getCacheDependencies(): CacheDependencies {
		static $dependencies = null;
		if (!$dependencies) {
			$dependencies = Server::get(CacheDependencies::class);
		}
		return $dependencies;
	}

	public function getCache(string $path = '', ?IStorage $storage = null): ICache {
		if (!$storage) {
			$storage = $this;
		}
		/** @var self $storage */
		if (!isset($storage->cache)) {
			$storage->cache = new Cache($storage, $this->getCacheDependencies());
		}
		return $storage->cache;
	}

	public function getScanner(string $path = '', ?IStorage $storage = null): IScanner {
		if (!$storage) {
			$storage = $this;
		}
		if (!$storage->instanceOfStorage(self::class)) {
			throw new \InvalidArgumentException('Storage is not of the correct class');
		}
		if (!isset($storage->scanner)) {
			$storage->scanner = new Scanner($storage);
		}
		return $storage->scanner;
	}

	public function getWatcher(string $path = '', ?IStorage $storage = null): IWatcher {
		if (!$storage) {
			$storage = $this;
		}
		if (!isset($this->watcher)) {
			$this->watcher = new Watcher($storage);
			$globalPolicy = Server::get(IConfig::class)->getSystemValueInt('filesystem_check_changes', Watcher::CHECK_NEVER);
			$this->watcher->setPolicy((int)$this->getMountOption('filesystem_check_changes', $globalPolicy));
		}
		return $this->watcher;
	}

	public function getPropagator(?IStorage $storage = null): IPropagator {
		if (!$storage) {
			$storage = $this;
		}
		if (!$storage->instanceOfStorage(self::class)) {
			throw new \InvalidArgumentException('Storage is not of the correct class');
		}
		/** @var self $storage */
		if (!isset($storage->propagator)) {
			$config = Server::get(IConfig::class);
			$storage->propagator = new Propagator($storage, \OC::$server->getDatabaseConnection(), ['appdata_' . $config->getSystemValueString('instanceid')]);
		}
		return $storage->propagator;
	}

	public function getUpdater(?IStorage $storage = null): IUpdater {
		if (!$storage) {
			$storage = $this;
		}
		if (!$storage->instanceOfStorage(self::class)) {
			throw new \InvalidArgumentException('Storage is not of the correct class');
		}
		/** @var self $storage */
		if (!isset($storage->updater)) {
			$storage->updater = new Updater($storage);
		}
		return $storage->updater;
	}

	public function getStorageCache(?IStorage $storage = null): \OC\Files\Cache\Storage {
		/** @var Cache $cache */
		$cache = $this->getCache(storage: $storage);
		return $cache->getStorageCache();
	}

	public function getOwner(string $path): string|false {
		if ($this->owner === null) {
			$this->owner = \OC_User::getUser();
		}

		return $this->owner;
	}

	public function getETag(string $path): string|false {
		return uniqid();
	}

	/**
	 * clean a path, i.e. remove all redundant '.' and '..'
	 * making sure that it can't point to higher than '/'
	 *
	 * @param string $path The path to clean
	 * @return string cleaned path
	 */
	public function cleanPath(string $path): string {
		if (strlen($path) == 0 || $path[0] != '/') {
			$path = '/' . $path;
		}

		$output = [];
		foreach (explode('/', $path) as $chunk) {
			if ($chunk == '..') {
				array_pop($output);
			} elseif ($chunk == '.') {
			} else {
				$output[] = $chunk;
			}
		}
		return implode('/', $output);
	}

	/**
	 * Test a storage for availability
	 */
	public function test(): bool {
		try {
			if ($this->stat('')) {
				return true;
			}
			Server::get(LoggerInterface::class)->info('External storage not available: stat() failed');
			return false;
		} catch (\Exception $e) {
			Server::get(LoggerInterface::class)->warning(
				'External storage not available: ' . $e->getMessage(),
				['exception' => $e]
			);
			return false;
		}
	}

	public function free_space(string $path): int|float|false {
		return \OCP\Files\FileInfo::SPACE_UNKNOWN;
	}

	public function isLocal(): bool {
		// the common implementation returns a temporary file by
		// default, which is not local
		return false;
	}

	/**
	 * Check if the storage is an instance of $class or is a wrapper for a storage that is an instance of $class
	 */
	public function instanceOfStorage(string $class): bool {
		if (ltrim($class, '\\') === 'OC\Files\Storage\Shared') {
			// FIXME Temporary fix to keep existing checks working
			$class = '\OCA\Files_Sharing\SharedStorage';
		}
		return is_a($this, $class);
	}

	/**
	 * A custom storage implementation can return an url for direct download of a give file.
	 *
	 * For now the returned array can hold the parameter url - in future more attributes might follow.
	 */
	public function getDirectDownload(string $path): array|false {
		return [];
	}

	public function verifyPath(string $path, string $fileName): void {
		$this->getFilenameValidator()
			->validateFilename($fileName);

		// verify also the path is valid
		if ($path && $path !== '/' && $path !== '.') {
			try {
				$this->verifyPath(dirname($path), basename($path));
			} catch (InvalidPathException $e) {
				// Ignore invalid file type exceptions on directories
				if ($e->getCode() !== FilenameValidator::INVALID_FILE_TYPE) {
					$l = \OCP\Util::getL10N('lib');
					throw new InvalidPathException($l->t('Invalid parent path'), previous: $e);
				}
			}
		}
	}

	/**
	 * Get the filename validator
	 * (cached for performance)
	 */
	protected function getFilenameValidator(): IFilenameValidator {
		if ($this->filenameValidator === null) {
			$this->filenameValidator = Server::get(IFilenameValidator::class);
		}
		return $this->filenameValidator;
	}

	public function setMountOptions(array $options): void {
		$this->mountOptions = $options;
	}

	public function getMountOption(string $name, mixed $default = null): mixed {
		return $this->mountOptions[$name] ?? $default;
	}

	public function copyFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath, bool $preserveMtime = false): bool {
		if ($sourceStorage === $this) {
			return $this->copy($sourceInternalPath, $targetInternalPath);
		}

		if ($sourceStorage->is_dir($sourceInternalPath)) {
			$dh = $sourceStorage->opendir($sourceInternalPath);
			$result = $this->mkdir($targetInternalPath);
			if (is_resource($dh)) {
				$result = true;
				while ($result && ($file = readdir($dh)) !== false) {
					if (!Filesystem::isIgnoredDir($file)) {
						$result &= $this->copyFromStorage($sourceStorage, $sourceInternalPath . '/' . $file, $targetInternalPath . '/' . $file);
					}
				}
			}
		} else {
			$source = $sourceStorage->fopen($sourceInternalPath, 'r');
			$result = false;
			if ($source) {
				try {
					$this->writeStream($targetInternalPath, $source);
					$result = true;
				} catch (\Exception $e) {
					Server::get(LoggerInterface::class)->warning('Failed to copy stream to storage', ['exception' => $e]);
				}
			}

			if ($result && $preserveMtime) {
				$mtime = $sourceStorage->filemtime($sourceInternalPath);
				$this->touch($targetInternalPath, is_int($mtime) ? $mtime : null);
			}

			if (!$result) {
				// delete partially written target file
				$this->unlink($targetInternalPath);
				// delete cache entry that was created by fopen
				$this->getCache()->remove($targetInternalPath);
			}
		}
		return (bool)$result;
	}

	/**
	 * Check if a storage is the same as the current one, including wrapped storages
	 */
	private function isSameStorage(IStorage $storage): bool {
		while ($storage->instanceOfStorage(Wrapper::class)) {
			/**
			 * @var Wrapper $storage
			 */
			$storage = $storage->getWrapperStorage();
		}

		return $storage === $this;
	}

	public function moveFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		if (
			!$sourceStorage->instanceOfStorage(Encryption::class)
			&& $this->isSameStorage($sourceStorage)
		) {
			// resolve any jailed paths
			while ($sourceStorage->instanceOfStorage(Jail::class)) {
				/**
				 * @var Jail $sourceStorage
				 */
				$sourceInternalPath = $sourceStorage->getUnjailedPath($sourceInternalPath);
				$sourceStorage = $sourceStorage->getUnjailedStorage();
			}

			return $this->rename($sourceInternalPath, $targetInternalPath);
		}

		if (!$sourceStorage->isDeletable($sourceInternalPath)) {
			return false;
		}

		$result = $this->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath, true);
		if ($result) {
			if ($sourceStorage->instanceOfStorage(ObjectStoreStorage::class)) {
				/** @var ObjectStoreStorage $sourceStorage */
				$sourceStorage->setPreserveCacheOnDelete(true);
			}
			try {
				if ($sourceStorage->is_dir($sourceInternalPath)) {
					$result = $sourceStorage->rmdir($sourceInternalPath);
				} else {
					$result = $sourceStorage->unlink($sourceInternalPath);
				}
			} finally {
				if ($sourceStorage->instanceOfStorage(ObjectStoreStorage::class)) {
					/** @var ObjectStoreStorage $sourceStorage */
					$sourceStorage->setPreserveCacheOnDelete(false);
				}
			}
		}
		return $result;
	}

	public function getMetaData(string $path): ?array {
		if (Filesystem::isFileBlacklisted($path)) {
			throw new ForbiddenException('Invalid path: ' . $path, false);
		}

		$permissions = $this->getPermissions($path);
		if (!$permissions & \OCP\Constants::PERMISSION_READ) {
			//can't read, nothing we can do
			return null;
		}

		$data = [];
		$data['mimetype'] = $this->getMimeType($path);
		$data['mtime'] = $this->filemtime($path);
		if ($data['mtime'] === false) {
			$data['mtime'] = time();
		}
		if ($data['mimetype'] == 'httpd/unix-directory') {
			$data['size'] = -1; //unknown
		} else {
			$data['size'] = $this->filesize($path);
		}
		$data['etag'] = $this->getETag($path);
		$data['storage_mtime'] = $data['mtime'];
		$data['permissions'] = $permissions;
		$data['name'] = basename($path);

		return $data;
	}

	public function acquireLock(string $path, int $type, ILockingProvider $provider): void {
		$logger = $this->getLockLogger();
		if ($logger) {
			$typeString = ($type === ILockingProvider::LOCK_SHARED) ? 'shared' : 'exclusive';
			$logger->info(
				sprintf(
					'acquire %s lock on "%s" on storage "%s"',
					$typeString,
					$path,
					$this->getId()
				),
				[
					'app' => 'locking',
				]
			);
		}
		try {
			$provider->acquireLock('files/' . md5($this->getId() . '::' . trim($path, '/')), $type, $this->getId() . '::' . $path);
		} catch (LockedException $e) {
			$e = new LockedException($e->getPath(), $e, $e->getExistingLock(), $path);
			if ($logger) {
				$logger->info($e->getMessage(), ['exception' => $e]);
			}
			throw $e;
		}
	}

	public function releaseLock(string $path, int $type, ILockingProvider $provider): void {
		$logger = $this->getLockLogger();
		if ($logger) {
			$typeString = ($type === ILockingProvider::LOCK_SHARED) ? 'shared' : 'exclusive';
			$logger->info(
				sprintf(
					'release %s lock on "%s" on storage "%s"',
					$typeString,
					$path,
					$this->getId()
				),
				[
					'app' => 'locking',
				]
			);
		}
		try {
			$provider->releaseLock('files/' . md5($this->getId() . '::' . trim($path, '/')), $type);
		} catch (LockedException $e) {
			$e = new LockedException($e->getPath(), $e, $e->getExistingLock(), $path);
			if ($logger) {
				$logger->info($e->getMessage(), ['exception' => $e]);
			}
			throw $e;
		}
	}

	public function changeLock(string $path, int $type, ILockingProvider $provider): void {
		$logger = $this->getLockLogger();
		if ($logger) {
			$typeString = ($type === ILockingProvider::LOCK_SHARED) ? 'shared' : 'exclusive';
			$logger->info(
				sprintf(
					'change lock on "%s" to %s on storage "%s"',
					$path,
					$typeString,
					$this->getId()
				),
				[
					'app' => 'locking',
				]
			);
		}
		try {
			$provider->changeLock('files/' . md5($this->getId() . '::' . trim($path, '/')), $type);
		} catch (LockedException $e) {
			$e = new LockedException($e->getPath(), $e, $e->getExistingLock(), $path);
			if ($logger) {
				$logger->info($e->getMessage(), ['exception' => $e]);
			}
			throw $e;
		}
	}

	private function getLockLogger(): ?LoggerInterface {
		if (is_null($this->shouldLogLocks)) {
			$this->shouldLogLocks = Server::get(IConfig::class)->getSystemValueBool('filelocking.debug', false);
			$this->logger = $this->shouldLogLocks ? Server::get(LoggerInterface::class) : null;
		}
		return $this->logger;
	}

	/**
	 * @return array{available: bool, last_checked: int}
	 */
	public function getAvailability(): array {
		return $this->getStorageCache()->getAvailability();
	}

	public function setAvailability(bool $isAvailable): void {
		$this->getStorageCache()->setAvailability($isAvailable);
	}

	public function setOwner(?string $user): void {
		$this->owner = $user;
	}

	public function needsPartFile(): bool {
		return true;
	}

	public function writeStream(string $path, $stream, ?int $size = null): int {
		$target = $this->fopen($path, 'w');
		if (!$target) {
			throw new GenericFileException("Failed to open $path for writing");
		}
		try {
			[$count, $result] = Files::streamCopy($stream, $target, true);
			if (!$result) {
				throw new GenericFileException('Failed to copy stream');
			}
		} finally {
			fclose($target);
			fclose($stream);
		}
		return $count;
	}

	public function getDirectoryContent(string $directory): \Traversable {
		$dh = $this->opendir($directory);

		if ($dh === false) {
			throw new StorageNotAvailableException('Directory listing failed');
		}

		if (is_resource($dh)) {
			$basePath = rtrim($directory, '/');
			while (($file = readdir($dh)) !== false) {
				if (!Filesystem::isIgnoredDir($file)) {
					$childPath = $basePath . '/' . trim($file, '/');
					$metadata = $this->getMetaData($childPath);
					if ($metadata !== null) {
						yield $metadata;
					}
				}
			}
		}
	}
}
