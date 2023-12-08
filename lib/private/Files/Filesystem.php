<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Florin Peter <github@florin-peter.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author korelstar <korelstar@users.noreply.github.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sam Tuke <mail@samtuke.com>
 * @author Stephan Peijnik <speijnik@anexia-it.com>
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
namespace OC\Files;

use OC\Files\Mount\MountPoint;
use OC\User\NoUserException;
use OCP\Cache\CappedMemoryCache;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\Node\FilesystemTornDownEvent;
use OCP\Files\Mount\IMountManager;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

class Filesystem {
	private static ?Mount\Manager $mounts = null;

	public static bool $loaded = false;

	private static ?View $defaultInstance = null;

	private static ?CappedMemoryCache $normalizedPathCache = null;

	/** @var string[]|null */
	private static ?array $blacklist = null;

	/**
	 * classname which used for hooks handling
	 * used as signalclass in OC_Hooks::emit()
	 */
	public const CLASSNAME = 'OC_Filesystem';

	/**
	 * signalname emitted before file renaming
	 *
	 * @param string $oldpath
	 * @param string $newpath
	 */
	public const signal_rename = 'rename';

	/**
	 * signal emitted after file renaming
	 *
	 * @param string $oldpath
	 * @param string $newpath
	 */
	public const signal_post_rename = 'post_rename';

	/**
	 * signal emitted before file/dir creation
	 *
	 * @param string $path
	 * @param bool $run changing this flag to false in hook handler will cancel event
	 */
	public const signal_create = 'create';

	/**
	 * signal emitted after file/dir creation
	 *
	 * @param string $path
	 * @param bool $run changing this flag to false in hook handler will cancel event
	 */
	public const signal_post_create = 'post_create';

	/**
	 * signal emits before file/dir copy
	 *
	 * @param string $oldpath
	 * @param string $newpath
	 * @param bool $run changing this flag to false in hook handler will cancel event
	 */
	public const signal_copy = 'copy';

	/**
	 * signal emits after file/dir copy
	 *
	 * @param string $oldpath
	 * @param string $newpath
	 */
	public const signal_post_copy = 'post_copy';

	/**
	 * signal emits before file/dir save
	 *
	 * @param string $path
	 * @param bool $run changing this flag to false in hook handler will cancel event
	 */
	public const signal_write = 'write';

	/**
	 * signal emits after file/dir save
	 *
	 * @param string $path
	 */
	public const signal_post_write = 'post_write';

	/**
	 * signal emitted before file/dir update
	 *
	 * @param string $path
	 * @param bool $run changing this flag to false in hook handler will cancel event
	 */
	public const signal_update = 'update';

	/**
	 * signal emitted after file/dir update
	 *
	 * @param string $path
	 * @param bool $run changing this flag to false in hook handler will cancel event
	 */
	public const signal_post_update = 'post_update';

	/**
	 * signal emits when reading file/dir
	 *
	 * @param string $path
	 */
	public const signal_read = 'read';

	/**
	 * signal emits when removing file/dir
	 *
	 * @param string $path
	 */
	public const signal_delete = 'delete';

	/**
	 * parameters definitions for signals
	 */
	public const signal_param_path = 'path';
	public const signal_param_oldpath = 'oldpath';
	public const signal_param_newpath = 'newpath';

	/**
	 * run - changing this flag to false in hook handler will cancel event
	 */
	public const signal_param_run = 'run';

	public const signal_create_mount = 'create_mount';
	public const signal_delete_mount = 'delete_mount';
	public const signal_param_mount_type = 'mounttype';
	public const signal_param_users = 'users';

	private static ?\OC\Files\Storage\StorageFactory $loader = null;

	private static bool $logWarningWhenAddingStorageWrapper = true;

	/**
	 * @param bool $shouldLog
	 * @return bool previous value
	 * @internal
	 */
	public static function logWarningWhenAddingStorageWrapper(bool $shouldLog): bool {
		$previousValue = self::$logWarningWhenAddingStorageWrapper;
		self::$logWarningWhenAddingStorageWrapper = $shouldLog;
		return $previousValue;
	}

	/**
	 * @param string $wrapperName
	 * @param callable $wrapper
	 * @param int $priority
	 */
	public static function addStorageWrapper($wrapperName, $wrapper, $priority = 50) {
		if (self::$logWarningWhenAddingStorageWrapper) {
			\OC::$server->getLogger()->warning("Storage wrapper '{wrapper}' was not registered via the 'OC_Filesystem - preSetup' hook which could cause potential problems.", [
				'wrapper' => $wrapperName,
				'app' => 'filesystem',
			]);
		}

		$mounts = self::getMountManager()->getAll();
		if (!self::getLoader()->addStorageWrapper($wrapperName, $wrapper, $priority, $mounts)) {
			// do not re-wrap if storage with this name already existed
			return;
		}
	}

