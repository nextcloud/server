<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Cache;

use OC\ForbiddenException;
use OC\User\NoUserException;
use OCP\Files\File as FileNode;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\ICache;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
use OCP\Server;
use Psr\Log\LoggerInterface;

class File implements ICache {
	protected ?Folder $storage = null;

	/**
	 * Returns the cache folder for the logged in user
	 *
	 * @return Folder cache folder
	 * @throws ForbiddenException
	 * @throws NoUserException
	 */
	protected function getStorage() {
		if ($this->storage !== null) {
			return $this->storage;
		}
		$session = Server::get(IUserSession::class);
		$user = $session->getUser();
		$rootFolder = Server::get(IRootFolder::class);
		if ($user) {
			$userId = $user->getUID();
			try {
				$cacheFolder = $rootFolder->get('/' . $userId . '/cache');
				if (!$cacheFolder instanceof Folder) {
					throw new \Exception('Cache folder is a file');
				}
			} catch (NotFoundException $e) {
				$cacheFolder = $rootFolder->newFolder('/' . $userId . '/cache');
			}
			$this->storage = $cacheFolder;
			return $this->storage;
		} else {
			Server::get(LoggerInterface::class)->error('Can\'t get cache storage, user not logged in', ['app' => 'core']);
			throw new ForbiddenException('Can\t get cache storage, user not logged in');
		}
	}

	/**
	 * @param string $key
	 * @return mixed|null
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function get($key) {
		$storage = $this->getStorage();
		try {
			/** @var FileNode $item */
			$item = $storage->get($key);
			return $item->getContent();
		} catch (NotFoundException $e) {
			return null;
		}
	}

	/**
	 * Returns the size of the stored/cached data
	 *
	 * @param string $key
	 * @return int|float
	 */
	public function size($key) {
		$storage = $this->getStorage();
		try {
			return $storage->get($key)->getSize();
		} catch (NotFoundException $e) {
			return 0;
		}
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl
	 * @return bool|mixed
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function set($key, $value, $ttl = 0) {
		$storage = $this->getStorage();
		// unique id to avoid chunk collision, just in case
		$uniqueId = Server::get(ISecureRandom::class)->generate(
			16,
			ISecureRandom::CHAR_ALPHANUMERIC
		);

		// use a temporary file to prevent hasKey() to find the key
		// while it is being written
		$keyPart = $key . '.' . $uniqueId;
		$file = $storage->newFile($keyPart, $value);
		if ($ttl === 0) {
			$ttl = 86400; // 60*60*24
		}
		$file->move($storage->getFullPath($key));
		$file->touch(time() + $ttl);

		return true;
	}

	/**
	 * @param string $key
	 * @return bool
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function hasKey($key) {
		return $this->getStorage()->nodeExists($key);
	}

	/**
	 * @param string $key
	 * @return bool|mixed
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function remove($key) {
		$storage = $this->getStorage();
		try {
			$storage->get($key)->delete();
			return true;
		} catch (NotFoundException $e) {
			return false;
		}
	}

	/**
	 * @param string $prefix
	 * @return bool
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function clear($prefix = '') {
		$storage = $this->getStorage();
		foreach ($storage->getDirectoryListing() as $file) {
			if ($prefix === '' || str_starts_with($file->getName(), $prefix)) {
				$file->delete();
			}
		}
		return true;
	}

	/**
	 * Runs GC
	 *
	 * @throws ForbiddenException
	 */
	public function gc() {
		$storage = $this->getStorage();
		$ttl = Server::get(IConfig::class)->getSystemValueInt('cache_chunk_gc_ttl', 60 * 60 * 24);
		// extra hour safety, in case of stray part chunks that take longer to write,
		// because touch() is only called after the chunk was finished

		$now = time() - $ttl;
		foreach ($storage->getDirectoryListing() as $file) {
			try {
				if ($file->getMTime() < $now) {
					$file->delete();
				}
			} catch (\OCP\Lock\LockedException $e) {
				// ignore locked chunks
				Server::get(LoggerInterface::class)->debug('Could not cleanup locked chunk "' . $file->getName() . '"', ['app' => 'core']);
			} catch (\OCP\Files\ForbiddenException $e) {
				Server::get(LoggerInterface::class)->debug('Could not cleanup forbidden chunk "' . $file->getName() . '"', ['app' => 'core']);
			} catch (\OCP\Files\LockNotAcquiredException $e) {
				Server::get(LoggerInterface::class)->debug('Could not cleanup locked chunk "' . $file->getName() . '"', ['app' => 'core']);
			}
		}
	}

	#[\Override]
	public static function isAvailable(): bool {
		return true;
	}
}
