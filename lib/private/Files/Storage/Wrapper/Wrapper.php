<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage\Wrapper;

use OC\Files\Storage\FailedStorage;
use OC\Files\Storage\Storage;
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
use Psr\Log\LoggerInterface;

class Wrapper implements \OC\Files\Storage\Storage, ILockingStorage, IWriteStreamStorage {
	/**
	 * @var \OC\Files\Storage\Storage $storage
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
	public function __construct($parameters) {
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

	public function mkdir($path): bool {
		return $this->getWrapperStorage()->mkdir($path);
	}

	public function rmdir($path): bool {
		return $this->getWrapperStorage()->rmdir($path);
	}

	public function opendir($path) {
		return $this->getWrapperStorage()->opendir($path);
	}

	public function is_dir($path): bool {
		return $this->getWrapperStorage()->is_dir($path);
	}

	public function is_file($path): bool {
		return $this->getWrapperStorage()->is_file($path);
	}

	public function stat($path): array|false {
		return $this->getWrapperStorage()->stat($path);
	}

	public function filetype($path): string|false {
		return $this->getWrapperStorage()->filetype($path);
	}

	public function filesize($path): int|float|false {
		return $this->getWrapperStorage()->filesize($path);
	}

	public function isCreatable($path): bool {
		return $this->getWrapperStorage()->isCreatable($path);
	}

	public function isReadable($path): bool {
		return $this->getWrapperStorage()->isReadable($path);
	}

	public function isUpdatable($path): bool {
		return $this->getWrapperStorage()->isUpdatable($path);
	}

	public function isDeletable($path): bool {
		return $this->getWrapperStorage()->isDeletable($path);
	}

	public function isSharable($path): bool {
		return $this->getWrapperStorage()->isSharable($path);
	}

	public function getPermissions($path): int {
		return $this->getWrapperStorage()->getPermissions($path);
	}

	public function file_exists($path): bool {
		return $this->getWrapperStorage()->file_exists($path);
	}

	public function filemtime($path): int|false {
		return $this->getWrapperStorage()->filemtime($path);
	}

	public function file_get_contents($path): string|false {
		return $this->getWrapperStorage()->file_get_contents($path);
	}

	public function file_put_contents($path, $data): int|float|false {
		return $this->getWrapperStorage()->file_put_contents($path, $data);
	}

	public function unlink($path): bool {
		return $this->getWrapperStorage()->unlink($path);
	}

	public function rename($source, $target): bool {
		return $this->getWrapperStorage()->rename($source, $target);
	}

	public function copy($source, $target): bool {
		return $this->getWrapperStorage()->copy($source, $target);
	}

	public function fopen($path, $mode) {
		return $this->getWrapperStorage()->fopen($path, $mode);
	}

	public function getMimeType($path): string|false {
		return $this->getWrapperStorage()->getMimeType($path);
	}

	public function hash($type, $path, $raw = false): string|false {
		return $this->getWrapperStorage()->hash($type, $path, $raw);
	}

	public function free_space($path): int|float|false {
		return $this->getWrapperStorage()->free_space($path);
	}

	public function touch($path, $mtime = null): bool {
		return $this->getWrapperStorage()->touch($path, $mtime);
	}

	public function getLocalFile($path): string|false {
		return $this->getWrapperStorage()->getLocalFile($path);
	}

	public function hasUpdated($path, $time): bool {
		return $this->getWrapperStorage()->hasUpdated($path, $time);
	}

	public function getCache($path = '', $storage = null): ICache {
		if (!$storage) {
			$storage = $this;
		}
		return $this->getWrapperStorage()->getCache($path, $storage);
	}

	public function getScanner($path = '', $storage = null): IScanner {
		if (!$storage) {
			$storage = $this;
		}
		return $this->getWrapperStorage()->getScanner($path, $storage);
	}

	public function getOwner($path): string|false {
		return $this->getWrapperStorage()->getOwner($path);
	}

	public function getWatcher($path = '', $storage = null): IWatcher {
		if (!$storage) {
			$storage = $this;
		}
		return $this->getWrapperStorage()->getWatcher($path, $storage);
	}

	public function getPropagator($storage = null): IPropagator {
		if (!$storage) {
			$storage = $this;
		}
		return $this->getWrapperStorage()->getPropagator($storage);
	}

	public function getUpdater($storage = null): IUpdater {
		if (!$storage) {
			$storage = $this;
		}
		return $this->getWrapperStorage()->getUpdater($storage);
	}

	public function getStorageCache(): \OC\Files\Cache\Storage {
		return $this->getWrapperStorage()->getStorageCache();
	}

	public function getETag($path): string|false {
		return $this->getWrapperStorage()->getETag($path);
	}

	public function test(): bool {
		return $this->getWrapperStorage()->test();
	}

	public function isLocal(): bool {
		return $this->getWrapperStorage()->isLocal();
	}

	public function instanceOfStorage($class): bool {
		if (ltrim($class, '\\') === 'OC\Files\Storage\Shared') {
			// FIXME Temporary fix to keep existing checks working
			$class = '\OCA\Files_Sharing\SharedStorage';
		}
		return is_a($this, $class) or $this->getWrapperStorage()->instanceOfStorage($class);
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
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 */
	public function __call($method, $args) {
		return call_user_func_array([$this->getWrapperStorage(), $method], $args);
	}

	public function getDirectDownload($path): array|false {
		return $this->getWrapperStorage()->getDirectDownload($path);
	}

	public function getAvailability(): array {
		return $this->getWrapperStorage()->getAvailability();
	}

	public function setAvailability($isAvailable): void {
		$this->getWrapperStorage()->setAvailability($isAvailable);
	}

	public function verifyPath($path, $fileName): void {
		$this->getWrapperStorage()->verifyPath($path, $fileName);
	}

	public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath): bool {
		if ($sourceStorage === $this) {
			return $this->copy($sourceInternalPath, $targetInternalPath);
		}

		return $this->getWrapperStorage()->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath): bool {
		if ($sourceStorage === $this) {
			return $this->rename($sourceInternalPath, $targetInternalPath);
		}

		return $this->getWrapperStorage()->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	public function getMetaData($path): ?array {
		return $this->getWrapperStorage()->getMetaData($path);
	}

	public function acquireLock($path, $type, ILockingProvider $provider): void {
		if ($this->getWrapperStorage()->instanceOfStorage('\OCP\Files\Storage\ILockingStorage')) {
			$this->getWrapperStorage()->acquireLock($path, $type, $provider);
		}
	}

	public function releaseLock($path, $type, ILockingProvider $provider): void {
		if ($this->getWrapperStorage()->instanceOfStorage('\OCP\Files\Storage\ILockingStorage')) {
			$this->getWrapperStorage()->releaseLock($path, $type, $provider);
		}
	}

	public function changeLock($path, $type, ILockingProvider $provider): void {
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
			[$count, $result] = \OC_Helper::streamCopy($stream, $target);
			fclose($stream);
			fclose($target);
			return $count;
		}
	}

	public function getDirectoryContent($directory): \Traversable|false {
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
