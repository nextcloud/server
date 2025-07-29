<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage\Wrapper;

use OCP\Files\Storage\IStorage;
use OCP\Files\StorageAuthException;
use OCP\Files\StorageNotAvailableException;
use OCP\IConfig;

/**
 * Availability checker for storages
 *
 * Throws a StorageNotAvailableException for storages with known failures
 */
class Availability extends Wrapper {
	public const RECHECK_TTL_SEC = 600; // 10 minutes

	/** @var IConfig */
	protected $config;

	public function __construct(array $parameters) {
		$this->config = $parameters['config'] ?? \OCP\Server::get(IConfig::class);
		parent::__construct($parameters);
	}

	public static function shouldRecheck($availability): bool {
		if (!$availability['available']) {
			// trigger a recheck if TTL reached
			if ((time() - $availability['last_checked']) > self::RECHECK_TTL_SEC) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Only called if availability === false
	 */
	private function updateAvailability(): bool {
		// reset availability to false so that multiple requests don't recheck concurrently
		$this->setAvailability(false);
		try {
			$result = $this->test();
		} catch (\Exception $e) {
			$result = false;
		}
		$this->setAvailability($result);
		return $result;
	}

	private function isAvailable(): bool {
		$availability = $this->getAvailability();
		if (self::shouldRecheck($availability)) {
			return $this->updateAvailability();
		}
		return $availability['available'];
	}

	/**
	 * @throws StorageNotAvailableException
	 */
	private function checkAvailability(): void {
		if (!$this->isAvailable()) {
			throw new StorageNotAvailableException();
		}
	}

	/**
	 * Handles availability checks and delegates method calls dynamically
	 */
	private function handleAvailability(string $method, mixed ...$args): mixed {
		$this->checkAvailability();
		try {
			return call_user_func_array([parent::class, $method], $args);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function mkdir(string $path): bool {
		return $this->handleAvailability('mkdir', $path);
	}

	public function rmdir(string $path): bool {
		return $this->handleAvailability('rmdir', $path);
	}

	public function opendir(string $path) {
		return $this->handleAvailability('opendir', $path);
	}

	public function is_dir(string $path): bool {
		return $this->handleAvailability('is_dir', $path);
	}

	public function is_file(string $path): bool {
		return $this->handleAvailability('is_file', $path);
	}

	public function stat(string $path): array|false {
		return $this->handleAvailability('stat', $path);
	}

	public function filetype(string $path): string|false {
		return $this->handleAvailability('filetype', $path);
	}

	public function filesize(string $path): int|float|false {
		return $this->handleAvailability('filesize', $path);
	}

	public function isCreatable(string $path): bool {
		return $this->handleAvailability('isCreatable', $path);
	}

	public function isReadable(string $path): bool {
		return $this->handleAvailability('isReadable', $path);
	}

	public function isUpdatable(string $path): bool {
		return $this->handleAvailability('isUpdatable', $path);
	}

	public function isDeletable(string $path): bool {
		return $this->handleAvailability('isDeletable', $path);
	}

	public function isSharable(string $path): bool {
		return $this->handleAvailability('isSharable', $path);
	}

	public function getPermissions(string $path): int {
		return $this->handleAvailability('getPermissions', $path);
	}

	public function file_exists(string $path): bool {
		if ($path === '') {
			return true;
		}
		return $this->handleAvailability('file_exists', $path);
	}

	public function filemtime(string $path): int|false {
		return $this->handleAvailability('filemtime', $path);
	}

	public function file_get_contents(string $path): string|false {
		return $this->handleAvailability('file_get_contents', $path);
	}

	public function file_put_contents(string $path, mixed $data): int|float|false {
		return $this->handleAvailability('file_put_contents', $path, $data);
	}

	public function unlink(string $path): bool {
		return $this->handleAvailability('unlink', $path);
	}

	public function rename(string $source, string $target): bool {
		return $this->handleAvailability('rename', $source, $target);
	}

	public function copy(string $source, string $target): bool {
		return $this->handleAvailability('copy', $source, $target);
	}

	public function fopen(string $path, string $mode) {
		return $this->handleAvailability('fopen', $path, $mode);
	}

	public function getMimeType(string $path): string|false {
		return $this->handleAvailability('getMimeType', $path);
	}

	public function hash(string $type, string $path, bool $raw = false): string|false {
		return $this->handleAvailability('hash', $type, $path, $raw);
	}

	public function free_space(string $path): int|float|false {
		return $this->handleAvailability('free_space', $path);
	}

	public function touch(string $path, ?int $mtime = null): bool {
		return $this->handleAvailability('touch', $path, $mtime);
	}

	public function getLocalFile(string $path): string|false {
		return $this->handleAvailability('getLocalFile', $path);
	}

	public function hasUpdated(string $path, int $time): bool {
		if (!$this->isAvailable()) {
			return false;
		}
		try {
			return parent::hasUpdated($path, $time);
		} catch (StorageNotAvailableException $e) {
			// set unavailable but don't rethrow
			$this->setUnavailable(null);
			return false;
		}
	}

	public function getOwner(string $path): string|false {
		try {
			return parent::getOwner($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function getETag(string $path): string|false {
		return $this->handleAvailability('getETag', $path);
	}

	public function getDirectDownload(string $path): array|false {
		return $this->handleAvailability('getDirectDownload', $path);
	}

	public function copyFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		return $this->handleAvailability('copyFromStorage', $sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	public function moveFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		return $this->handleAvailability('moveFromStorage', $sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	public function getMetaData(string $path): ?array {
		$this->checkAvailability();
		try {
			return parent::getMetaData($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return null;
		}
	}

	/**
	 * @template T of StorageNotAvailableException|null
	 * @param T $e
	 * @psalm-return (T is null ? void : never)
	 * @throws StorageNotAvailableException
	 */
	protected function setUnavailable(?StorageNotAvailableException $e): void {
		$delay = self::RECHECK_TTL_SEC;
		if ($e instanceof StorageAuthException) {
			$delay = max(
				// 30min
				$this->config->getSystemValueInt('external_storage.auth_availability_delay', 1800),
				self::RECHECK_TTL_SEC
			);
		}
		$this->getStorageCache()->setAvailability(false, $delay);
		if ($e !== null) {
			throw $e;
		}
	}



	public function getDirectoryContent(string $directory): \Traversable {
		$this->checkAvailability();
		try {
			return parent::getDirectoryContent($directory);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return new \EmptyIterator();
		}
	}
}
