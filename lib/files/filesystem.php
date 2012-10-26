<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * Class for abstraction of filesystem functions
 * This class won't call any filesystem functions for itself but but will pass them to the correct OC_Filestorage object
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
 *
 *   the &run parameter can be set to false to prevent the operation from occurring
 */

namespace OC\Files;

class Filesystem {
	static private $storages = array();
	static private $mounts = array();
	public static $loaded = false;
	/**
	 * @var \OC\Files\View $defaultInstance
	 */
	static private $defaultInstance;


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

	/**
	 * get the mountpoint of the storage object for a path
	( note: because a storage is not always mounted inside the fakeroot, the returned mountpoint is relative to the absolute root of the filesystem and doesn't take the chroot into account
	 *
	 * @param string $path
	 * @return string
	 */
	static public function getMountPoint($path) {
		\OC_Hook::emit(self::CLASSNAME, 'get_mountpoint', array('path' => $path));
		if (!$path) {
			$path = '/';
		}
		if ($path[0] !== '/') {
			$path = '/' . $path;
		}
		$path = str_replace('//', '/', $path);
		$foundMountPoint = '';
		$mountPoints = array_keys(self::$mounts);
		foreach ($mountPoints as $mountpoint) {
			if ($mountpoint == $path) {
				return $mountpoint;
			}
			if (strpos($path, $mountpoint) === 0 and strlen($mountpoint) > strlen($foundMountPoint)) {
				$foundMountPoint = $mountpoint;
			}
		}
		return $foundMountPoint;
	}

