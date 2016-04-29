<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Files\Storage\Wrapper;

/**
 * Availability checker for storages
 *
 * Throws a StorageNotAvailableException for storages with known failures
 */
class Availability extends Wrapper {
	const RECHECK_TTL_SEC = 600; // 10 minutes

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
	 * @throws \OCP\Files\StorageNotAvailableException
	 */
	private function checkAvailability() {
		if (!$this->isAvailable()) {
			throw new \OCP\Files\StorageNotAvailableException();
		}
	}

	/** {@inheritdoc} */
	public function mkdir($path) {
		$this->checkAvailability();
		try {
			return parent::mkdir($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function rmdir($path) {
		$this->checkAvailability();
		try {
			return parent::rmdir($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function opendir($path) {
		$this->checkAvailability();
		try {
			return parent::opendir($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function is_dir($path) {
		$this->checkAvailability();
		try {
			return parent::is_dir($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function is_file($path) {
		$this->checkAvailability();
		try {
			return parent::is_file($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function stat($path) {
		$this->checkAvailability();
		try {
			return parent::stat($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function filetype($path) {
		$this->checkAvailability();
		try {
			return parent::filetype($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function filesize($path) {
		$this->checkAvailability();
		try {
			return parent::filesize($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function isCreatable($path) {
		$this->checkAvailability();
		try {
			return parent::isCreatable($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function isReadable($path) {
		$this->checkAvailability();
		try {
			return parent::isReadable($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function isUpdatable($path) {
		$this->checkAvailability();
		try {
			return parent::isUpdatable($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function isDeletable($path) {
		$this->checkAvailability();
		try {
			return parent::isDeletable($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function isSharable($path) {
		$this->checkAvailability();
		try {
			return parent::isSharable($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function getPermissions($path) {
		$this->checkAvailability();
		try {
			return parent::getPermissions($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function file_exists($path) {
		if ($path === '') {
			return true;
		}
		$this->checkAvailability();
		try {
			return parent::file_exists($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function filemtime($path) {
		$this->checkAvailability();
		try {
			return parent::filemtime($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function file_get_contents($path) {
		$this->checkAvailability();
		try {
			return parent::file_get_contents($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function file_put_contents($path, $data) {
		$this->checkAvailability();
		try {
			return parent::file_put_contents($path, $data);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function unlink($path) {
		$this->checkAvailability();
		try {
			return parent::unlink($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function rename($path1, $path2) {
		$this->checkAvailability();
		try {
			return parent::rename($path1, $path2);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function copy($path1, $path2) {
		$this->checkAvailability();
		try {
			return parent::copy($path1, $path2);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function fopen($path, $mode) {
		$this->checkAvailability();
		try {
			return parent::fopen($path, $mode);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function getMimeType($path) {
		$this->checkAvailability();
		try {
			return parent::getMimeType($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function hash($type, $path, $raw = false) {
		$this->checkAvailability();
		try {
			return parent::hash($type, $path, $raw);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function free_space($path) {
		$this->checkAvailability();
		try {
			return parent::free_space($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function search($query) {
		$this->checkAvailability();
		try {
			return parent::search($query);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function touch($path, $mtime = null) {
		$this->checkAvailability();
		try {
			return parent::touch($path, $mtime);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function getLocalFile($path) {
		$this->checkAvailability();
		try {
			return parent::getLocalFile($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function hasUpdated($path, $time) {
		$this->checkAvailability();
		try {
			return parent::hasUpdated($path, $time);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function getOwner($path) {
		try {
			return parent::getOwner($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function getETag($path) {
		$this->checkAvailability();
		try {
			return parent::getETag($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function getDirectDownload($path) {
		$this->checkAvailability();
		try {
			return parent::getDirectDownload($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function copyFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		$this->checkAvailability();
		try {
			return parent::copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function moveFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		$this->checkAvailability();
		try {
			return parent::moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}

	/** {@inheritdoc} */
	public function getMetaData($path) {
		$this->checkAvailability();
		try {
			return parent::getMetaData($path);
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			$this->setAvailability(false);
			throw $e;
		}
	}
}
