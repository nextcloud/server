<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage\Wrapper;

use OC\Files\Cache\Wrapper\CacheJail;
use OC\Files\Cache\Wrapper\JailPropagator;
use OC\Files\Cache\Wrapper\JailWatcher;
use OC\Files\Filesystem;
use OC\Files\Storage\FailedStorage;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\IPropagator;
use OCP\Files\Cache\IWatcher;
use OCP\Files\GenericFileException;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IWriteStreamStorage;
use OCP\IDBConnection;
use OCP\Lock\ILockingProvider;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Jail to a subdirectory of the wrapped storage
 *
 * This restricts access to a subfolder of the wrapped storage with the subfolder becoming the root folder new storage
 */
class Jail extends Wrapper {
	/**
	 * @var string
	 */
	protected $rootPath;

	/**
	 * @param array $parameters ['storage' => $storage, 'root' => $root]
	 *
	 * $storage: The storage that will be wrapper
	 * $root: The folder in the wrapped storage that will become the root folder of the wrapped storage
	 */
	public function __construct(array $parameters) {
		parent::__construct($parameters);
		$this->rootPath = $parameters['root'];
	}

	public function getUnjailedPath(string $path): string {
		return trim(Filesystem::normalizePath($this->rootPath . '/' . $path), '/');
	}

	/**
	 * This is separate from Wrapper::getWrapperStorage so we can get the jailed storage consistently even if the jail is inside another wrapper
	 */
	public function getUnjailedStorage(): IStorage {
		if ($this->storage === null) {
			$message = 'storage jail ' . get_class($this) . " doesn't have a wrapped storage set";
			Server::get(LoggerInterface::class)->error($message);
			$this->storage = new FailedStorage(['exception' => new \Exception($message)]);
		}

		return $this->storage;
	}


	public function getJailedPath(string $path): ?string {
		$root = rtrim($this->rootPath, '/') . '/';

		if ($path !== $this->rootPath && !str_starts_with($path, $root)) {
			return null;
		} else {
			$path = substr($path, strlen($this->rootPath));
			return trim($path, '/');
		}
	}

	#[\Override]
	public function getId(): string {
		return parent::getId();
	}

	#[\Override]
	public function mkdir(string $path): bool {
		return $this->getWrapperStorage()->mkdir($this->getUnjailedPath($path));
	}

	#[\Override]
	public function rmdir(string $path): bool {
		return $this->getWrapperStorage()->rmdir($this->getUnjailedPath($path));
	}

	#[\Override]
	public function opendir(string $path) {
		return $this->getWrapperStorage()->opendir($this->getUnjailedPath($path));
	}

	#[\Override]
	public function is_dir(string $path): bool {
		return $this->getWrapperStorage()->is_dir($this->getUnjailedPath($path));
	}

	#[\Override]
	public function is_file(string $path): bool {
		return $this->getWrapperStorage()->is_file($this->getUnjailedPath($path));
	}

	#[\Override]
	public function stat(string $path): array|false {
		return $this->getWrapperStorage()->stat($this->getUnjailedPath($path));
	}

	#[\Override]
	public function filetype(string $path): string|false {
		return $this->getWrapperStorage()->filetype($this->getUnjailedPath($path));
	}

	#[\Override]
	public function filesize(string $path): int|float|false {
		return $this->getWrapperStorage()->filesize($this->getUnjailedPath($path));
	}

	#[\Override]
	public function isCreatable(string $path): bool {
		return $this->getWrapperStorage()->isCreatable($this->getUnjailedPath($path));
	}

	#[\Override]
	public function isReadable(string $path): bool {
		return $this->getWrapperStorage()->isReadable($this->getUnjailedPath($path));
	}

	#[\Override]
	public function isUpdatable(string $path): bool {
		return $this->getWrapperStorage()->isUpdatable($this->getUnjailedPath($path));
	}

	#[\Override]
	public function isDeletable(string $path): bool {
		return $this->getWrapperStorage()->isDeletable($this->getUnjailedPath($path));
	}

	#[\Override]
	public function isSharable(string $path): bool {
		return $this->getWrapperStorage()->isSharable($this->getUnjailedPath($path));
	}

	#[\Override]
	public function getPermissions(string $path): int {
		return $this->getWrapperStorage()->getPermissions($this->getUnjailedPath($path));
	}

	#[\Override]
	public function file_exists(string $path): bool {
		return $this->getWrapperStorage()->file_exists($this->getUnjailedPath($path));
	}

	#[\Override]
	public function filemtime(string $path): int|false {
		return $this->getWrapperStorage()->filemtime($this->getUnjailedPath($path));
	}

	#[\Override]
	public function file_get_contents(string $path): string|false {
		return $this->getWrapperStorage()->file_get_contents($this->getUnjailedPath($path));
	}

	#[\Override]
	public function file_put_contents(string $path, mixed $data): int|float|false {
		return $this->getWrapperStorage()->file_put_contents($this->getUnjailedPath($path), $data);
	}

	#[\Override]
	public function unlink(string $path): bool {
		return $this->getWrapperStorage()->unlink($this->getUnjailedPath($path));
	}

