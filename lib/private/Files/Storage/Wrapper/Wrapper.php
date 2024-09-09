<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage\Wrapper;

use OC\Files\Storage\FailedStorage;
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

	public function __construct($parameters) {
		$this->storage = $parameters['storage'];
	}

	/**
	 * @return \OC\Files\Storage\Storage
	 */
	public function getWrapperStorage() {
		if (!$this->storage) {
			$message = 'storage wrapper ' . get_class($this) . " doesn't have a wrapped storage set";
			$logger = Server::get(LoggerInterface::class);
			$logger->error($message);
			$this->storage = new FailedStorage(['exception' => new \Exception($message)]);
		}
		return $this->storage;
	}

	public function getId() {
		return $this->getWrapperStorage()->getId();
	}

	public function mkdir($path) {
		return $this->getWrapperStorage()->mkdir($path);
	}

	public function rmdir($path) {
		return $this->getWrapperStorage()->rmdir($path);
	}

	public function opendir($path) {
		return $this->getWrapperStorage()->opendir($path);
	}

	public function is_dir($path) {
		return $this->getWrapperStorage()->is_dir($path);
	}

	public function is_file($path) {
		return $this->getWrapperStorage()->is_file($path);
	}

	public function stat($path) {
		return $this->getWrapperStorage()->stat($path);
	}

	public function filetype($path) {
		return $this->getWrapperStorage()->filetype($path);
	}

	public function filesize($path): false|int|float {
		return $this->getWrapperStorage()->filesize($path);
	}

	public function isCreatable($path) {
		return $this->getWrapperStorage()->isCreatable($path);
	}

	public function isReadable($path) {
		return $this->getWrapperStorage()->isReadable($path);
	}

	public function isUpdatable($path) {
		return $this->getWrapperStorage()->isUpdatable($path);
	}

	public function isDeletable($path) {
		return $this->getWrapperStorage()->isDeletable($path);
	}

	public function isSharable($path) {
		return $this->getWrapperStorage()->isSharable($path);
	}

	public function getPermissions($path) {
		return $this->getWrapperStorage()->getPermissions($path);
	}

	public function file_exists($path) {
		return $this->getWrapperStorage()->file_exists($path);
	}

	public function filemtime($path) {
		return $this->getWrapperStorage()->filemtime($path);
	}

	public function file_get_contents($path) {
		return $this->getWrapperStorage()->file_get_contents($path);
	}

	public function file_put_contents($path, $data) {
		return $this->getWrapperStorage()->file_put_contents($path, $data);
	}

	public function unlink($path) {
		return $this->getWrapperStorage()->unlink($path);
	}

	public function rename($source, $target) {
		return $this->getWrapperStorage()->rename($source, $target);
	}

	public function copy($source, $target) {
		return $this->getWrapperStorage()->copy($source, $target);
	}

	public function fopen($path, $mode) {
		return $this->getWrapperStorage()->fopen($path, $mode);
	}

	public function getMimeType($path) {
		return $this->getWrapperStorage()->getMimeType($path);
	}

	public function hash($type, $path, $raw = false) {
		return $this->getWrapperStorage()->hash($type, $path, $raw);
	}

	public function free_space($path) {
		return $this->getWrapperStorage()->free_space($path);
	}

	/**
	 * search for occurrences of $query in file names
	 *
	 * @param string $query
	 * @return array|bool
	 */
	public function search($query) {
		return $this->getWrapperStorage()->search($query);
	}

	public function touch($path, $mtime = null) {
		return $this->getWrapperStorage()->touch($path, $mtime);
	}

	public function getLocalFile($path) {
		return $this->getWrapperStorage()->getLocalFile($path);
	}

	public function hasUpdated($path, $time) {
		return $this->getWrapperStorage()->hasUpdated($path, $time);
	}

	public function getCache($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		return $this->getWrapperStorage()->getCache($path, $storage);
	}

	public function getScanner($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		return $this->getWrapperStorage()->getScanner($path, $storage);
	}


	public function getOwner($path) {
		return $this->getWrapperStorage()->getOwner($path);
	}

	public function getWatcher($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		return $this->getWrapperStorage()->getWatcher($path, $storage);
	}

	public function getPropagator($storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		return $this->getWrapperStorage()->getPropagator($storage);
	}

	public function getUpdater($storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		return $this->getWrapperStorage()->getUpdater($storage);
	}

	/**
	 * @return \OC\Files\Cache\Storage
	 */
	public function getStorageCache() {
		return $this->getWrapperStorage()->getStorageCache();
	}

	public function getETag($path) {
		return $this->getWrapperStorage()->getETag($path);
	}

	public function test() {
		return $this->getWrapperStorage()->test();
	}

	public function isLocal() {
		return $this->getWrapperStorage()->isLocal();
	}

	public function instanceOfStorage($class) {
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
	public function getInstanceOfStorage(string $class) {
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

	public function getDirectDownload($path) {
		return $this->getWrapperStorage()->getDirectDownload($path);
	}

	public function getAvailability() {
		return $this->getWrapperStorage()->getAvailability();
	}

	public function setAvailability($isAvailable) {
		$this->getWrapperStorage()->setAvailability($isAvailable);
	}

	public function verifyPath($path, $fileName) {
		$this->getWrapperStorage()->verifyPath($path, $fileName);
	}

	public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		if ($sourceStorage === $this) {
			return $this->copy($sourceInternalPath, $targetInternalPath);
		}

		return $this->getWrapperStorage()->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		if ($sourceStorage === $this) {
			return $this->rename($sourceInternalPath, $targetInternalPath);
		}

		return $this->getWrapperStorage()->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	public function getMetaData($path) {
		return $this->getWrapperStorage()->getMetaData($path);
	}

	public function acquireLock($path, $type, ILockingProvider $provider) {
		if ($this->getWrapperStorage()->instanceOfStorage('\OCP\Files\Storage\ILockingStorage')) {
			$this->getWrapperStorage()->acquireLock($path, $type, $provider);
		}
	}

	public function releaseLock($path, $type, ILockingProvider $provider) {
		if ($this->getWrapperStorage()->instanceOfStorage('\OCP\Files\Storage\ILockingStorage')) {
			$this->getWrapperStorage()->releaseLock($path, $type, $provider);
		}
	}

	public function changeLock($path, $type, ILockingProvider $provider) {
		if ($this->getWrapperStorage()->instanceOfStorage('\OCP\Files\Storage\ILockingStorage')) {
			$this->getWrapperStorage()->changeLock($path, $type, $provider);
		}
	}

	/**
	 * @return bool
	 */
	public function needsPartFile() {
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

	public function getDirectoryContent($directory): \Traversable {
		return $this->getWrapperStorage()->getDirectoryContent($directory);
	}

	public function isWrapperOf(IStorage $storage) {
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
