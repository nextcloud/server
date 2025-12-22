<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage\Wrapper;

use OC\Files\Storage\FailedStorage;
use OC\Files\Storage\Storage;
use OCP\Files;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\IPropagator;
use OCP\Files\Cache\IScanner;
use OCP\Files\Cache\IUpdater;
use OCP\Files\Cache\IWatcher;
use OCP\Files\Storage\ILockingStorage;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IWriteStreamStorage;
use OCP\Lock\ILockingProvider;
use OCP\Server;
use Override;
use Psr\Log\LoggerInterface;

class Wrapper implements Storage, ILockingStorage, IWriteStreamStorage {
	/**
	 * @var Storage $storage
	 */
	protected $storage;

	public $cache;
	public $scanner;
	public $watcher;
	public $propagator;
	public $updater;

	/**
	 * @param array $parameters
	 */
	public function __construct(array $parameters) {
		$this->storage = $parameters['storage'];
	}

	public function getWrapperStorage(): Storage {
		if (!$this->storage) {
			$message = 'storage wrapper ' . get_class($this) . " doesn't have a wrapped storage set";
			$logger = Server::get(LoggerInterface::class);
			$logger->error($message);
			$this->storage = new FailedStorage(['exception' => new \Exception($message)]);
		}
		return $this->storage;
	}

	public function getId(): string {
		return $this->getWrapperStorage()->getId();
	}

	public function mkdir(string $path): bool {
		return $this->getWrapperStorage()->mkdir($path);
	}

	public function rmdir(string $path): bool {
		return $this->getWrapperStorage()->rmdir($path);
	}

	public function opendir(string $path) {
		return $this->getWrapperStorage()->opendir($path);
	}

	public function is_dir(string $path): bool {
		return $this->getWrapperStorage()->is_dir($path);
	}

	public function is_file(string $path): bool {
		return $this->getWrapperStorage()->is_file($path);
	}

	public function stat(string $path): array|false {
		return $this->getWrapperStorage()->stat($path);
	}

	public function filetype(string $path): string|false {
		return $this->getWrapperStorage()->filetype($path);
	}

	public function filesize(string $path): int|float|false {
		return $this->getWrapperStorage()->filesize($path);
	}

	public function isCreatable(string $path): bool {
		return $this->getWrapperStorage()->isCreatable($path);
	}

	public function isReadable(string $path): bool {
		return $this->getWrapperStorage()->isReadable($path);
	}

	public function isUpdatable(string $path): bool {
		return $this->getWrapperStorage()->isUpdatable($path);
	}

	public function isDeletable(string $path): bool {
		return $this->getWrapperStorage()->isDeletable($path);
	}

	public function isSharable(string $path): bool {
		return $this->getWrapperStorage()->isSharable($path);
	}

	public function getPermissions(string $path): int {
		return $this->getWrapperStorage()->getPermissions($path);
	}

	public function file_exists(string $path): bool {
		return $this->getWrapperStorage()->file_exists($path);
	}

	public function filemtime(string $path): int|false {
		return $this->getWrapperStorage()->filemtime($path);
	}

	public function file_get_contents(string $path): string|false {
		return $this->getWrapperStorage()->file_get_contents($path);
	}

	public function file_put_contents(string $path, mixed $data): int|float|false {
		return $this->getWrapperStorage()->file_put_contents($path, $data);
	}

	public function unlink(string $path): bool {
		return $this->getWrapperStorage()->unlink($path);
	}

	public function rename(string $source, string $target): bool {
		return $this->getWrapperStorage()->rename($source, $target);
	}

	public function copy(string $source, string $target): bool {
		return $this->getWrapperStorage()->copy($source, $target);
	}

	public function fopen(string $path, string $mode) {
		return $this->getWrapperStorage()->fopen($path, $mode);
	}

	public function getMimeType(string $path): string|false {
		return $this->getWrapperStorage()->getMimeType($path);
	}

	public function hash(string $type, string $path, bool $raw = false): string|false {
		return $this->getWrapperStorage()->hash($type, $path, $raw);
	}

	public function free_space(string $path): int|float|false {
		return $this->getWrapperStorage()->free_space($path);
	}

	public function touch(string $path, ?int $mtime = null): bool {
		return $this->getWrapperStorage()->touch($path, $mtime);
	}

	public function getLocalFile(string $path): string|false {
		return $this->getWrapperStorage()->getLocalFile($path);
	}

	public function hasUpdated(string $path, int $time): bool {
		return $this->getWrapperStorage()->hasUpdated($path, $time);
	}

	public function getCache(string $path = '', ?IStorage $storage = null): ICache {
		if (!$storage) {
			$storage = $this;
		}
		return $this->getWrapperStorage()->getCache($path, $storage);
	}

	public function getScanner(string $path = '', ?IStorage $storage = null): IScanner {
		if (!$storage) {
			$storage = $this;
		}
		return $this->getWrapperStorage()->getScanner($path, $storage);
	}

	public function getOwner(string $path): string|false {
		return $this->getWrapperStorage()->getOwner($path);
	}