	/**
	 * Returns the storage factory
	 *
	 * @return IStorageFactory
	 */
	public static function getLoader() {
		if (!self::$loader) {
			self::$loader = \OC::$server->get(IStorageFactory::class);
		}
		return self::$loader;
	}

	/**
	 * Returns the mount manager
	 */
	public static function getMountManager(): Mount\Manager {
		self::initMountManager();
		assert(self::$mounts !== null);
		return self::$mounts;
	}

	/**
	 * get the mountpoint of the storage object for a path
	 * ( note: because a storage is not always mounted inside the fakeroot, the
	 * returned mountpoint is relative to the absolute root of the filesystem
	 * and doesn't take the chroot into account )
	 *
	 * @param string $path
	 * @return string
	 */
	public static function getMountPoint($path) {
		if (!self::$mounts) {
			\OC_Util::setupFS();
		}
		$mount = self::$mounts->find($path);
		return $mount->getMountPoint();
	}

	/**
	 * get a list of all mount points in a directory
	 *
	 * @param string $path
	 * @return string[]
	 */
	public static function getMountPoints($path) {
		if (!self::$mounts) {
			\OC_Util::setupFS();
		}
		$result = [];
		$mounts = self::$mounts->findIn($path);
		foreach ($mounts as $mount) {
			$result[] = $mount->getMountPoint();
		}
		return $result;
	}

	/**
	 * get the storage mounted at $mountPoint
	 *
	 * @param string $mountPoint
	 * @return \OC\Files\Storage\Storage|null
	 */
	public static function getStorage($mountPoint) {
		$mount = self::getMountManager()->find($mountPoint);
		return $mount->getStorage();
	}

	/**
	 * @param string $id
	 * @return Mount\MountPoint[]
	 */
	public static function getMountByStorageId($id) {
		return self::getMountManager()->findByStorageId($id);
	}

	/**
	 * @param int $id
	 * @return Mount\MountPoint[]
	 */
	public static function getMountByNumericId($id) {
		return self::getMountManager()->findByNumericId($id);
	}

	/**
	 * resolve a path to a storage and internal path
	 *
	 * @param string $path
	 * @return array{?\OCP\Files\Storage\IStorage, string} an array consisting of the storage and the internal path
	 */
	public static function resolvePath($path): array {
		$mount = self::getMountManager()->find($path);
		return [$mount->getStorage(), rtrim($mount->getInternalPath($path), '/')];
	}

	public static function init(string|IUser|null $user, string $root): bool {
		if (self::$defaultInstance) {
			return false;
		}
		self::initInternal($root);

		//load custom mount config
		self::initMountPoints($user);

		return true;
	}

	public static function initInternal(string $root): bool {
		if (self::$defaultInstance) {
			return false;
		}
		self::getLoader();
		self::$defaultInstance = new View($root);
		/** @var IEventDispatcher $eventDispatcher */
		$eventDispatcher = \OC::$server->get(IEventDispatcher::class);
		$eventDispatcher->addListener(FilesystemTornDownEvent::class, function () {
			self::$defaultInstance = null;
			self::$loaded = false;
		});

		self::initMountManager();

		self::$loaded = true;

		return true;
	}

	public static function initMountManager(): void {
		if (!self::$mounts) {
			self::$mounts = \OC::$server->get(IMountManager::class);
		}
	}

	/**
	 * Initialize system and personal mount points for a user
	 *
	 * @throws \OC\User\NoUserException if the user is not available
	 */
	public static function initMountPoints(string|IUser|null $user = ''): void {
		/** @var IUserManager $userManager */
		$userManager = \OC::$server->get(IUserManager::class);

		$userObject = ($user instanceof IUser) ? $user : $userManager->get($user);
		if ($userObject) {
			/** @var SetupManager $setupManager */
			$setupManager = \OC::$server->get(SetupManager::class);
			$setupManager->setupForUser($userObject);
		} else {
			throw new NoUserException();
		}
	}

	/**
	 * Get the default filesystem view
	 */
	public static function getView(): ?View {
		if (!self::$defaultInstance) {
			/** @var IUserSession $session */
			$session = \OC::$server->get(IUserSession::class);
			$user = $session->getUser();
			if ($user) {
				$userDir = '/' . $user->getUID() . '/files';
				self::initInternal($userDir);
			}
		}
		return self::$defaultInstance;
	}

	/**
	 * tear down the filesystem, removing all storage providers
	 */
	public static function tearDown() {
		\OC_Util::tearDownFS();
	}

	/**
	 * get the relative path of the root data directory for the current user
	 *
	 * @return ?string
	 *
	 * Returns path like /admin/files
	 */
	public static function getRoot() {
		if (!self::$defaultInstance) {
			return null;
		}
		return self::$defaultInstance->getRoot();
	}