	/**
	 * get a list of all mount points in a directory
	 *
	 * @param string $path
	 * @return string[]
	 */
	static public function getMountPoints($path) {
		$path = self::normalizePath($path);
		if (strlen($path) > 1) {
			$path .= '/';
		}
		$pathLength = strlen($path);

		$mountPoints = array_keys(self::$mounts);
		$result = array();
		foreach ($mountPoints as $mountPoint) {
			if (substr($mountPoint, 0, $pathLength) === $path and strlen($mountPoint) > $pathLength) {
				$result[] = $mountPoint;
			}
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
		if (!isset(self::$storages[$mountPoint])) {
			$mount = self::$mounts[$mountPoint];
			self::$storages[$mountPoint] = self::createStorage($mount['class'], $mount['arguments']);
		}
		return self::$storages[$mountPoint];
	}

	/**
	 * resolve a path to a storage and internal path
	 *
	 * @param string $path
	 * @return array consisting of the storage and the internal path
	 */
	static public function resolvePath($path) {
		$mountpoint = self::getMountPoint($path);
		if ($mountpoint) {
			$storage = self::getStorage($mountpoint);
			if ($mountpoint === $path) {
				$internalPath = '';
			} else {
				$internalPath = substr($path, strlen($mountpoint));
			}
			return array($storage, $internalPath);
		} else {
			return array(null, null);
		}
	}

	static public function init($root) {
		if (self::$defaultInstance) {
			return false;
		}
		self::$defaultInstance = new View($root);

		//load custom mount config
		if (is_file(\OC::$SERVERROOT . '/config/mount.php')) {
			$mountConfig = include 'config/mount.php';
			if (isset($mountConfig['global'])) {
				foreach ($mountConfig['global'] as $mountPoint => $options) {
					self::mount($options['class'], $options['options'], $mountPoint);
				}
			}

			if (isset($mountConfig['group'])) {
				foreach ($mountConfig['group'] as $group => $mounts) {
					if (\OC_Group::inGroup(\OC_User::getUser(), $group)) {
						foreach ($mounts as $mountPoint => $options) {
							$mountPoint = self::setUserVars($mountPoint);
							foreach ($options as &$option) {
								$option = self::setUserVars($option);
							}
							self::mount($options['class'], $options['options'], $mountPoint);
						}
					}
				}
			}

			if (isset($mountConfig['user'])) {
				foreach ($mountConfig['user'] as $user => $mounts) {
					if ($user === 'all' or strtolower($user) === strtolower(\OC_User::getUser())) {
						foreach ($mounts as $mountPoint => $options) {
							$mountPoint = self::setUserVars($mountPoint);
							foreach ($options as &$option) {
								$option = self::setUserVars($option);
							}
							self::mount($options['class'], $options['options'], $mountPoint);
						}
					}
				}
			}
		}

		self::$loaded = true;

		return true;
	}

	/**
	 * fill in the correct values for $user, and $password placeholders
	 *
	 * @param string $input
	 * @return string
	 */
	private static function setUserVars($input) {
		return str_replace('$user', \OC_User::getUser(), $input);
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
		self::$storages = array();
	}

	/**
	 * create a new storage of a specific type
	 *
	 * @param  string $type
	 * @param  array $arguments
	 * @return \OC\Files\Storage\Storage
	 */
	static private function createStorage($class, $arguments) {
		if (class_exists($class)) {
			try {
				return new $class($arguments);
			} catch (\Exception $exception) {
				\OC_Log::write('core', $exception->getMessage(), \OC_Log::ERROR);
				return false;
			}
		} else {
			\OC_Log::write('core', 'storage backend ' . $class . ' not found', \OC_Log::ERROR);
			return false;
		}
	}

	/**
	 * @brief get the relative path of the root data directory for the current user
	 * @return string
	 *
	 * Returns path like /admin/files
	 */
	static public function getRoot() {
		return self::$defaultInstance->getRoot();
	}

	/**
	 * clear all mounts and storage backends
	 */
	public static function clearMounts() {
		self::$mounts = array();
		self::$storages = array();
	}

	/**
	 * mount an \OC\Files\Storage\Storage in our virtual filesystem
	 *
	 * @param \OC\Files\Storage\Storage|string $class
	 * @param array $arguments
	 * @param string $mountpoint
	 */
	static public function mount($class, $arguments, $mountpoint) {
		$mountpoint = self::normalizePath($mountpoint);
		if (strlen($mountpoint) > 1) {
			$mountpoint .= '/';
		}

		if ($class instanceof \OC\Files\Storage\Storage) {
			self::$mounts[$mountpoint] = array('class' => get_class($class), 'arguments' => $arguments);
			self::$storages[$mountpoint] = $class;
		} else {
			self::$mounts[$mountpoint] = array('class' => $class, 'arguments' => $arguments);
		}
	}

	/**
	 * return the path to a local version of the file
	 * we need this because we can't know if a file is stored local or not from outside the filestorage and for some purposes a local file is needed
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
		if (!$path || $path[0] !== '/') {
			$path = '/' . $path;
		}
		if (strstr($path, '/../') || strrchr($path, '/') === '/..') {
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
		$blacklist = array('.htaccess');
		if (isset($data['path'])) {
			$path = $data['path'];
		} else if (isset($data['newpath'])) {
			$path = $data['newpath'];
		}
		if (isset($path)) {
			$filename = strtolower(basename($path));
			if (in_array($filename, $blacklist)) {
				$data['run'] = false;
			}
		}
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
		return Cache\Cache::search($query);
	}

	/**
	 * check if a file or folder has been updated since $time
	 *
	 * @param int $time
	 * @return bool
	 */
	static public function hasUpdated($path, $time) {
		return self::$defaultInstance->hasUpdated($path, $time);
	}

	static public function removeETagHook($params, $root = false) {
		if (isset($params['path'])) {
			$path = $params['path'];
		} else {
			$path = $params['oldpath'];
		}

		if ($root) { // reduce path to the required part of it (no 'username/files')
			$fakeRootView = new View($root);
			$count = 1;
			$path = str_replace(\OC_App::getStorage("files")->getAbsolutePath(''), "", $fakeRootView->getAbsolutePath($path), $count);
		}

		$path = self::normalizePath($path);
		\OC_Connector_Sabre_Node::removeETagPropertyForPath($path);
	}

	/**
	 * normalize a path
	 *
	 * @param string $path
	 * @param bool $stripTrailingSlash
	 * @return string
	 */
	public static function normalizePath($path, $stripTrailingSlash = true) {
		if ($path == '') {
			return '/';
		}
//no windows style slashes
		$path = str_replace('\\', '/', $path);
//add leading slash
		if ($path[0] !== '/') {
			$path = '/' . $path;
		}
//remove duplicate slashes
		while (strpos($path, '//') !== false) {
			$path = str_replace('//', '/', $path);
		}
//remove trailing slash
		if ($stripTrailingSlash and strlen($path) > 1 and substr($path, -1, 1) === '/') {
			$path = substr($path, 0, -1);
		}
//normalize unicode if possible
		if (class_exists('Normalizer')) {
			$path = \Normalizer::normalize($path);
		}
		return $path;
	}

	/**
	 * get the filesystem info
	 *
	 * @param string $path
	 * @return array
	 *
	 * returns an associative array with the following keys:
	 * - size
	 * - mtime
	 * - mimetype
	 * - encrypted
	 * - versioned
	 */
	public static function getFileInfo($path) {
		return self::$defaultInstance->getFileInfo($path);
	}

	/**
	 * get the content of a directory
	 *
	 * @param string $directory path under datadirectory
	 * @return array
	 */
	public static function getDirectoryContent($directory, $mimetype_filter = '') {
		return self::$defaultInstance->getDirectoryContent($directory, $mimetype_filter);
	}
}

\OC_Hook::connect('OC_Filesystem', 'post_write', 'OC_Filesystem', 'removeETagHook');
\OC_Hook::connect('OC_Filesystem', 'post_delete', 'OC_Filesystem', 'removeETagHook');
\OC_Hook::connect('OC_Filesystem', 'post_rename', 'OC_Filesystem', 'removeETagHook');

\OC_Util::setupFS();
