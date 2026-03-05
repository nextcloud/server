<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use bantu\IniGetWrapper\IniGetWrapper;
use OC\Files\FilenameValidator;
use OC\Files\Filesystem;
use OC\Files\Storage\Home;
use OC\Files\Storage\Wrapper\Quota;
use OC\SystemConfig;
use OCA\Files_Sharing\External\Storage;
use OCP\Files\FileInfo;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\IBinaryFinder;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use OCP\Util;
use Psr\Log\LoggerInterface;

/**
 * Collection of useful functions
 *
 * @psalm-type StorageInfo = array{
 *     free: float|int,
 *     mountPoint: string,
 *     mountType: string,
 *     owner: string,
 *     ownerDisplayName: string,
 *     quota: float|int,
 *     relative: float|int,
 *     total: float|int,
 *     used: float|int,
 * }
 */
class OC_Helper {
	private static ?ICacheFactory $cacheFactory = null;
	private static ?bool $quotaIncludeExternalStorage = null;

	/**
	 * Recursive copying of folders
	 * @param string $src source folder
	 * @param string $dest target folder
	 * @return void
	 * @deprecated 32.0.0 - use \OCP\Files\Folder::copy
	 */
	public static function copyr($src, $dest) {
		if (!file_exists($src)) {
			return;
		}

		if (is_dir($src)) {
			if (!is_dir($dest)) {
				mkdir($dest);
			}
			$files = scandir($src);
			foreach ($files as $file) {
				if ($file != '.' && $file != '..') {
					self::copyr("$src/$file", "$dest/$file");
				}
			}
		} else {
			$validator = Server::get(FilenameValidator::class);
			if (!$validator->isForbidden($src)) {
				copy($src, $dest);
			}
		}
	}

