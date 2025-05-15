<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Cache;

use OCP\Files\File as FileNode;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\ICache;
use OCP\IUser;
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
	 * @throws \OC\ForbiddenException
	 * @throws \OC\User\NoUserException
	 */
	protected function getStorage(?IUser $user = null): Folder {
		if ($this->storage !== null) {
			return $this->storage;
		}
		if (!$user) {
			$session = Server::get(IUserSession::class);
			$user = $session->getUser();
		}
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
			throw new \OC\ForbiddenException('Can\t get cache storage, user not logged in');
		}
	}

	/**
	 * @param string $key
	 * @return mixed|null
	 * @throws \OC\ForbiddenException
	 */
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
		$file = $storage->newFile($keyPart, $value);
		if ($ttl === 0) {
			$ttl = 86400; // 60*60*24
		}
		$file->touch(time() + $ttl);
		$file->move($storage->getFullPath($key));

		return true;
	}

	/**
	 * @param string $key
	 * @return bool
	 * @throws \OC\ForbiddenException
	 */
	public function hasKey($key) {
		return $this->getStorage()->nodeExists($key);
	}

	/**
	 * @param string $key
	 * @return bool|mixed
	 * @throws \OC\ForbiddenException
	 */
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
	 * @throws \OC\ForbiddenException
	 */
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
	 * @throws \OC\ForbiddenException
	 */
	public function gc(?IUser $user = null) {
		$storage = $this->getStorage($user);
		// extra hour safety, in case of stray part chunks that take longer to write,
		// because touch() is only called after the chunk was finished

		$now = time() - 3600;
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

	public static function isAvailable(): bool {
		return true;
	}
}