	public function getWatcher(string $path = '', ?IStorage $storage = null): IWatcher {
		if (!$storage) {
			$storage = $this;
		}
		return $this->getWrapperStorage()->getWatcher($path, $storage);
	}

	public function getPropagator(?IStorage $storage = null): IPropagator {
		if (!$storage) {
			$storage = $this;
		}
		return $this->getWrapperStorage()->getPropagator($storage);
	}

	public function getUpdater(?IStorage $storage = null): IUpdater {
		if (!$storage) {
			$storage = $this;
		}
		return $this->getWrapperStorage()->getUpdater($storage);
	}

	public function getStorageCache(): \OC\Files\Cache\Storage {
		return $this->getWrapperStorage()->getStorageCache();
	}

	public function getETag(string $path): string|false {
		return $this->getWrapperStorage()->getETag($path);
	}

	public function test(): bool {
		return $this->getWrapperStorage()->test();
	}

	public function isLocal(): bool {
		return $this->getWrapperStorage()->isLocal();
	}

	public function instanceOfStorage(string $class): bool {
		if (ltrim($class, '\\') === 'OC\Files\Storage\Shared') {
			// FIXME Temporary fix to keep existing checks working
			$class = '\OCA\Files_Sharing\SharedStorage';
		}
		return is_a($this, $class) || $this->getWrapperStorage()->instanceOfStorage($class);
	}

	/**
	 * @psalm-template T of IStorage
	 * @psalm-param class-string<T> $class
	 * @psalm-return T|null
	 */
	public function getInstanceOfStorage(string $class): ?IStorage {
		$storage = $this;
		while ($storage instanceof Wrapper) {
			if ($storage instanceof $class) {
				break;
			}
			$storage = $storage->getWrapperStorage();
		}
		if (!($storage instanceof $class)) {
			return null;
		}
		return $storage;
	}

	/**
	 * Pass any methods custom to specific storage implementations to the wrapped storage
	 *
	 * @return mixed
	 */
	public function __call(string $method, array $args) {
		return call_user_func_array([$this->getWrapperStorage(), $method], $args);
	}

	#[Override]
	public function getDirectDownload(string $path): array|false {
		return $this->getWrapperStorage()->getDirectDownload($path);
	}

	#[Override]
	public function getDirectDownloadById(string $fileId): array|false {
		return $this->getWrapperStorage()->getDirectDownloadById($fileId);
	}

	public function getAvailability(): array {
		return $this->getWrapperStorage()->getAvailability();
	}

	public function setAvailability(bool $isAvailable): void {
		$this->getWrapperStorage()->setAvailability($isAvailable);
	}

	public function verifyPath(string $path, string $fileName): void {
		$this->getWrapperStorage()->verifyPath($path, $fileName);
	}

	public function copyFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		if ($sourceStorage === $this) {
			return $this->copy($sourceInternalPath, $targetInternalPath);
		}

		return $this->getWrapperStorage()->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	public function moveFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		if ($sourceStorage === $this) {
			return $this->rename($sourceInternalPath, $targetInternalPath);
		}

		return $this->getWrapperStorage()->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	public function getMetaData(string $path): ?array {
		return $this->getWrapperStorage()->getMetaData($path);
	}

	public function acquireLock(string $path, int $type, ILockingProvider $provider): void {
		if ($this->getWrapperStorage()->instanceOfStorage('\OCP\Files\Storage\ILockingStorage')) {
			$this->getWrapperStorage()->acquireLock($path, $type, $provider);
		}
	}

	public function releaseLock(string $path, int $type, ILockingProvider $provider): void {
		if ($this->getWrapperStorage()->instanceOfStorage('\OCP\Files\Storage\ILockingStorage')) {
			$this->getWrapperStorage()->releaseLock($path, $type, $provider);
		}
	}

	public function changeLock(string $path, int $type, ILockingProvider $provider): void {
		if ($this->getWrapperStorage()->instanceOfStorage('\OCP\Files\Storage\ILockingStorage')) {
			$this->getWrapperStorage()->changeLock($path, $type, $provider);
		}
	}

	public function needsPartFile(): bool {
		return $this->getWrapperStorage()->needsPartFile();
	}

	public function writeStream(string $path, $stream, ?int $size = null): int {
		$storage = $this->getWrapperStorage();
		if ($storage->instanceOfStorage(IWriteStreamStorage::class)) {
			/** @var IWriteStreamStorage $storage */
			return $storage->writeStream($path, $stream, $size);
		} else {
			$target = $this->fopen($path, 'w');
			$count = Files::streamCopy($stream, $target);
			fclose($stream);
			fclose($target);
			return $count;
		}
	}

	public function getDirectoryContent(string $directory): \Traversable {
		return $this->getWrapperStorage()->getDirectoryContent($directory);
	}

	public function isWrapperOf(IStorage $storage): bool {
		$wrapped = $this->getWrapperStorage();
		if ($wrapped === $storage) {
			return true;
		}
		if ($wrapped instanceof Wrapper) {
			return $wrapped->isWrapperOf($storage);
		}
		return false;
	}

	public function setOwner(?string $user): void {
		$this->getWrapperStorage()->setOwner($user);
	}
}
