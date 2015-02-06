<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * Class for abstraction of filesystem functions
 * This class won't call any filesystem functions for itself but will pass them to the correct OC_Filestorage object
 * this class should also handle all the file permission related stuff
 *
 * Hooks provided:
 *   read(path)
 *   write(path, &run)
 *   post_write(path)
 *   create(path, &run) (when a file is created, both create and write will be emitted in that order)
 *   post_create(path)
 *   delete(path, &run)
 *   post_delete(path)
 *   rename(oldpath,newpath, &run)
 *   post_rename(oldpath,newpath)
 *   copy(oldpath,newpath, &run) (if the newpath doesn't exists yes, copy, create and write will be emitted in that order)
 *   post_rename(oldpath,newpath)
 *   post_initMountPoints(user, user_dir)
 *
 *   the &run parameter can be set to false to prevent the operation from occurring
 */

namespace OC\Files;

use OC\Files\Storage\StorageFactory;

class Filesystem {

	/**
	 * @var Mount\Manager $mounts
	 */
	private static $mounts;

	public static $loaded = false;
	/**
	 * @var \OC\Files\View $defaultInstance
	 */
	static private $defaultInstance;

	static private $usersSetup = array();

	static private $normalizedPathCache = array();

	/**
	 * classname which used for hooks handling
	 * used as signalclass in OC_Hooks::emit()
	 */
	const CLASSNAME = 'OC_Filesystem';

	/**
	 * signalname emitted before file renaming
	 *
	 * @param string $oldpath
	 * @param string $newpath
	 */
	const signal_rename = 'rename';

	/**
	 * signal emitted after file renaming
	 *
	 * @param string $oldpath
	 * @param string $newpath
	 */
	const signal_post_rename = 'post_rename';

	/**
	 * signal emitted before file/dir creation
	 *
	 * @param string $path
	 * @param bool $run changing this flag to false in hook handler will cancel event
	 */
	const signal_create = 'create';

	/**
	 * signal emitted after file/dir creation
	 *
	 * @param string $path
	 * @param bool $run changing this flag to false in hook handler will cancel event
	 */
	const signal_post_create = 'post_create';

	/**
	 * signal emits before file/dir copy
	 *
	 * @param string $oldpath
	 * @param string $newpath
	 * @param bool $run changing this flag to false in hook handler will cancel event
	 */
	const signal_copy = 'copy';

	/**
	 * signal emits after file/dir copy
	 *
	 * @param string $oldpath
	 * @param string $newpath
	 */
	const signal_post_copy = 'post_copy';

	/**
	 * signal emits before file/dir save
	 *
	 * @param string $path
	 * @param bool $run changing this flag to false in hook handler will cancel event
	 */
	const signal_write = 'write';

	/**
	 * signal emits after file/dir save
	 *
	 * @param string $path
	 */
	const signal_post_write = 'post_write';

	/**
	 * signal emitted before file/dir update
	 *
	 * @param string $path
	 * @param bool $run changing this flag to false in hook handler will cancel event
	 */
	const signal_update = 'update';

	/**
	 * signal emitted after file/dir update
	 *
	 * @param string $path
	 * @param bool $run changing this flag to false in hook handler will cancel event
	 */
	const signal_post_update = 'post_update';

	/**
	 * signal emits when reading file/dir
	 *
	 * @param string $path
	 */
	const signal_read = 'read';

	/**
	 * signal emits when removing file/dir
	 *
	 * @param string $path
	 */
	const signal_delete = 'delete';

	/**
	 * parameters definitions for signals
	 */
	const signal_param_path = 'path';
	const signal_param_oldpath = 'oldpath';
	const signal_param_newpath = 'newpath';

	/**
	 * run - changing this flag to false in hook handler will cancel event
	 */
	const signal_param_run = 'run';

	const signal_create_mount = 'create_mount';
	const signal_delete_mount = 'delete_mount';
	const signal_param_mount_type = 'mounttype';
	const signal_param_users = 'users';

	/**
	 * @var \OC\Files\Storage\StorageFactory $loader
	 */
	private static $loader;

	/**
	 * @param callable $wrapper
	 */
	public static function addStorageWrapper($wrapperName, $wrapper) {
		$mounts = self::getMountManager()->getAll();
		if (!self::getLoader()->addStorageWrapper($wrapperName, $wrapper, $mounts)) {
			// do not re-wrap if storage with this name already existed
			return;
		}
	}

	/**
	 * Returns the storage factory
	 *
	 * @return \OCP\Files\Storage\IStorageFactory
	 */
	public static function getLoader() {
		if (!self::$loader) {
			self::$loader = new StorageFactory();
		}
		return self::$loader;
	}

	/**
	 * Returns the mount manager
	 *
	 * @return \OC\Files\Mount\Manager
	 */
	public static function getMountManager() {
		if (!self::$mounts) {
			\OC_Util::setupFS();
		}
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
	static public function getMountPoint($path) {
		if (!self::$mounts) {
			\OC_Util::setupFS();
		}
		$mount = self::$mounts->find($path);
		if ($mount) {
			return $mount->getMountPoint();
		} else {
			return '';
		}
	}

	/**
	 * get a list of all mount points in a directory
	 *
	 * @param string $path
	 * @return string[]
	 */
	static public function getMountPoints($path) {
		if (!self::$mounts) {
			\OC_Util::setupFS();
		}
		$result = array();
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
	 * @return \OC\Files\Storage\Storage
	 */
	public static function getStorage($mountPoint) {
		if (!self::$mounts) {
			\OC_Util::setupFS();
		}
		$mount = self::$mounts->find($mountPoint);
		return $mount->getStorage();
	}

	/**
	 * @param string $id
	 * @return Mount\MountPoint[]
	 */
	public static function getMountByStorageId($id) {
		if (!self::$mounts) {
			\OC_Util::setupFS();
		}
		return self::$mounts->findByStorageId($id);
	}

	/**
	 * @param int $id
	 * @return Mount\MountPoint[]
	 */
	public static function getMountByNumericId($id) {
		if (!self::$mounts) {
			\OC_Util::setupFS();
		}
		return self::$mounts->findByNumericId($id);
	}

	/**
	 * resolve a path to a storage and internal path
	 *
	 * @param string $path
	 * @return array an array consisting of the storage and the internal path
	 */
	static public function resolvePath($path) {
		if (!self::$mounts) {
			\OC_Util::setupFS();
		}
		$mount = self::$mounts->find($path);
		if ($mount) {
			return array($mount->getStorage(), rtrim($mount->getInternalPath($path), '/'));
		} else {
			return array(null, null);
		}
	}

	static public function init($user, $root) {
		if (self::$defaultInstance) {
			return false;
		}
		self::getLoader();
		self::$defaultInstance = new View($root);

		if (!self::$mounts) {
			self::$mounts = new Mount\Manager();
		}

		//load custom mount config
		self::initMountPoints($user);

		self::$loaded = true;

		return true;
	}

	static public function initMounts() {
		if (!self::$mounts) {
			self::$mounts = new Mount\Manager();
		}
	}

	/**
	 * Initialize system and personal mount points for a user
	 *
	 * @param string $user
	 */
	public static function initMountPoints($user = '') {
		if ($user == '') {
			$user = \OC_User::getUser();
		}
		if (isset(self::$usersSetup[$user])) {
			return;
		}
		self::$usersSetup[$user] = true;

		$root = \OC_User::getHome($user);

		$userObject = \OC_User::getManager()->get($user);

		if (!is_null($userObject)) {
			$homeStorage = \OC_Config::getValue( 'objectstore' );
			if (!empty($homeStorage)) {
				// sanity checks
				if (empty($homeStorage['class'])) {
					\OCP\Util::writeLog('files', 'No class given for objectstore', \OCP\Util::ERROR);
				}
				if (!isset($homeStorage['arguments'])) {
					$homeStorage['arguments'] = array();
				}
				// instantiate object store implementation
				$homeStorage['arguments']['objectstore'] = new $homeStorage['class']($homeStorage['arguments']);
				// mount with home object store implementation
				$homeStorage['class'] = '\OC\Files\ObjectStore\HomeObjectStoreStorage';
			} else {
				$homeStorage = array(
					//default home storage configuration:
					'class' => '\OC\Files\Storage\Home',
					'arguments' => array()
				);
			}
			$homeStorage['arguments']['user'] = $userObject;

			// check for legacy home id (<= 5.0.12)
			if (\OC\Files\Cache\Storage::exists('local::' . $root . '/')) {
				$homeStorage['arguments']['legacy'] = true;
			}

			self::mount($homeStorage['class'], $homeStorage['arguments'], $user);

			$home = \OC\Files\Filesystem::getStorage($user);
		}
		else {
			self::mount('\OC\Files\Storage\Local', array('datadir' => $root), $user);
		}

		self::mountCacheDir($user);

		// Chance to mount for other storages
		if($userObject) {
			$mountConfigManager = \OC::$server->getMountProviderCollection();
			$mounts = $mountConfigManager->getMountsForUser($userObject);
			array_walk($mounts, array(self::$mounts, 'addMount'));
		}
		\OC_Hook::emit('OC_Filesystem', 'post_initMountPoints', array('user' => $user, 'user_dir' => $root));
	}

	/**
	 * Mounts the cache directory
	 * @param string $user user name
	 */
	private static function mountCacheDir($user) {
		$cacheBaseDir = \OC_Config::getValue('cache_path', '');
		if ($cacheBaseDir === '') {
			// use local cache dir relative to the user's home
			$subdir = 'cache';
			$view = new \OC\Files\View('/' . $user);
			if(!$view->file_exists($subdir)) {
				$view->mkdir($subdir);
			}
		} else {
			$cacheDir = rtrim($cacheBaseDir, '/') . '/' . $user;
			if (!file_exists($cacheDir)) {
				mkdir($cacheDir, 0770, true);
			}
			// mount external cache dir to "/$user/cache" mount point
			self::mount('\OC\Files\Storage\Local', array('datadir' => $cacheDir), '/' . $user . '/cache');
		}
	}

	/**
	 * get the default filesystem view
	 *
	 * @return View
	 */
	static public function getView() {
		return self::$defaultInstance;
	}

	/**
	 * tear down the filesystem, removing all storage providers
	 */
	static public function tearDown() {
		self::clearMounts();
		self::$defaultInstance = null;
	}

	/**
	 * get the relative path of the root data directory for the current user
	 * @return string
	 *
	 * Returns path like /admin/files
	 */
	static public function getRoot() {
		if (!self::$defaultInstance) {
			return null;
		}
		return self::$defaultInstance->getRoot();
	}

	/**
	 * clear all mounts and storage backends
	 */
	public static function clearMounts() {
		if (self::$mounts) {
			self::$usersSetup = array();
			self::$mounts->clear();
		}
	}

	/**
	 * mount an \OC\Files\Storage\Storage in our virtual filesystem
	 *
	 * @param \OC\Files\Storage\Storage|string $class
	 * @param array $arguments
	 * @param string $mountpoint
	 */
	static public function mount($class, $arguments, $mountpoint) {
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
	 *
	 * @param string $path
	 * @return string
	 */
	static public function getLocalFile($path) {
		return self::$defaultInstance->getLocalFile($path);
	}

	/**
	 * @param string $path
	 * @return string
	 */
	static public function getLocalFolder($path) {
		return self::$defaultInstance->getLocalFolder($path);
	}

	/**
	 * return path to file which reflects one visible in browser
	 *
	 * @param string $path
	 * @return string
	 */
	static public function getLocalPath($path) {
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
	static public function isValidPath($path) {
		$path = self::normalizePath($path);
		if (!$path || $path[0] !== '/') {
			$path = '/' . $path;
		}
		if (strpos($path, '/../') !== FALSE || strrchr($path, '/') === '/..') {
			return false;
		}
		return true;
	}

	/**
	 * checks if a file is blacklisted for storage in the filesystem
	 * Listens to write and rename hooks
	 *
	 * @param array $data from hook
	 */
	static public function isBlacklisted($data) {
		if (isset($data['path'])) {
			$path = $data['path'];
		} else if (isset($data['newpath'])) {
			$path = $data['newpath'];
		}
		if (isset($path)) {
			if (self::isFileBlacklisted($path)) {
				$data['run'] = false;
			}
		}
	}

	/**
	 * @param string $filename
	 * @return bool
	 */
	static public function isFileBlacklisted($filename) {
		$filename = self::normalizePath($filename);

		$blacklist = \OC_Config::getValue('blacklisted_files', array('.htaccess'));
		$filename = strtolower(basename($filename));
		return in_array($filename, $blacklist);
	}

	/**
	 * check if the directory should be ignored when scanning
	 * NOTE: the special directories . and .. would cause never ending recursion
	 * @param String $dir
	 * @return boolean
	 */
	static public function isIgnoredDir($dir) {
		if ($dir === '.' || $dir === '..') {
			return true;
		}
		return false;
	}

	/**
	 * following functions are equivalent to their php builtin equivalents for arguments/return values.
	 */
	static public function mkdir($path) {
		return self::$defaultInstance->mkdir($path);
	}

	static public function rmdir($path) {
		return self::$defaultInstance->rmdir($path);
	}

	static public function opendir($path) {
		return self::$defaultInstance->opendir($path);
	}

	static public function readdir($path) {
		return self::$defaultInstance->readdir($path);
	}

	static public function is_dir($path) {
		return self::$defaultInstance->is_dir($path);
	}

	static public function is_file($path) {
		return self::$defaultInstance->is_file($path);
	}

	static public function stat($path) {
		return self::$defaultInstance->stat($path);
	}

	static public function filetype($path) {
		return self::$defaultInstance->filetype($path);
	}

	static public function filesize($path) {
		return self::$defaultInstance->filesize($path);
	}

	static public function readfile($path) {
		return self::$defaultInstance->readfile($path);
	}

	static public function isCreatable($path) {
		return self::$defaultInstance->isCreatable($path);
	}

	static public function isReadable($path) {
		return self::$defaultInstance->isReadable($path);
	}

	static public function isUpdatable($path) {
		return self::$defaultInstance->isUpdatable($path);
	}

	static public function isDeletable($path) {
		return self::$defaultInstance->isDeletable($path);
	}

	static public function isSharable($path) {
		return self::$defaultInstance->isSharable($path);
	}

	static public function file_exists($path) {
		return self::$defaultInstance->file_exists($path);
	}

	static public function filemtime($path) {
		return self::$defaultInstance->filemtime($path);
	}

	static public function touch($path, $mtime = null) {
		return self::$defaultInstance->touch($path, $mtime);
	}

	/**
	 * @return string
	 */
	static public function file_get_contents($path) {
		return self::$defaultInstance->file_get_contents($path);
	}

	static public function file_put_contents($path, $data) {
		return self::$defaultInstance->file_put_contents($path, $data);
	}

	static public function unlink($path) {
		return self::$defaultInstance->unlink($path);
	}

	static public function rename($path1, $path2) {
		return self::$defaultInstance->rename($path1, $path2);
	}

	static public function copy($path1, $path2) {
		return self::$defaultInstance->copy($path1, $path2);
	}

	static public function fopen($path, $mode) {
		return self::$defaultInstance->fopen($path, $mode);
	}

	/**
	 * @return string
	 */
	static public function toTmpFile($path) {
		return self::$defaultInstance->toTmpFile($path);
	}

	static public function fromTmpFile($tmpFile, $path) {
		return self::$defaultInstance->fromTmpFile($tmpFile, $path);
	}

	static public function getMimeType($path) {
		return self::$defaultInstance->getMimeType($path);
	}

	static public function hash($type, $path, $raw = false) {
		return self::$defaultInstance->hash($type, $path, $raw);
	}

	static public function free_space($path = '/') {
		return self::$defaultInstance->free_space($path);
	}

	static public function search($query) {
		return self::$defaultInstance->search($query);
	}

	/**
	 * @param string $query
	 */
	static public function searchByMime($query) {
		return self::$defaultInstance->searchByMime($query);
	}

	/**
	 * @param string|int $tag name or tag id
	 * @param string $userId owner of the tags
	 * @return FileInfo[] array or file info
	 */
	static public function searchByTag($tag, $userId) {
		return self::$defaultInstance->searchByTag($tag, $userId);
	}

	/**
	 * check if a file or folder has been updated since $time
	 *
	 * @param string $path
	 * @param int $time
	 * @return bool
	 */
	static public function hasUpdated($path, $time) {
		return self::$defaultInstance->hasUpdated($path, $time);
	}

	/**
	 * Fix common problems with a file path
	 * @param string $path
	 * @param bool $stripTrailingSlash
	 * @return string
	 */
	public static function normalizePath($path, $stripTrailingSlash = true, $isAbsolutePath = false) {
		$cacheKey = json_encode([$path, $stripTrailingSlash, $isAbsolutePath]);

		if(isset(self::$normalizedPathCache[$cacheKey])) {
			return self::$normalizedPathCache[$cacheKey];
		}

		if ($path == '') {
			return '/';
		}

		//normalize unicode if possible
		$path = \OC_Util::normalizeUnicode($path);

		//no windows style slashes
		$path = str_replace('\\', '/', $path);

		// When normalizing an absolute path, we need to ensure that the drive-letter
		// is still at the beginning on windows
		$windows_drive_letter = '';
		if ($isAbsolutePath && \OC_Util::runningOnWindows() && preg_match('#^([a-zA-Z])$#', $path[0]) && $path[1] == ':' && $path[2] == '/') {
			$windows_drive_letter = substr($path, 0, 2);
			$path = substr($path, 2);
		}

		//add leading slash
		if ($path[0] !== '/') {
			$path = '/' . $path;
		}

		// remove '/./'
		// ugly, but str_replace() can't replace them all in one go
		// as the replacement itself is part of the search string
		// which will only be found during the next iteration
		while (strpos($path, '/./') !== false) {
			$path = str_replace('/./', '/', $path);
		}
		// remove sequences of slashes
		$path = preg_replace('#/{2,}#', '/', $path);

		//remove trailing slash
		if ($stripTrailingSlash and strlen($path) > 1 and substr($path, -1, 1) === '/') {
			$path = substr($path, 0, -1);
		}

		// remove trailing '/.'
		if (substr($path, -2) == '/.') {
			$path = substr($path, 0, -2);
		}

		$normalizedPath = $windows_drive_letter . $path;
		self::$normalizedPathCache[$cacheKey] = $normalizedPath;

		return $normalizedPath;
	}

	/**
	 * get the filesystem info
	 *
	 * @param string $path
	 * @param boolean $includeMountPoints whether to add mountpoint sizes,
	 * defaults to true
	 * @return \OC\Files\FileInfo
	 */
	public static function getFileInfo($path, $includeMountPoints = true) {
		return self::$defaultInstance->getFileInfo($path, $includeMountPoints);
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
	 *
	 * @param string $path
	 * @return string
	 */
	static public function getETag($path) {
		return self::$defaultInstance->getETag($path);
	}
}
