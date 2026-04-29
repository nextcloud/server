<?php

declare(strict_types=1);

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
use OCP\Files\GenericFileException;
use OCP\Files\Storage\ILockingStorage;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IWriteStreamStorage;
use OCP\Lock\ILockingProvider;
use OCP\Server;
use Override;
use Psr\Log\LoggerInterface;

class Wrapper implements Storage, ILockingStorage, IWriteStreamStorage {
	protected ?IStorage $storage = null;

	public ?ICache $cache = null;

	public ?IScanner $scanner = null;

	public ?IWatcher $watcher = null;

	public ?IPropagator $propagator = null;

	public ?IUpdater $updater = null;

	/**
	 * @param array{storage: IStorage, ...} $parameters
	 */
	public function __construct(array $parameters) {
		$this->storage = $parameters['storage'];
	}

	public function getWrapperStorage(): Storage {
		if (!$this->storage instanceof Storage) {
			$message = 'storage wrapper ' . static::class . " doesn't have a wrapped storage set";
			Server::get(LoggerInterface::class)->error($message);
			$this->storage = new FailedStorage(['exception' => new \Exception($message)]);
		}

		return $this->storage;
	}

	#[\Override]
	public function getId(): string {
		return $this->getWrapperStorage()->getId();
	}

	#[\Override]
	public function mkdir(string $path): bool {
		return $this->getWrapperStorage()->mkdir($path);
	}

	#[\Override]
	public function rmdir(string $path): bool {
		return $this->getWrapperStorage()->rmdir($path);
	}

	#[\Override]
	public function opendir(string $path) {
		return $this->getWrapperStorage()->opendir($path);
	}

	#[\Override]
	public function is_dir(string $path): bool {
		return $this->getWrapperStorage()->is_dir($path);
	}

	#[\Override]
	public function is_file(string $path): bool {
		return $this->getWrapperStorage()->is_file($path);
	}

	#[\Override]
	public function stat(string $path): array|false {
		return $this->getWrapperStorage()->stat($path);
	}

	#[\Override]
	public function filetype(string $path): string|false {
		return $this->getWrapperStorage()->filetype($path);
	}

	#[\Override]
	public function filesize(string $path): int|float|false {
		return $this->getWrapperStorage()->filesize($path);
	}

	#[\Override]
	public function isCreatable(string $path): bool {
		return $this->getWrapperStorage()->isCreatable($path);
	}

	#[\Override]
	public function isReadable(string $path): bool {
		return $this->getWrapperStorage()->isReadable($path);
	}

	#[\Override]
	public function isUpdatable(string $path): bool {
		return $this->getWrapperStorage()->isUpdatable($path);
	}

	#[\Override]
	public function isDeletable(string $path): bool {
		return $this->getWrapperStorage()->isDeletable($path);
	}

	#[\Override]
	public function isSharable(string $path): bool {
		return $this->getWrapperStorage()->isSharable($path);
	}

	#[\Override]
	public function getPermissions(string $path): int {
		return $this->getWrapperStorage()->getPermissions($path);
	}

	#[\Override]
	public function file_exists(string $path): bool {
		return $this->getWrapperStorage()->file_exists($path);
	}

	#[\Override]
	public function filemtime(string $path): int|false {
		return $this->getWrapperStorage()->filemtime($path);
	}

	#[\Override]
	public function file_get_contents(string $path): string|false {
		return $this->getWrapperStorage()->file_get_contents($path);
	}

	#[\Override]
	public function file_put_contents(string $path, mixed $data): int|float|false {
		return $this->getWrapperStorage()->file_put_contents($path, $data);
	}

	#[\Override]
	public function unlink(string $path): bool {
		return $this->getWrapperStorage()->unlink($path);
	}

	#[\Override]
	public function rename(string $source, string $target): bool {
		return $this->getWrapperStorage()->rename($source, $target);
	}

	#[\Override]
	public function copy(string $source, string $target): bool {
		return $this->getWrapperStorage()->copy($source, $target);
	}

	#[\Override]
	public function fopen(string $path, string $mode) {
		return $this->getWrapperStorage()->fopen($path, $mode);
	}

	#[\Override]
	public function getMimeType(string $path): string|false {
		return $this->getWrapperStorage()->getMimeType($path);
	}

	#[\Override]
	public function hash(string $type, string $path, bool $raw = false): string|false {
		return $this->getWrapperStorage()->hash($type, $path, $raw);
	}

	#[\Override]
	public function free_space(string $path): int|float|false {
		return $this->getWrapperStorage()->free_space($path);
	}

	#[\Override]
	public function touch(string $path, ?int $mtime = null): bool {
		return $this->getWrapperStorage()->touch($path, $mtime);
	}

	#[\Override]
	public function getLocalFile(string $path): string|false {
		return $this->getWrapperStorage()->getLocalFile($path);
	}

	#[\Override]
	public function hasUpdated(string $path, int $time): bool {
		return $this->getWrapperStorage()->hasUpdated($path, $time);
	}

