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

	public function __construct($parameters) {
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

	public function mkdir($path): bool {
		$this->checkAvailability();
		try {
			return parent::mkdir($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function rmdir($path): bool {
		$this->checkAvailability();
		try {
			return parent::rmdir($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function opendir($path) {
		$this->checkAvailability();
		try {
			return parent::opendir($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function is_dir($path): bool {
		$this->checkAvailability();
		try {
			return parent::is_dir($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function is_file($path): bool {
		$this->checkAvailability();
		try {
			return parent::is_file($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function stat($path): array|false {
		$this->checkAvailability();
		try {
			return parent::stat($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function filetype($path): string|false {
		$this->checkAvailability();
		try {
			return parent::filetype($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function filesize($path): int|float|false {
		$this->checkAvailability();
		try {
			return parent::filesize($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function isCreatable($path): bool {
		$this->checkAvailability();
		try {
			return parent::isCreatable($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function isReadable($path): bool {
		$this->checkAvailability();
		try {
			return parent::isReadable($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function isUpdatable($path): bool {
		$this->checkAvailability();
		try {
			return parent::isUpdatable($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function isDeletable($path): bool {
		$this->checkAvailability();
		try {
			return parent::isDeletable($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function isSharable($path): bool {
		$this->checkAvailability();
		try {
			return parent::isSharable($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function getPermissions($path): int {
		$this->checkAvailability();
		try {
			return parent::getPermissions($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return 0;
		}
	}

	public function file_exists($path): bool {
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

	public function filemtime($path): int|false {
		$this->checkAvailability();
		try {
			return parent::filemtime($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function file_get_contents($path): string|false {
		$this->checkAvailability();
		try {
			return parent::file_get_contents($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function file_put_contents($path, $data): int|float|false {
		$this->checkAvailability();
		try {
			return parent::file_put_contents($path, $data);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function unlink($path): bool {
		$this->checkAvailability();
		try {
			return parent::unlink($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function rename($source, $target): bool {
		$this->checkAvailability();
		try {
			return parent::rename($source, $target);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function copy($source, $target): bool {
		$this->checkAvailability();
		try {
			return parent::copy($source, $target);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function fopen($path, $mode) {
		$this->checkAvailability();
		try {
			return parent::fopen($path, $mode);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function getMimeType($path): string|false {
		$this->checkAvailability();
		try {
			return parent::getMimeType($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function hash($type, $path, $raw = false): string|false {
		$this->checkAvailability();
		try {
			return parent::hash($type, $path, $raw);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function free_space($path): int|float|false {
		$this->checkAvailability();
		try {
			return parent::free_space($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function touch($path, $mtime = null): bool {
		$this->checkAvailability();
		try {
			return parent::touch($path, $mtime);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function getLocalFile($path): string|false {
		$this->checkAvailability();
		try {
			return parent::getLocalFile($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function hasUpdated($path, $time): bool {
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

	public function getOwner($path): string|false {
		try {
			return parent::getOwner($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function getETag($path): string|false {
		$this->checkAvailability();
		try {
			return parent::getETag($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function getDirectDownload($path): array|false {
		$this->checkAvailability();
		try {
			return parent::getDirectDownload($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath): bool {
		$this->checkAvailability();
		try {
			return parent::copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath): bool {
		$this->checkAvailability();
		try {
			return parent::moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}

	public function getMetaData($path): ?array {
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



	public function getDirectoryContent($directory): \Traversable|false {
		$this->checkAvailability();
		try {
			return parent::getDirectoryContent($directory);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
			return false;
		}
	}
}
