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
 *   post_initMountPoints(user, user_dir)
 *
 *   the &run parameter can be set to false to prevent the operation from occurring
 */

namespace OC\Files;

const FREE_SPACE_UNKNOWN = -2;

class Filesystem {
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
	 * ( note: because a storage is not always mounted inside the fakeroot, the
	 * returned mountpoint is relative to the absolute root of the filesystem
	 * and doesn't take the chroot into account )
	 *
	 * @param string $path
	 * @return string
	 */
	static public function getMountPoint($path) {
		$mount = Mount::find($path);
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
		$result = array();
		$mounts = Mount::findIn($path);
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
		$mount = Mount::find($mountPoint);
		return $mount->getStorage();
	}

	/**
	 * resolve a path to a storage and internal path
	 *
	 * @param string $path
	 * @return array consisting of the storage and the internal path
	 */
	static public function resolvePath($path) {
		$mount = Mount::find($path);
		if ($mount) {
			return array($mount->getStorage(), $mount->getInternalPath($path));
		} else {
			return array(null, null);
		}
	}

	static public function init($user, $root) {
		if (self::$defaultInstance) {
			return false;
		}
		self::$defaultInstance = new View($root);

		//load custom mount config
		self::initMountPoints($user);

		self::$loaded = true;

		return true;
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
		$parser = new \OC\ArrayParser();

		$root = \OC_User::getHome($user);
		self::mount('\OC\Files\Storage\Local', array('datadir' => $root), $user);
		$datadir = \OC_Config::getValue("datadirectory", \OC::$SERVERROOT . "/data");

		//move config file to it's new position
		if (is_file(\OC::$SERVERROOT . '/config/mount.json')) {
			rename(\OC::$SERVERROOT . '/config/mount.json', $datadir . '/mount.json');
		}
		// Load system mount points
		if (is_file(\OC::$SERVERROOT . '/config/mount.php') or is_file($datadir . '/mount.json')) {
			if (is_file($datadir . '/mount.json')) {
				$mountConfig = json_decode(file_get_contents($datadir . '/mount.json'), true);
			} elseif (is_file(\OC::$SERVERROOT . '/config/mount.php')) {
				$mountConfig = $parser->parsePHP(file_get_contents(\OC::$SERVERROOT . '/config/mount.php'));
			}
			if (isset($mountConfig['global'])) {
				foreach ($mountConfig['global'] as $mountPoint => $options) {
					self::mount($options['class'], $options['options'], $mountPoint);
				}
			}
			if (isset($mountConfig['group'])) {
				foreach ($mountConfig['group'] as $group => $mounts) {
					if (\OC_Group::inGroup($user, $group)) {
						foreach ($mounts as $mountPoint => $options) {
							$mountPoint = self::setUserVars($user, $mountPoint);
							foreach ($options as &$option) {
								$option = self::setUserVars($user, $option);
							}
							self::mount($options['class'], $options['options'], $mountPoint);
						}
					}
				}
			}
			if (isset($mountConfig['user'])) {
				foreach ($mountConfig['user'] as $mountUser => $mounts) {
					if ($mountUser === 'all' or strtolower($mountUser) === strtolower($user)) {
						foreach ($mounts as $mountPoint => $options) {
							$mountPoint = self::setUserVars($user, $mountPoint);
							foreach ($options as &$option) {
								$option = self::setUserVars($user, $option);
							}
							self::mount($options['class'], $options['options'], $mountPoint);
						}
					}
				}
			}
		}
		// Load personal mount points
		if (is_file($root . '/mount.php') or is_file($root . '/mount.json')) {
			if (is_file($root . '/mount.json')) {
				$mountConfig = json_decode(file_get_contents($root . '/mount.json'), true);
			} elseif (is_file($root . '/mount.php')) {
				$mountConfig = $parser->parsePHP(file_get_contents($root . '/mount.php'));
			}
			if (isset($mountConfig['user'][$user])) {
				foreach ($mountConfig['user'][$user] as $mountPoint => $options) {
					self::mount($options['class'], $options['options'], $mountPoint);
				}
			}
		}

		// Chance to mount for other storages
		\OC_Hook::emit('OC_Filesystem', 'post_initMountPoints', array('user' => $user, 'user_dir' => $root));
	}

	/**
	 * fill in the correct values for $user, and $password placeholders
	 *
	 * @param string $input
	 * @param string $input
	 * @return string
	 */
	private static function setUserVars($user, $input) {
		return str_replace('$user', $user, $input);
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
		Mount::clear();
	}

	/**
	 * mount an \OC\Files\Storage\Storage in our virtual filesystem
	 *
	 * @param \OC\Files\Storage\Storage|string $class
	 * @param array $arguments
	 * @param string $mountpoint
	 */
	static public function mount($class, $arguments, $mountpoint) {
		new Mount($class, $mountpoint, $arguments);
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
		$blacklist = \OC_Config::getValue('blacklisted_files', array('.htaccess'));
		$filename = strtolower(basename($filename));
		return (in_array($filename, $blacklist));
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
		return self::$defaultInstance->search($query);
	}

	static public function searchByMime($query) {
		return self::$defaultInstance->searchByMime($query);
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
	 * @brief Fix common problems with a file path
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
	 * @return array
	 */
	public static function getDirectoryContent($directory) {
		return self::$defaultInstance->getDirectoryContent($directory);
	}

	/**
	 * Get the path of a file by id
	 *
	 * Note that the resulting path is not guarantied to be unique for the id, multiple paths can point to the same file
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

\OC_Util::setupFS();
