<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Cache;

use OC\Files\Filesystem;
use OC\Files\View;
use OCP\ICache;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
use OCP\Server;
use Psr\Log\LoggerInterface;

class File implements ICache {
	/** @var View */
	protected $storage;

	/**
	 * Returns the cache storage for the logged in user
	 *
	 * @return \OC\Files\View cache storage
	 * @throws \OC\ForbiddenException
	 * @throws \OC\User\NoUserException
	 */
	protected function getStorage() {
		if ($this->storage !== null) {
			return $this->storage;
		}
		$session = Server::get(IUserSession::class);
		if ($session->isLoggedIn()) {
			$rootView = new View();
			$userId = $session->getUser()->getUID();
			Filesystem::initMountPoints($userId);
			if (!$rootView->file_exists('/' . $userId . '/cache')) {
				$rootView->mkdir('/' . $userId . '/cache');
			}
			$this->storage = new View('/' . $userId . '/cache');
			return $this->storage;
		} else {
			Server::get(LoggerInterface::class)->error('Can\'t get cache storage, user not logged in', ['app' => 'core']);
			throw new \OC\ForbiddenException('Can\t get cache storage, user not logged in');
		}
	}

	/**
	 * @param string $key
	 * @return mixed|null
	 * @throws \OC\ForbiddenException
	 */
	public function get($key) {
		$result = null;
		if ($this->hasKey($key)) {
			$storage = $this->getStorage();
			$result = $storage->file_get_contents($key);
		}
		return $result;
	}

	/**
	 * Returns the size of the stored/cached data
	 *
	 * @param string $key
	 * @return int
	 */
	public function size($key) {
		$result = 0;
		if ($this->hasKey($key)) {
			$storage = $this->getStorage();
			$result = $storage->filesize($key);
		}
		return $result;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl
	 * @return bool|mixed
	 * @throws \OC\ForbiddenException
	 */
	public function set($key, $value, $ttl = 0) {
		$storage = $this->getStorage();
		$result = false;
		// unique id to avoid chunk collision, just in case
		$uniqueId = Server::get(ISecureRandom::class)->generate(
			16,
			ISecureRandom::CHAR_ALPHANUMERIC
		);

		// use part file to prevent hasKey() to find the key
		// while it is being written
		$keyPart = $key . '.' . $uniqueId . '.part';
		if ($storage && $storage->file_put_contents($keyPart, $value)) {
			if ($ttl === 0) {
				$ttl = 86400; // 60*60*24
			}
			$result = $storage->touch($keyPart, time() + $ttl);
			$result &= $storage->rename($keyPart, $key);
		}
		return $result;
	}

	/**
	 * @param string $key
	 * @return bool
	 * @throws \OC\ForbiddenException
	 */
	public function hasKey($key) {
		$storage = $this->getStorage();
		if ($storage && $storage->is_file($key) && $storage->isReadable($key)) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $key
	 * @return bool|mixed
	 * @throws \OC\ForbiddenException
	 */
	public function remove($key) {
		$storage = $this->getStorage();
		if (!$storage) {
			return false;
		}
		return $storage->unlink($key);
	}

	/**
	 * @param string $prefix
	 * @return bool
	 * @throws \OC\ForbiddenException
	 */
	public function clear($prefix = '') {
		$storage = $this->getStorage();
		if ($storage && $storage->is_dir('/')) {
			$dh = $storage->opendir('/');
			if (is_resource($dh)) {
				while (($file = readdir($dh)) !== false) {
					if ($file !== '.' && $file !== '..' && ($prefix === '' || str_starts_with($file, $prefix))) {
						$storage->unlink('/' . $file);
					}
				}
			}
		}
		return true;
	}

	/**
	 * Runs GC
	 * @throws \OC\ForbiddenException
	 */
	public function gc() {
		$storage = $this->getStorage();
		if ($storage) {
			// extra hour safety, in case of stray part chunks that take longer to write,
			// because touch() is only called after the chunk was finished
			$now = time() - 3600;
			$dh = $storage->opendir('/');
			if (!is_resource($dh)) {
				return null;
			}
			while (($file = readdir($dh)) !== false) {
				if ($file !== '.' && $file !== '..') {
					try {
						$mtime = $storage->filemtime('/' . $file);
						if ($mtime < $now) {
							$storage->unlink('/' . $file);
						}
					} catch (\OCP\Lock\LockedException $e) {
						// ignore locked chunks
						Server::get(LoggerInterface::class)->debug('Could not cleanup locked chunk "' . $file . '"', ['app' => 'core']);
					} catch (\OCP\Files\ForbiddenException $e) {
						Server::get(LoggerInterface::class)->debug('Could not cleanup forbidden chunk "' . $file . '"', ['app' => 'core']);
					} catch (\OCP\Files\LockNotAcquiredException $e) {
						Server::get(LoggerInterface::class)->debug('Could not cleanup locked chunk "' . $file . '"', ['app' => 'core']);
					}
				}
			}
		}
	}

	public static function isAvailable(): bool {
		return true;
	}
}
