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
		$this->config = $parameters['config'] ?? \OC::$server->getConfig();
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

	public function mkdir(string $path): bool {
		$this->checkAvailability();
		try {
			return parent::mkdir($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function rmdir(string $path): bool {
		$this->checkAvailability();
		try {
			return parent::rmdir($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function opendir(string $path) {
		$this->checkAvailability();
		try {
			return parent::opendir($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function is_dir(string $path): bool {
		$this->checkAvailability();
		try {
			return parent::is_dir($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function is_file(string $path): bool {
		$this->checkAvailability();
		try {
			return parent::is_file($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function stat(string $path): array|false {
		$this->checkAvailability();
		try {
			return parent::stat($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function filetype(string $path): string|false {
		$this->checkAvailability();
		try {
			return parent::filetype($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function filesize(string $path): int|float|false {
		$this->checkAvailability();
		try {
			return parent::filesize($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function isCreatable(string $path): bool {
		$this->checkAvailability();
		try {
			return parent::isCreatable($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function isReadable(string $path): bool {
		$this->checkAvailability();
		try {
			return parent::isReadable($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function isUpdatable(string $path): bool {
		$this->checkAvailability();
		try {
			return parent::isUpdatable($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function isDeletable(string $path): bool {
		$this->checkAvailability();
		try {
			return parent::isDeletable($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function isSharable(string $path): bool {
		$this->checkAvailability();
		try {
			return parent::isSharable($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function getPermissions(string $path): int {
		$this->checkAvailability();
		try {
			return parent::getPermissions($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return 0;
		}
	}

	public function file_exists(string $path): bool {
		if ($path === '') {
			return true;
		}
		$this->checkAvailability();
		try {
			return parent::file_exists($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function filemtime(string $path): int|false {
		$this->checkAvailability();
		try {
			return parent::filemtime($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function file_get_contents(string $path): string|false {
		$this->checkAvailability();
		try {
			return parent::file_get_contents($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function file_put_contents(string $path, mixed $data): int|float|false {
		$this->checkAvailability();
		try {
			return parent::file_put_contents($path, $data);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function unlink(string $path): bool {
		$this->checkAvailability();
		try {
			return parent::unlink($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function rename(string $source, string $target): bool {
		$this->checkAvailability();
		try {
			return parent::rename($source, $target);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function copy(string $source, string $target): bool {
		$this->checkAvailability();
		try {
			return parent::copy($source, $target);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function fopen(string $path, string $mode) {
		$this->checkAvailability();
		try {
			return parent::fopen($path, $mode);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function getMimeType(string $path): string|false {
		$this->checkAvailability();
		try {
			return parent::getMimeType($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function hash(string $type, string $path, bool $raw = false): string|false {
		$this->checkAvailability();
		try {
			return parent::hash($type, $path, $raw);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function free_space(string $path): int|float|false {
		$this->checkAvailability();
		try {
			return parent::free_space($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function touch(string $path, ?int $mtime = null): bool {
		$this->checkAvailability();
		try {
			return parent::touch($path, $mtime);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function getLocalFile(string $path): string|false {
		$this->checkAvailability();
		try {
			return parent::getLocalFile($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
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
		$this->checkAvailability();
		try {
			return parent::getETag($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function getDirectDownload(string $path): array|false {
		$this->checkAvailability();
		try {
			return parent::getDirectDownload($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function copyFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		$this->checkAvailability();
		try {
			return parent::copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function moveFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		$this->checkAvailability();
		try {
			return parent::moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
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