	#[\Override]
	public function rename(string $source, string $target): bool {
		return $this->getWrapperStorage()->rename($this->getUnjailedPath($source), $this->getUnjailedPath($target));
	}

	#[\Override]
	public function copy(string $source, string $target): bool {
		return $this->getWrapperStorage()->copy($this->getUnjailedPath($source), $this->getUnjailedPath($target));
	}

	#[\Override]
	public function fopen(string $path, string $mode) {
		return $this->getWrapperStorage()->fopen($this->getUnjailedPath($path), $mode);
	}

	#[\Override]
	public function getMimeType(string $path): string|false {
		return $this->getWrapperStorage()->getMimeType($this->getUnjailedPath($path));
	}

	#[\Override]
	public function hash(string $type, string $path, bool $raw = false): string|false {
		return $this->getWrapperStorage()->hash($type, $this->getUnjailedPath($path), $raw);
	}

	#[\Override]
	public function free_space(string $path): int|float|false {
		return $this->getWrapperStorage()->free_space($this->getUnjailedPath($path));
	}

	#[\Override]
	public function touch(string $path, ?int $mtime = null): bool {
		return $this->getWrapperStorage()->touch($this->getUnjailedPath($path), $mtime);
	}

	#[\Override]
	public function getLocalFile(string $path): string|false {
		return $this->getWrapperStorage()->getLocalFile($this->getUnjailedPath($path));
	}

	#[\Override]
	public function hasUpdated(string $path, int $time): bool {
		return $this->getWrapperStorage()->hasUpdated($this->getUnjailedPath($path), $time);
	}

	#[\Override]
	public function getCache(string $path = '', ?IStorage $storage = null): ICache {
		$sourceCache = $this->getWrapperStorage()->getCache($this->getUnjailedPath($path));
		return new CacheJail($sourceCache, $this->rootPath);
	}

	#[\Override]
	public function getOwner(string $path): string|false {
		return $this->getWrapperStorage()->getOwner($this->getUnjailedPath($path));
	}

	#[\Override]
	public function getWatcher(string $path = '', ?IStorage $storage = null): IWatcher {
		$sourceWatcher = $this->getWrapperStorage()->getWatcher($this->getUnjailedPath($path), $this->getWrapperStorage());
		return new JailWatcher($sourceWatcher, $this->rootPath);
	}

	#[\Override]
	public function getETag(string $path): string|false {
		return $this->getWrapperStorage()->getETag($this->getUnjailedPath($path));
	}

	#[\Override]
	public function getMetaData(string $path): ?array {
		return $this->getWrapperStorage()->getMetaData($this->getUnjailedPath($path));
	}

	#[\Override]
	public function acquireLock(string $path, int $type, ILockingProvider $provider): void {
		$this->getWrapperStorage()->acquireLock($this->getUnjailedPath($path), $type, $provider);
	}

	#[\Override]
	public function releaseLock(string $path, int $type, ILockingProvider $provider): void {
		$this->getWrapperStorage()->releaseLock($this->getUnjailedPath($path), $type, $provider);
	}

	#[\Override]
	public function changeLock(string $path, int $type, ILockingProvider $provider): void {
		$this->getWrapperStorage()->changeLock($this->getUnjailedPath($path), $type, $provider);
	}

	/**
	 * Resolve the path for the source of the share.
	 *
	 * @return array{0: IStorage, 1: string}
	 */
	public function resolvePath(string $path): array {
		return [$this->getWrapperStorage(), $this->getUnjailedPath($path)];
	}

	#[\Override]
	public function copyFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		if ($sourceStorage === $this) {
			return $this->copy($sourceInternalPath, $targetInternalPath);
		}
		return $this->getWrapperStorage()->copyFromStorage($sourceStorage, $sourceInternalPath, $this->getUnjailedPath($targetInternalPath));
	}

	#[\Override]
	public function moveFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		if ($sourceStorage === $this) {
			return $this->rename($sourceInternalPath, $targetInternalPath);
		}
		return $this->getWrapperStorage()->moveFromStorage($sourceStorage, $sourceInternalPath, $this->getUnjailedPath($targetInternalPath));
	}

	#[\Override]
	public function getPropagator(?IStorage $storage = null): IPropagator {
		if (isset($this->propagator)) {
			return $this->propagator;
		}

		if (!$storage) {
			$storage = $this;
		}
		$this->propagator = new JailPropagator($storage, Server::get(IDBConnection::class));
		return $this->propagator;
	}

	#[\Override]
	public function writeStream(string $path, $stream, ?int $size = null): int {
		$storage = $this->getWrapperStorage();
		if ($storage->instanceOfStorage(IWriteStreamStorage::class)) {
			/** @var IWriteStreamStorage $storage */
			return $storage->writeStream($this->getUnjailedPath($path), $stream, $size);
		} else {
			$target = $this->fopen($path, 'w');
			$count = stream_copy_to_stream($stream, $target);
			fclose($stream);
			fclose($target);
			if ($count === false) {
				throw new GenericFileException('Failed to copy stream.');
			}

			return $count;
		}
	}

	#[\Override]
	public function getDirectoryContent(string $directory): \Traversable {
		return $this->getWrapperStorage()->getDirectoryContent($this->getUnjailedPath($directory));
	}
}