	#[\Override]
	public function getCache(string $path = '', ?IStorage $storage = null): ICache {
		if (!$storage instanceof IStorage) {
			$storage = $this;
		}

		return $this->getWrapperStorage()->getCache($path, $storage);
	}

	#[\Override]
	public function getScanner(string $path = '', ?IStorage $storage = null): IScanner {
		if (!$storage instanceof IStorage) {
			$storage = $this;
		}

		return $this->getWrapperStorage()->getScanner($path, $storage);
	}

	#[\Override]
	public function getOwner(string $path): string|false {
		return $this->getWrapperStorage()->getOwner($path);
	}

	#[\Override]
	public function getWatcher(string $path = '', ?IStorage $storage = null): IWatcher {
		if (!$storage instanceof IStorage) {
			$storage = $this;
		}

		return $this->getWrapperStorage()->getWatcher($path, $storage);
	}

	#[\Override]
	public function getPropagator(?IStorage $storage = null): IPropagator {
		if (!$storage instanceof IStorage) {
			$storage = $this;
		}

		return $this->getWrapperStorage()->getPropagator($storage);
	}

	#[\Override]
	public function getUpdater(?IStorage $storage = null): IUpdater {
		if (!$storage instanceof IStorage) {
			$storage = $this;
		}

		return $this->getWrapperStorage()->getUpdater($storage);
	}

	#[\Override]
	public function getStorageCache(): \OC\Files\Cache\Storage {
		return $this->getWrapperStorage()->getStorageCache();
	}

	#[\Override]
	public function getETag(string $path): string|false {
		return $this->getWrapperStorage()->getETag($path);
	}

	#[\Override]
	public function test(): bool {
		return $this->getWrapperStorage()->test();
	}

	#[\Override]
	public function isLocal(): bool {
		return $this->getWrapperStorage()->isLocal();
	}

	#[\Override]
	public function instanceOfStorage(string $class): bool {
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
		/** @psalm-suppress DeprecatedMethod */
		return $this->getWrapperStorage()->getDirectDownload($path);
	}

	#[Override]
	public function getDirectDownloadById(string $fileId): array|false {
		return $this->getWrapperStorage()->getDirectDownloadById($fileId);
	}

	#[\Override]
	public function getAvailability(): array {
		return $this->getWrapperStorage()->getAvailability();
	}

	#[\Override]
	public function setAvailability(bool $isAvailable): void {
		$this->getWrapperStorage()->setAvailability($isAvailable);
	}

	#[\Override]
	public function verifyPath(string $path, string $fileName): void {
		$this->getWrapperStorage()->verifyPath($path, $fileName);
	}

	#[\Override]
	public function copyFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		if ($sourceStorage === $this) {
			return $this->copy($sourceInternalPath, $targetInternalPath);
		}

		return $this->getWrapperStorage()->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	#[\Override]
	public function moveFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		if ($sourceStorage === $this) {
			return $this->rename($sourceInternalPath, $targetInternalPath);
		}

		return $this->getWrapperStorage()->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	#[\Override]
	public function getMetaData(string $path): ?array {
		return $this->getWrapperStorage()->getMetaData($path);
	}

	#[\Override]
	public function acquireLock(string $path, int $type, ILockingProvider $provider): void {
		$storage = $this->getWrapperStorage();
		if ($storage->instanceOfStorage(ILockingStorage::class)) {
			$storage->acquireLock($path, $type, $provider);
		}
	}

	#[\Override]
	public function releaseLock(string $path, int $type, ILockingProvider $provider): void {
		$storage = $this->getWrapperStorage();
		if ($storage->instanceOfStorage(ILockingStorage::class)) {
			$storage->releaseLock($path, $type, $provider);
		}
	}

	#[\Override]
	public function changeLock(string $path, int $type, ILockingProvider $provider): void {
		$storage = $this->getWrapperStorage();
		if ($storage->instanceOfStorage(ILockingStorage::class)) {
			$storage->changeLock($path, $type, $provider);
		}
	}

	#[\Override]
	public function needsPartFile(): bool {
		return $this->getWrapperStorage()->needsPartFile();
	}

	#[\Override]
	public function writeStream(string $path, $stream, ?int $size = null): int {
		$storage = $this->getWrapperStorage();
		if ($storage->instanceOfStorage(IWriteStreamStorage::class)) {
			/** @var IWriteStreamStorage $storage */
			return $storage->writeStream($path, $stream, $size);
		}

		$target = $this->fopen($path, 'w');
		if ($target === false) {
			throw new GenericFileException('Failed to open ' . $path);
		}

		$count = stream_copy_to_stream($stream, $target);
		fclose($stream);
		fclose($target);
		if ($count === false) {
			throw new GenericFileException('Failed to copy stream.');
		}

		return $count;
	}

	#[\Override]
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

	#[\Override]
	public function setOwner(?string $user): void {
		$this->getWrapperStorage()->setOwner($user);
	}
}