	/**
	 * mount an \OC\Files\Storage\Storage in our virtual filesystem
	 *
	 * @param \OC\Files\Storage\Storage|string $class
	 * @param array $arguments
	 * @param string $mountpoint
	 */
	public static function mount($class, $arguments, $mountpoint) {
		if (!self::$mounts) {
			\OC_Util::setupFS();
		}
		$mount = new Mount\MountPoint($class, $mountpoint, $arguments, self::getLoader());
		self::$mounts->addMount($mount);
	}

	/**
	 * return the path to a local version of the file
	 * we need this because we can't know if a file is stored local or not from
	 * outside the filestorage and for some purposes a local file is needed
	 */
	public static function getLocalFile(string $path): string|false {
		return self::$defaultInstance->getLocalFile($path);
	}

	/**
	 * return path to file which reflects one visible in browser
	 *
	 * @param string $path
	 * @return string
	 */
	public static function getLocalPath($path) {
		$datadir = \OC_User::getHome(\OC_User::getUser()) . '/files';
		$newpath = $path;
		if (strncmp($newpath, $datadir, strlen($datadir)) == 0) {
			$newpath = substr($path, strlen($datadir));
		}
		return $newpath;
	}

	/**
	 * check if the requested path is valid
	 *
	 * @param string $path
	 * @return bool
	 */
	public static function isValidPath($path) {
		$path = self::normalizePath($path);
		if (!$path || $path[0] !== '/') {
			$path = '/' . $path;
		}
		if (str_contains($path, '/../') || strrchr($path, '/') === '/..') {
			return false;
		}
		return true;
	}

	/**
	 * @param string $filename
	 * @return bool
	 */
	public static function isFileBlacklisted($filename) {
		$filename = self::normalizePath($filename);

		if (self::$blacklist === null) {
			self::$blacklist = \OC::$server->getConfig()->getSystemValue('blacklisted_files', ['.htaccess']);
		}

		$filename = strtolower(basename($filename));
		return in_array($filename, self::$blacklist);
	}

	/**
	 * check if the directory should be ignored when scanning
	 * NOTE: the special directories . and .. would cause never ending recursion
	 *
	 * @param string $dir
	 * @return boolean
	 */
	public static function isIgnoredDir($dir) {
		if ($dir === '.' || $dir === '..') {
			return true;
		}
		return false;
	}

	/**
	 * following functions are equivalent to their php builtin equivalents for arguments/return values.
	 */
	public static function mkdir($path) {
		return self::$defaultInstance->mkdir($path);
	}

	public static function rmdir($path) {
		return self::$defaultInstance->rmdir($path);
	}

	public static function is_dir($path) {
		return self::$defaultInstance->is_dir($path);
	}

	public static function is_file($path) {
		return self::$defaultInstance->is_file($path);
	}

	public static function stat($path) {
		return self::$defaultInstance->stat($path);
	}

	public static function filetype($path) {
		return self::$defaultInstance->filetype($path);
	}

	public static function filesize($path) {
		return self::$defaultInstance->filesize($path);
	}

	public static function readfile($path) {
		return self::$defaultInstance->readfile($path);
	}

	public static function isCreatable($path) {
		return self::$defaultInstance->isCreatable($path);
	}

	public static function isReadable($path) {
		return self::$defaultInstance->isReadable($path);
	}

	public static function isUpdatable($path) {
		return self::$defaultInstance->isUpdatable($path);
	}

	public static function isDeletable($path) {
		return self::$defaultInstance->isDeletable($path);
	}

	public static function isSharable($path) {
		return self::$defaultInstance->isSharable($path);
	}

	public static function file_exists($path) {
		return self::$defaultInstance->file_exists($path);
	}

	public static function filemtime($path) {
		return self::$defaultInstance->filemtime($path);
	}

	public static function touch($path, $mtime = null) {
		return self::$defaultInstance->touch($path, $mtime);
	}

	/**
	 * @return string|false
	 */
	public static function file_get_contents($path) {
		return self::$defaultInstance->file_get_contents($path);
	}

	public static function file_put_contents($path, $data) {
		return self::$defaultInstance->file_put_contents($path, $data);
	}

	public static function unlink($path) {
		return self::$defaultInstance->unlink($path);
	}

	public static function rename($source, $target) {
		return self::$defaultInstance->rename($source, $target);
	}

	public static function copy($source, $target) {
		return self::$defaultInstance->copy($source, $target);
	}

	public static function fopen($path, $mode) {
		return self::$defaultInstance->fopen($path, $mode);
	}

	/**
	 * @param string $path
	 * @throws \OCP\Files\InvalidPathException
	 */
	public static function toTmpFile($path): string|false {
		return self::$defaultInstance->toTmpFile($path);
	}

	public static function fromTmpFile($tmpFile, $path) {
		return self::$defaultInstance->fromTmpFile($tmpFile, $path);
	}

