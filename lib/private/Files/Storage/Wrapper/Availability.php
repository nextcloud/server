<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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
	const RECHECK_TTL_SEC = 600; // 10 minutes

	/** @var IConfig */
	protected $config;

	public function __construct($parameters) {
		$this->config = $parameters['config'] ?? \OC::$server->getConfig();
		parent::__construct($parameters);
	}

	public static function shouldRecheck($availability) {
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
	 *
	 * @return bool
	 */
	private function updateAvailability() {
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

	/**
	 * @return bool
	 */
	private function isAvailable() {
		$availability = $this->getAvailability();
		if (self::shouldRecheck($availability)) {
			return $this->updateAvailability();
		}
		return $availability['available'];
	}

	/**
	 * @throws StorageNotAvailableException
	 */
	private function checkAvailability() {
		if (!$this->isAvailable()) {
			throw new StorageNotAvailableException();
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function mkdir($path): ?bool {
		$this->checkAvailability();
		try {
			return parent::mkdir($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function rmdir($path): ?bool {
		$this->checkAvailability();
		try {
			return parent::rmdir($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return null|resource
	 */
	public function opendir($path) {
		$this->checkAvailability();
		try {
			return parent::opendir($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function is_dir($path): ?bool {
		$this->checkAvailability();
		try {
			return parent::is_dir($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function is_file($path): ?bool {
		$this->checkAvailability();
		try {
			return parent::is_file($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array|null
	 */
	public function stat($path): ?array {
		$this->checkAvailability();
		try {
			return parent::stat($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function filetype($path): ?bool {
		$this->checkAvailability();
		try {
			return parent::filetype($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return int|null
	 */
	public function filesize($path): ?int {
		$this->checkAvailability();
		try {
			return parent::filesize($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function isCreatable($path): ?bool {
		$this->checkAvailability();
		try {
			return parent::isCreatable($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function isReadable($path): ?bool {
		$this->checkAvailability();
		try {
			return parent::isReadable($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function isUpdatable($path): ?bool {
		$this->checkAvailability();
		try {
			return parent::isUpdatable($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function isDeletable($path): ?bool {
		$this->checkAvailability();
		try {
			return parent::isDeletable($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function isSharable($path): ?bool {
		$this->checkAvailability();
		try {
			return parent::isSharable($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return int|null
	 */
	public function getPermissions($path): ?int {
		$this->checkAvailability();
		try {
			return parent::getPermissions($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function file_exists($path): ?bool {
		if ($path === '') {
			return true;
		}
		$this->checkAvailability();
		try {
			return parent::file_exists($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return int|null
	 */
	public function filemtime($path): ?int {
		$this->checkAvailability();
		try {
			return parent::filemtime($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return null|string
	 */
	public function file_get_contents($path): ?string {
		$this->checkAvailability();
		try {
			return parent::file_get_contents($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function file_put_contents($path, $data): ?bool {
		$this->checkAvailability();
		try {
			return parent::file_put_contents($path, $data);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function unlink($path): ?bool {
		$this->checkAvailability();
		try {
			return parent::unlink($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function rename($path1, $path2): ?bool {
		$this->checkAvailability();
		try {
			return parent::rename($path1, $path2);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function copy($path1, $path2): ?bool {
		$this->checkAvailability();
		try {
			return parent::copy($path1, $path2);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return null|resource
	 */
	public function fopen($path, $mode) {
		$this->checkAvailability();
		try {
			return parent::fopen($path, $mode);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return null|string
	 */
	public function getMimeType($path): ?string {
		$this->checkAvailability();
		try {
			return parent::getMimeType($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return null|string
	 */
	public function hash($type, $path, $raw = false): ?string {
		$this->checkAvailability();
		try {
			return parent::hash($type, $path, $raw);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return int|null
	 */
	public function free_space($path): ?int {
		$this->checkAvailability();
		try {
			return parent::free_space($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array|null
	 */
	public function search($query): ?array {
		$this->checkAvailability();
		try {
			return parent::search($query);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function touch($path, $mtime = null): ?bool {
		$this->checkAvailability();
		try {
			return parent::touch($path, $mtime);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return null|string
	 */
	public function getLocalFile($path): ?string {
		$this->checkAvailability();
		try {
			return parent::getLocalFile($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function hasUpdated($path, $time): ?bool {
		$this->checkAvailability();
		try {
			return parent::hasUpdated($path, $time);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return null|string
	 */
	public function getOwner($path): ?string {
		try {
			return parent::getOwner($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return null|string
	 */
	public function getETag($path): ?string {
		$this->checkAvailability();
		try {
			return parent::getETag($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array|null
	 */
	public function getDirectDownload($path): ?array {
		$this->checkAvailability();
		try {
			return parent::getDirectDownload($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath): ?bool {
		$this->checkAvailability();
		try {
			return parent::copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|null
	 */
	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath): ?bool {
		$this->checkAvailability();
		try {
			return parent::moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array|null
	 */
	public function getMetaData($path): ?array {
		$this->checkAvailability();
		try {
			return parent::getMetaData($path);
		} catch (StorageNotAvailableException $e) {
			$this->setUnavailable($e);
		}
	}

	/**
	 * @throws StorageNotAvailableException
	 */
	protected function setUnavailable(StorageNotAvailableException $e) {
		$delay = self::RECHECK_TTL_SEC;
		if($e instanceof StorageAuthException) {
			$delay = max(
				// 30min
				$this->config->getSystemValueInt('external_storage.auth_availability_delay', 1800),
				self::RECHECK_TTL_SEC
			);
		}
		$this->getStorageCache()->setAvailability(false, $delay);
		throw $e;
	}
}
