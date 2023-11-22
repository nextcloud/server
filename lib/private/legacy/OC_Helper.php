<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Ardinis <Ardinis@users.noreply.github.com>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Pellaeon Lin <nfsmwlin@gmail.com>
 * @author RealRancor <fisch.666@gmx.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Simon Könnecke <simonkoennecke@gmail.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Vincent Petry <vincent@nextcloud.com>
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
use bantu\IniGetWrapper\IniGetWrapper;
use OC\Files\Filesystem;
use OCP\Files\Mount\IMountPoint;
use OCP\ICacheFactory;
use OCP\IBinaryFinder;
use OCP\IUser;
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
	private static $templateManager;
	private static ?ICacheFactory $cacheFactory = null;
	private static ?bool $quotaIncludeExternalStorage = null;

	/**
	 * Make a human file size
	 * @param int|float $bytes file size in bytes
	 * @return string a human readable file size
	 *
	 * Makes 2048 to 2 kB.
	 */
	public static function humanFileSize(int|float $bytes): string {
		if ($bytes < 0) {
			return "?";
		}
		if ($bytes < 1024) {
			return "$bytes B";
		}
		$bytes = round($bytes / 1024, 0);
		if ($bytes < 1024) {
			return "$bytes KB";
		}
		$bytes = round($bytes / 1024, 1);
		if ($bytes < 1024) {
			return "$bytes MB";
		}
		$bytes = round($bytes / 1024, 1);
		if ($bytes < 1024) {
			return "$bytes GB";
		}
		$bytes = round($bytes / 1024, 1);
		if ($bytes < 1024) {
			return "$bytes TB";
		}

		$bytes = round($bytes / 1024, 1);
		return "$bytes PB";
	}

	/**
	 * Make a computer file size
	 * @param string $str file size in human readable format
	 * @return false|int|float a file size in bytes
	 *
	 * Makes 2kB to 2048.
	 *
	 * Inspired by: https://www.php.net/manual/en/function.filesize.php#92418
	 */
	public static function computerFileSize(string $str): false|int|float {
		$str = strtolower($str);
		if (is_numeric($str)) {
			return Util::numericToNumber($str);
		}

		$bytes_array = [
			'b' => 1,
			'k' => 1024,
			'kb' => 1024,
			'mb' => 1024 * 1024,
			'm' => 1024 * 1024,
			'gb' => 1024 * 1024 * 1024,
			'g' => 1024 * 1024 * 1024,
			'tb' => 1024 * 1024 * 1024 * 1024,
			't' => 1024 * 1024 * 1024 * 1024,
			'pb' => 1024 * 1024 * 1024 * 1024 * 1024,
			'p' => 1024 * 1024 * 1024 * 1024 * 1024,
		];

		$bytes = (float)$str;

		if (preg_match('#([kmgtp]?b?)$#si', $str, $matches) && !empty($bytes_array[$matches[1]])) {
			$bytes *= $bytes_array[$matches[1]];
		} else {
			return false;
		}

		return Util::numericToNumber(round($bytes));
	}

	/**
	 * Recursive copying of folders
	 * @param string $src source folder
	 * @param string $dest target folder
	 * @return void
	 */
	public static function copyr($src, $dest) {
		if (is_dir($src)) {
			if (!is_dir($dest)) {
				mkdir($dest);
			}
			$files = scandir($src);
			foreach ($files as $file) {
				if ($file != "." && $file != "..") {
					self::copyr("$src/$file", "$dest/$file");
				}
			}
		} elseif (file_exists($src) && !\OC\Files\Filesystem::isFileBlacklisted($src)) {
			copy($src, $dest);
		}
	}

	/**
	 * Recursive deletion of folders
	 * @param string $dir path to the folder
	 * @param bool $deleteSelf if set to false only the content of the folder will be deleted
	 * @return bool
	 */
	public static function rmdirr($dir, $deleteSelf = true) {
		if (is_dir($dir)) {
			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ($files as $fileInfo) {
				/** @var SplFileInfo $fileInfo */
				if ($fileInfo->isLink()) {
					unlink($fileInfo->getPathname());
				} elseif ($fileInfo->isDir()) {
					rmdir($fileInfo->getRealPath());
				} else {
					unlink($fileInfo->getRealPath());
				}
			}
			if ($deleteSelf) {
				rmdir($dir);
			}
		} elseif (file_exists($dir)) {
			if ($deleteSelf) {
				unlink($dir);
			}
		}
		if (!$deleteSelf) {
			return true;
		}

		return !file_exists($dir);
	}

	/**
	 * @deprecated 18.0.0
	 * @return \OC\Files\Type\TemplateManager
	 */
	public static function getFileTemplateManager() {
		if (!self::$templateManager) {
			self::$templateManager = new \OC\Files\Type\TemplateManager();
		}
		return self::$templateManager;
	}

	/**
	 * detect if a given program is found in the search PATH
	 *
	 * @param string $name
	 * @param bool $path
	 * @internal param string $program name
	 * @internal param string $optional search path, defaults to $PATH
	 * @return bool    true if executable program found in path
	 */
	public static function canExecute($name, $path = false) {
		// path defaults to PATH from environment if not set
		if ($path === false) {
			$path = getenv("PATH");
		}
		// we look for an executable file of that name
		$exts = [""];
		$check_fn = "is_executable";
		// Default check will be done with $path directories :
		$dirs = explode(PATH_SEPARATOR, $path);
		// WARNING : We have to check if open_basedir is enabled :
		$obd = OC::$server->get(IniGetWrapper::class)->getString('open_basedir');
		if ($obd != "none") {
			$obd_values = explode(PATH_SEPARATOR, $obd);
			if (count($obd_values) > 0 and $obd_values[0]) {
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
	 * copy the contents of one stream to another
	 *
	 * @param resource $source
	 * @param resource $target
	 * @return array the number of bytes copied and result
	 */
	public static function streamCopy($source, $target) {
		if (!$source or !$target) {
			return [0, false];
		}
		$bufSize = 8192;
		$result = true;
		$count = 0;
		while (!feof($source)) {
			$buf = fread($source, $bufSize);
			$bytesWritten = fwrite($target, $buf);
			if ($bytesWritten !== false) {
				$count += $bytesWritten;
			}
			// note: strlen is expensive so only use it when necessary,
			// on the last block
			if ($bytesWritten === false
				|| ($bytesWritten < $bufSize && $bytesWritten < strlen($buf))
			) {
				// write error, could be disk full ?
				$result = false;
				break;
			}
		}
		return [$count, $result];
	}

	/**
	 * Adds a suffix to the name in case the file exists
	 *
	 * @param string $path
	 * @param string $filename
	 * @return string
	 */
	public static function buildNotExistingFileName($path, $filename) {
		$view = \OC\Files\Filesystem::getView();
		return self::buildNotExistingFileNameForView($path, $filename, $view);
	}

	/**
	 * Adds a suffix to the name in case the file exists
	 *
	 * @param string $path
	 * @param string $filename
	 * @return string
	 */
	public static function buildNotExistingFileNameForView($path, $filename, \OC\Files\View $view) {
		if ($path === '/') {
			$path = '';
		}
		if ($pos = strrpos($filename, '.')) {
			$name = substr($filename, 0, $pos);
			$ext = substr($filename, $pos);
		} else {
			$name = $filename;
			$ext = '';
		}

		$newpath = $path . '/' . $filename;
		if ($view->file_exists($newpath)) {
			if (preg_match_all('/\((\d+)\)/', $name, $matches, PREG_OFFSET_CAPTURE)) {
				//Replace the last "(number)" with "(number+1)"
				$last_match = count($matches[0]) - 1;
				$counter = $matches[1][$last_match][0] + 1;
				$offset = $matches[0][$last_match][1];
				$match_length = strlen($matches[0][$last_match][0]);
			} else {
				$counter = 2;
				$match_length = 0;
				$offset = false;
			}
			do {
				if ($offset) {
					//Replace the last "(number)" with "(number+1)"
					$newname = substr_replace($name, '(' . $counter . ')', $offset, $match_length);
				} else {
					$newname = $name . ' (' . $counter . ')';
				}
				$newpath = $path . '/' . $newname . $ext;
				$counter++;
			} while ($view->file_exists($newpath));
		}

		return $newpath;
	}

	/**
	 * Returns an array with all keys from input lowercased or uppercased. Numbered indices are left as is.
	 *
	 * @param array $input The array to work on
	 * @param int $case Either MB_CASE_UPPER or MB_CASE_LOWER (default)
	 * @param string $encoding The encoding parameter is the character encoding. Defaults to UTF-8
	 * @return array
	 *
	 * Returns an array with all keys from input lowercased or uppercased. Numbered indices are left as is.
	 * based on https://www.php.net/manual/en/function.array-change-key-case.php#107715
	 *
	 */
	public static function mb_array_change_key_case($input, $case = MB_CASE_LOWER, $encoding = 'UTF-8') {
		$case = ($case != MB_CASE_UPPER) ? MB_CASE_LOWER : MB_CASE_UPPER;
		$ret = [];
		foreach ($input as $k => $v) {
			$ret[mb_convert_case($k, $case, $encoding)] = $v;
		}
		return $ret;
	}

	/**
	 * performs a search in a nested array
	 * @param array $haystack the array to be searched
	 * @param string $needle the search string
	 * @param mixed $index optional, only search this key name
	 * @return mixed the key of the matching field, otherwise false
	 *
	 * performs a search in a nested array
	 *
	 * taken from https://www.php.net/manual/en/function.array-search.php#97645
	 */
	public static function recursiveArraySearch($haystack, $needle, $index = null) {
		$aIt = new RecursiveArrayIterator($haystack);
		$it = new RecursiveIteratorIterator($aIt);

		while ($it->valid()) {
			if (((isset($index) and ($it->key() == $index)) or !isset($index)) and ($it->current() == $needle)) {
				return $aIt->key();
			}

			$it->next();
		}

		return false;
	}

	/**
	 * calculates the maximum upload size respecting system settings, free space and user quota
	 *
	 * @param string $dir the current folder where the user currently operates
	 * @param int|float $freeSpace the number of bytes free on the storage holding $dir, if not set this will be received from the storage directly
	 * @return int|float number of bytes representing
	 */
	public static function maxUploadFilesize($dir, $freeSpace = null) {
		if (is_null($freeSpace) || $freeSpace < 0) {
			$freeSpace = self::freeSpace($dir);
		}
		return min($freeSpace, self::uploadLimit());
	}

	/**
	 * Calculate free space left within user quota
	 *
	 * @param string $dir the current folder where the user currently operates
	 * @return int|float number of bytes representing
	 */
	public static function freeSpace($dir) {
		$freeSpace = \OC\Files\Filesystem::free_space($dir);
		if ($freeSpace < \OCP\Files\FileInfo::SPACE_UNLIMITED) {
			$freeSpace = max($freeSpace, 0);
			return $freeSpace;
		} else {
			return (INF > 0)? INF: PHP_INT_MAX; // work around https://bugs.php.net/bug.php?id=69188
		}
	}

	/**
	 * Calculate PHP upload limit
	 *
	 * @return int|float PHP upload file size limit
	 */
	public static function uploadLimit() {
		$ini = \OC::$server->get(IniGetWrapper::class);
		$upload_max_filesize = Util::computerFileSize($ini->get('upload_max_filesize')) ?: 0;
		$post_max_size = Util::computerFileSize($ini->get('post_max_size')) ?: 0;
		if ($upload_max_filesize === 0 && $post_max_size === 0) {
			return INF;
		} elseif ($upload_max_filesize === 0 || $post_max_size === 0) {
			return max($upload_max_filesize, $post_max_size); //only the non 0 value counts
		} else {
			return min($upload_max_filesize, $post_max_size);
		}
	}

	/**
	 * Checks if a function is available
	 *
	 * @deprecated Since 25.0.0 use \OCP\Util::isFunctionEnabled instead
	 */
	public static function is_function_enabled(string $function_name): bool {
		return \OCP\Util::isFunctionEnabled($function_name);
	}

	/**
	 * Try to find a program
	 * @deprecated Since 25.0.0 Use \OC\BinaryFinder directly
	 */
	public static function findBinaryPath(string $program): ?string {
		$result = \OCP\Server::get(IBinaryFinder::class)->findBinaryPath($program);
		return $result !== false ? $result : null;
	}

	/**
	 * Calculate the disc space for the given path
	 *
	 * BEWARE: this requires that Util::setupFS() was called
	 * already !
	 *
	 * @param string $path
	 * @param \OCP\Files\FileInfo $rootInfo (optional)
	 * @param bool $includeMountPoints whether to include mount points in the size calculation
	 * @param bool $useCache whether to use the cached quota values
	 * @psalm-suppress LessSpecificReturnStatement Legacy code outputs weird types - manually validated that they are correct
	 * @return StorageInfo
	 * @throws \OCP\Files\NotFoundException
	 */
	public static function getStorageInfo($path, $rootInfo = null, $includeMountPoints = true, $useCache = true) {
		if (!self::$cacheFactory) {
			self::$cacheFactory = \OC::$server->get(ICacheFactory::class);
		}
		$memcache = self::$cacheFactory->createLocal('storage_info');

		// return storage info without adding mount points
		if (self::$quotaIncludeExternalStorage === null) {
			self::$quotaIncludeExternalStorage = \OC::$server->getSystemConfig()->getValue('quota_include_external_storage', false);
		}

		$view = Filesystem::getView();
		if (!$view) {
			throw new \OCP\Files\NotFoundException();
		}
		$fullPath = Filesystem::normalizePath($view->getAbsolutePath($path));

		$cacheKey = $fullPath. '::' . ($includeMountPoints ? 'include' : 'exclude');
		if ($useCache) {
			$cached = $memcache->get($cacheKey);
			if ($cached) {
				return $cached;
			}
		}

		if (!$rootInfo) {
			$rootInfo = \OC\Files\Filesystem::getFileInfo($path, self::$quotaIncludeExternalStorage ? 'ext' : false);
		}
		if (!$rootInfo instanceof \OCP\Files\FileInfo) {
			throw new \OCP\Files\NotFoundException('The root directory of the user\'s files is missing');
		}
		$used = $rootInfo->getSize($includeMountPoints);
		if ($used < 0) {
			$used = 0.0;
		}
		/** @var int|float $quota */
		$quota = \OCP\Files\FileInfo::SPACE_UNLIMITED;
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
				/** @var \OC\Files\Storage\Home $storage */
				$user = $storage->getUser();
			} else {
				$user = \OC::$server->getUserSession()->getUser();
			}
			$quota = OC_Util::getUserQuota($user);
			if ($quota !== \OCP\Files\FileInfo::SPACE_UNLIMITED) {
				// always get free space / total space from root + mount points
				return self::getGlobalStorageInfo($quota, $user, $mount);
			}
		}

		// TODO: need a better way to get total space from storage
		if ($sourceStorage->instanceOfStorage('\OC\Files\Storage\Wrapper\Quota')) {
			/** @var \OC\Files\Storage\Wrapper\Quota $storage */
			$quota = $sourceStorage->getQuota();
		}
		try {
			$free = $sourceStorage->free_space($rootInfo->getInternalPath());
			if (is_bool($free)) {
				$free = 0.0;
			}
		} catch (\Exception $e) {
			if ($path === "") {
				throw $e;
			}
			/** @var LoggerInterface $logger */
			$logger = \OC::$server->get(LoggerInterface::class);
			$logger->warning("Error while getting quota info, using root quota", ['exception' => $e]);
			$rootInfo = self::getStorageInfo("");
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

		/** @var string $ownerId */
		$ownerId = $storage->getOwner($path);
		$ownerDisplayName = '';
		if ($ownerId) {
			$ownerDisplayName = \OC::$server->getUserManager()->getDisplayName($ownerId) ?? '';
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
		$rootInfo = \OC\Files\Filesystem::getFileInfo('', 'ext');
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
		$cacheFactory = \OC::$server->get(ICacheFactory::class);
		$memcache = $cacheFactory->createLocal('storage_info');
		$cacheKeyPrefix = Filesystem::normalizePath($absolutePath) . '::';
		$memcache->remove($cacheKeyPrefix . 'include');
		$memcache->remove($cacheKeyPrefix . 'exclude');
	}

	/**
	 * Returns whether the config file is set manually to read-only
	 * @return bool
	 */
	public static function isReadOnlyConfigEnabled() {
		return \OC::$server->getConfig()->getSystemValueBool('config_is_read_only', false);
	}
}