	public static function getMimeType($path) {
		return self::$defaultInstance->getMimeType($path);
	}

	public static function hash($type, $path, $raw = false) {
		return self::$defaultInstance->hash($type, $path, $raw);
	}

	public static function free_space($path = '/') {
		return self::$defaultInstance->free_space($path);
	}

	public static function search($query) {
		return self::$defaultInstance->search($query);
	}

	/**
	 * @param string $query
	 */
	public static function searchByMime($query) {
		return self::$defaultInstance->searchByMime($query);
	}

	/**
	 * @param string|int $tag name or tag id
	 * @param string $userId owner of the tags
	 * @return FileInfo[] array or file info
	 */
	public static function searchByTag($tag, $userId) {
		return self::$defaultInstance->searchByTag($tag, $userId);
	}

	/**
	 * check if a file or folder has been updated since $time
	 *
	 * @param string $path
	 * @param int $time
	 * @return bool
	 */
	public static function hasUpdated($path, $time) {
		return self::$defaultInstance->hasUpdated($path, $time);
	}

	/**
	 * Fix common problems with a file path
	 *
	 * @param string $path
	 * @param bool $stripTrailingSlash whether to strip the trailing slash
	 * @param bool $isAbsolutePath whether the given path is absolute
	 * @param bool $keepUnicode true to disable unicode normalization
	 * @psalm-taint-escape file
	 * @return string
	 */
	public static function normalizePath($path, $stripTrailingSlash = true, $isAbsolutePath = false, $keepUnicode = false) {
		/**
		 * FIXME: This is a workaround for existing classes and files which call
		 *        this function with another type than a valid string. This
		 *        conversion should get removed as soon as all existing
		 *        function calls have been fixed.
		 */
		$path = (string)$path;

		if ($path === '') {
			return '/';
		}

		if (is_null(self::$normalizedPathCache)) {
			self::$normalizedPathCache = new CappedMemoryCache(2048);
		}

		$cacheKey = json_encode([$path, $stripTrailingSlash, $isAbsolutePath, $keepUnicode]);

		if ($cacheKey && isset(self::$normalizedPathCache[$cacheKey])) {
			return self::$normalizedPathCache[$cacheKey];
		}

		//normalize unicode if possible
		if (!$keepUnicode) {
			$path = \OC_Util::normalizeUnicode($path);
		}

		//add leading slash, if it is already there we strip it anyway
		$path = '/' . $path;

		$patterns = [
			'#\\\\#s',       // no windows style '\\' slashes
			'#/\.(/\.)*/#s', // remove '/./'
			'#\//+#s',       // remove sequence of slashes
			'#/\.$#s',       // remove trailing '/.'
		];

		do {
			$count = 0;
			$path = preg_replace($patterns, '/', $path, -1, $count);
		} while ($count > 0);

		//remove trailing slash
		if ($stripTrailingSlash && strlen($path) > 1) {
			$path = rtrim($path, '/');
		}

		self::$normalizedPathCache[$cacheKey] = $path;

		return $path;
	}

	/**
	 * get the filesystem info
	 *
	 * @param string $path
	 * @param bool|string $includeMountPoints whether to add mountpoint sizes,
	 * defaults to true
	 * @return \OC\Files\FileInfo|false False if file does not exist
	 */
	public static function getFileInfo($path, $includeMountPoints = true) {
		return self::getView()->getFileInfo($path, $includeMountPoints);
	}

	/**
	 * change file metadata
	 *
	 * @param string $path
	 * @param array $data
	 * @return int
	 *
	 * returns the fileid of the updated file
	 */
	public static function putFileInfo($path, $data) {
		return self::$defaultInstance->putFileInfo($path, $data);
	}

	/**
	 * get the content of a directory
	 *
	 * @param string $directory path under datadirectory
	 * @param string $mimetype_filter limit returned content to this mimetype or mimepart
	 * @return \OC\Files\FileInfo[]
	 */
	public static function getDirectoryContent($directory, $mimetype_filter = '') {
		return self::$defaultInstance->getDirectoryContent($directory, $mimetype_filter);
	}

	/**
	 * Get the path of a file by id
	 *
	 * Note that the resulting path is not guaranteed to be unique for the id, multiple paths can point to the same file
	 *
	 * @param int $id
	 * @throws NotFoundException
	 * @return string
	 */
	public static function getPath($id) {
		return self::$defaultInstance->getPath($id);
	}

	/**
	 * Get the owner for a file or folder
	 *
	 * @param string $path
	 * @return string
	 */
	public static function getOwner($path) {
		return self::$defaultInstance->getOwner($path);
	}

	/**
	 * get the ETag for a file or folder
	 */
	public static function getETag(string $path): string|false {
		return self::$defaultInstance->getETag($path);
	}
}