	/**
	 * detect if a given program is found in the search PATH
	 *
	 * @param string $name
	 * @param bool $path
	 * @internal param string $program name
	 * @internal param string $optional search path, defaults to $PATH
	 * @return bool true if executable program found in path
	 * @deprecated 32.0.0 use the \OCP\IBinaryFinder
	 */
	public static function canExecute($name, $path = false) {
		// path defaults to PATH from environment if not set
		if ($path === false) {
			$path = getenv('PATH');
		}
		// we look for an executable file of that name
		$exts = [''];
		$check_fn = 'is_executable';
		// Default check will be done with $path directories :
		$dirs = explode(PATH_SEPARATOR, (string)$path);
		// WARNING : We have to check if open_basedir is enabled :
		$obd = Server::get(IniGetWrapper::class)->getString('open_basedir');
		if ($obd != 'none') {
			$obd_values = explode(PATH_SEPARATOR, $obd);
			if (count($obd_values) > 0 && $obd_values[0]) {
				// open_basedir is in effect !
				// We need to check if the program is in one of these dirs :
				$dirs = $obd_values;
			}
		}
		foreach ($dirs as $dir) {
			foreach ($exts as $ext) {
				if ($check_fn("$dir/$name" . $ext)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Checks if a function is available
	 *
	 * @deprecated 25.0.0 use \OCP\Util::isFunctionEnabled instead
	 */
	public static function is_function_enabled(string $function_name): bool {
		return Util::isFunctionEnabled($function_name);
	}

	/**
	 * Try to find a program
	 * @deprecated 25.0.0 Use \OCP\IBinaryFinder directly
	 */
	public static function findBinaryPath(string $program): ?string {
		$result = Server::get(IBinaryFinder::class)->findBinaryPath($program);
		return $result !== false ? $result : null;
	}

	/**
	 * Calculate the disc space for the given path
	 *
	 * BEWARE: this requires that Util::setupFS() was called
	 * already !
	 *
	 * @param string $path
	 * @param FileInfo $rootInfo (optional)
	 * @param bool $includeMountPoints whether to include mount points in the size calculation
	 * @param bool $useCache whether to use the cached quota values
	 * @psalm-suppress LessSpecificReturnStatement Legacy code outputs weird types - manually validated that they are correct
	 * @return StorageInfo
	 * @throws NotFoundException
	 */
	public static function getStorageInfo($path, $rootInfo = null, $includeMountPoints = true, $useCache = true) {
		if (!self::$cacheFactory) {
			self::$cacheFactory = Server::get(ICacheFactory::class);
		}
		$memcache = self::$cacheFactory->createLocal('storage_info');

		// return storage info without adding mount points
		if (self::$quotaIncludeExternalStorage === null) {
			self::$quotaIncludeExternalStorage = Server::get(SystemConfig::class)->getValue('quota_include_external_storage', false);
		}

		$view = Filesystem::getView();
		if (!$view) {
			throw new NotFoundException();
		}
		$fullPath = Filesystem::normalizePath($view->getAbsolutePath($path));

		$cacheKey = $fullPath . '::' . ($includeMountPoints ? 'include' : 'exclude');
		if ($useCache) {
			$cached = $memcache->get($cacheKey);
			if ($cached) {
				return $cached;
			}
		}

		if (!$rootInfo) {
			$rootInfo = Filesystem::getFileInfo($path, self::$quotaIncludeExternalStorage ? 'ext' : false);
		}
		if (!$rootInfo instanceof FileInfo) {
			throw new NotFoundException('The root directory of the user\'s files is missing');
		}
		$used = $rootInfo->getSize($includeMountPoints);
		if ($used < 0) {
			$used = 0.0;
		}
		/** @var int|float $quota */
		$quota = FileInfo::SPACE_UNLIMITED;
		$mount = $rootInfo->getMountPoint();
		$storage = $mount->getStorage();
		$sourceStorage = $storage;
		if ($storage->instanceOfStorage('\OCA\Files_Sharing\SharedStorage')) {
			self::$quotaIncludeExternalStorage = false;
		}
		if (self::$quotaIncludeExternalStorage) {
			if ($storage->instanceOfStorage('\OC\Files\Storage\Home')
				|| $storage->instanceOfStorage('\OC\Files\ObjectStore\HomeObjectStoreStorage')
			) {
				/** @var Home $storage */
				$user = $storage->getUser();
			} else {
				$user = Server::get(IUserSession::class)->getUser();
			}
			$quota = $user?->getQuotaBytes() ?? FileInfo::SPACE_UNKNOWN;
			if ($quota !== FileInfo::SPACE_UNLIMITED) {
				// always get free space / total space from root + mount points
				return self::getGlobalStorageInfo($quota, $user, $mount);
			}
		}

		// TODO: need a better way to get total space from storage
		if ($sourceStorage->instanceOfStorage('\OC\Files\Storage\Wrapper\Quota')) {
			/** @var Quota $storage */
			$quota = $sourceStorage->getQuota();
		}
		try {
			$free = $sourceStorage->free_space($rootInfo->getInternalPath());
			if (is_bool($free)) {
				$free = 0.0;
			}
		} catch (\Exception $e) {
			if ($path === '') {
				throw $e;
			}
			/** @var LoggerInterface $logger */
			$logger = Server::get(LoggerInterface::class);
			$logger->warning('Error while getting quota info, using root quota', ['exception' => $e]);
			$rootInfo = self::getStorageInfo('');
			$memcache->set($cacheKey, $rootInfo, 5 * 60);
			return $rootInfo;
		}
		if ($free >= 0) {
			$total = $free + $used;
		} else {
			$total = $free; //either unknown or unlimited
		}
		if ($total > 0) {
			if ($quota > 0 && $total > $quota) {
				$total = $quota;
			}
			// prevent division by zero or error codes (negative values)
			$relative = round(($used / $total) * 10000) / 100;
		} else {
			$relative = 0;
		}

		/*
		 * \OCA\Files_Sharing\External\Storage returns the cloud ID as the owner for the storage.
		 * It is unnecessary to query the user manager for the display name, as it won't have this information.
		 */
		$isRemoteShare = $storage->instanceOfStorage(Storage::class);

		$ownerId = $storage->getOwner($path);
		$ownerDisplayName = '';

		if ($isRemoteShare === false && $ownerId !== false) {
			$ownerDisplayName = Server::get(IUserManager::class)->getDisplayName($ownerId) ?? '';
		}

		if (substr_count($mount->getMountPoint(), '/') < 3) {
			$mountPoint = '';
		} else {
			[,,,$mountPoint] = explode('/', $mount->getMountPoint(), 4);
		}

		$info = [
			'free' => $free,
			'used' => $used,
			'quota' => $quota,
			'total' => $total,
			'relative' => $relative,
			'owner' => $ownerId,
			'ownerDisplayName' => $ownerDisplayName,
			'mountType' => $mount->getMountType(),
			'mountPoint' => trim($mountPoint, '/'),
		];

		if ($isRemoteShare === false && $ownerId !== false && $path === '/') {
			// If path is root, store this as last known quota usage for this user
			Server::get(IConfig::class)->setUserValue($ownerId, 'files', 'lastSeenQuotaUsage', (string)$relative);
		}

		$memcache->set($cacheKey, $info, 5 * 60);

		return $info;
	}

	/**
	 * Get storage info including all mount points and quota
	 *
	 * @psalm-suppress LessSpecificReturnStatement Legacy code outputs weird types - manually validated that they are correct
	 * @return StorageInfo
	 */
	private static function getGlobalStorageInfo(int|float $quota, IUser $user, IMountPoint $mount): array {
		$rootInfo = Filesystem::getFileInfo('', 'ext');
		/** @var int|float $used */
		$used = $rootInfo['size'];
		if ($used < 0) {
			$used = 0.0;
		}

		$total = $quota;
		/** @var int|float $free */
		$free = $quota - $used;

		if ($total > 0) {
			if ($quota > 0 && $total > $quota) {
				$total = $quota;
			}
			// prevent division by zero or error codes (negative values)
			$relative = round(($used / $total) * 10000) / 100;
		} else {
			$relative = 0.0;
		}

		if (substr_count($mount->getMountPoint(), '/') < 3) {
			$mountPoint = '';
		} else {
			[,,,$mountPoint] = explode('/', $mount->getMountPoint(), 4);
		}

		return [
			'free' => $free,
			'used' => $used,
			'total' => $total,
			'relative' => $relative,
			'quota' => $quota,
			'owner' => $user->getUID(),
			'ownerDisplayName' => $user->getDisplayName(),
			'mountType' => $mount->getMountType(),
			'mountPoint' => trim($mountPoint, '/'),
		];
	}

	public static function clearStorageInfo(string $absolutePath): void {
		/** @var ICacheFactory $cacheFactory */
		$cacheFactory = Server::get(ICacheFactory::class);
		$memcache = $cacheFactory->createLocal('storage_info');
		$cacheKeyPrefix = Filesystem::normalizePath($absolutePath) . '::';
		$memcache->remove($cacheKeyPrefix . 'include');
		$memcache->remove($cacheKeyPrefix . 'exclude');
	}

	/**
	 * Returns whether the config file is set manually to read-only
	 * @return bool
	 * @deprecated 32.0.0 use the `config_is_read_only` system config directly
	 */
	public static function isReadOnlyConfigEnabled() {
		return Server::get(IConfig::class)->getSystemValueBool('config_is_read_only', false);
	}
}
