<?php
/**
 * ownCloud - trash bin
 *
 * @author Bjoern Schiessle
 * @copyright 2013 Bjoern Schiessle schiessle@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_Trashbin;

use OC\Files\Filesystem;

class Trashbin {
	// how long do we keep files in the trash bin if no other value is defined in the config file (unit: days)

	const DEFAULT_RETENTION_OBLIGATION = 30;

	// unit: percentage; 50% of available disk space/quota
	const DEFAULTMAXSIZE = 50;

	public static function getUidAndFilename($filename) {
		$uid = \OC\Files\Filesystem::getOwner($filename);
		\OC\Files\Filesystem::initMountPoints($uid);
		if ($uid != \OCP\User::getUser()) {
			$info = \OC\Files\Filesystem::getFileInfo($filename);
			$ownerView = new \OC\Files\View('/' . $uid . '/files');
			$filename = $ownerView->getPath($info['fileid']);
		}
		return array($uid, $filename);
	}

	/**
	 * get original location of files for user
	 *
	 * @param string $user
	 * @return array (filename => array (timestamp => original location))
	 */
	public static function getLocations($user) {
		$query = \OC_DB::prepare('SELECT `id`, `timestamp`, `location`'
			. ' FROM `*PREFIX*files_trash` WHERE `user`=?');
		$result = $query->execute(array($user));
		$array = array();
		while ($row = $result->fetchRow()) {
			if (isset($array[$row['id']])) {
				$array[$row['id']][$row['timestamp']] = $row['location'];
			} else {
				$array[$row['id']] = array($row['timestamp'] => $row['location']);
			}
		}
		return $array;
	}

	/**
	 * get original location of file
	 *
	 * @param string $user
	 * @param string $filename
	 * @param string $timestamp
	 * @return string original location
	 */
	public static function getLocation($user, $filename, $timestamp) {
		$query = \OC_DB::prepare('SELECT `location` FROM `*PREFIX*files_trash`'
			. ' WHERE `user`=? AND `id`=? AND `timestamp`=?');
		$result = $query->execute(array($user, $filename, $timestamp))->fetchAll();
		if (isset($result[0]['location'])) {
			return $result[0]['location'];
		} else {
			return false;
		}
	}

	private static function setUpTrash($user) {
		$view = new \OC\Files\View('/' . $user);
		if (!$view->is_dir('files_trashbin')) {
			$view->mkdir('files_trashbin');
		}
		if (!$view->is_dir('files_trashbin/files')) {
			$view->mkdir('files_trashbin/files');
		}
		if (!$view->is_dir('files_trashbin/versions')) {
			$view->mkdir('files_trashbin/versions');
		}
		if (!$view->is_dir('files_trashbin/keys')) {
			$view->mkdir('files_trashbin/keys');
		}
	}


	/**
	 * copy file to owners trash
	 * @param string $sourcePath
	 * @param string $owner
	 * @param string $ownerPath
	 * @param integer $timestamp
	 */
	private static function copyFilesToOwner($sourcePath, $owner, $ownerPath, $timestamp) {
		self::setUpTrash($owner);

		$ownerFilename = basename($ownerPath);
		$ownerLocation = dirname($ownerPath);

		$sourceFilename = basename($sourcePath);

		$view = new \OC\Files\View('/');

		$source = \OCP\User::getUser() . '/files_trashbin/files/' . $sourceFilename . '.d' . $timestamp;
		$target = $owner . '/files_trashbin/files/' . $ownerFilename . '.d' . $timestamp;
		self::copy_recursive($source, $target, $view);


		if ($view->file_exists($target)) {
			$query = \OC_DB::prepare("INSERT INTO `*PREFIX*files_trash` (`id`,`timestamp`,`location`,`user`) VALUES (?,?,?,?)");
			$result = $query->execute(array($ownerFilename, $timestamp, $ownerLocation, $owner));
			if (!$result) {
				\OC_Log::write('files_trashbin', 'trash bin database couldn\'t be updated for the files owner', \OC_log::ERROR);
			}
		}
	}


	/**
	 * move file to the trash bin
	 *
	 * @param string $file_path path to the deleted file/directory relative to the files root directory
	 */
	public static function move2trash($file_path) {
		// get the user for which the filesystem is setup
		$root = Filesystem::getRoot();
		list(, $user) = explode('/', $root);
		$size = 0;
		list($owner, $ownerPath) = self::getUidAndFilename($file_path);

		$view = new \OC\Files\View('/' . $user);
		// file has been deleted in between
		if (!$view->file_exists('/files/' . $file_path)) {
			return true;
		}

		self::setUpTrash($user);

		$path_parts = pathinfo($file_path);

		$filename = $path_parts['basename'];
		$location = $path_parts['dirname'];
		$timestamp = time();

		$userTrashSize = self::getTrashbinSize($user);

		// disable proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;
		$trashPath = '/files_trashbin/files/' . $filename . '.d' . $timestamp;
		try {
			$sizeOfAddedFiles = $view->filesize('/files/' . $file_path);
			if ($view->file_exists($trashPath)) {
				$view->unlink($trashPath);
			}
			$view->rename('/files/' . $file_path, $trashPath);
		} catch (\OCA\Files_Trashbin\Exceptions\CopyRecursiveException $e) {
			$sizeOfAddedFiles = false;
			if ($view->file_exists($trashPath)) {
				$view->deleteAll($trashPath);
			}
			\OC_Log::write('files_trashbin', 'Couldn\'t move ' . $file_path . ' to the trash bin', \OC_log::ERROR);
		}
		\OC_FileProxy::$enabled = $proxyStatus;

		if ($view->file_exists('/files/' . $file_path)) { // failed to delete the original file, abort
			$view->unlink($trashPath);
			return false;
		}

		if ($sizeOfAddedFiles !== false) {
			$size = $sizeOfAddedFiles;
			$query = \OC_DB::prepare("INSERT INTO `*PREFIX*files_trash` (`id`,`timestamp`,`location`,`user`) VALUES (?,?,?,?)");
			$result = $query->execute(array($filename, $timestamp, $location, $user));
			if (!$result) {
				\OC_Log::write('files_trashbin', 'trash bin database couldn\'t be updated', \OC_log::ERROR);
			}
			\OCP\Util::emitHook('\OCA\Files_Trashbin\Trashbin', 'post_moveToTrash', array('filePath' => \OC\Files\Filesystem::normalizePath($file_path),
				'trashPath' => \OC\Files\Filesystem::normalizePath($filename . '.d' . $timestamp)));

			$size += self::retainVersions($file_path, $filename, $timestamp);
			$size += self::retainEncryptionKeys($file_path, $filename, $timestamp);

			// if owner !== user we need to also add a copy to the owners trash
			if ($user !== $owner) {
				self::copyFilesToOwner($file_path, $owner, $ownerPath, $timestamp);
			}
		}

		$userTrashSize += $size;
		$userTrashSize -= self::expire($userTrashSize, $user);

		// if owner !== user we also need to update the owners trash size
		if ($owner !== $user) {
			$ownerTrashSize = self::getTrashbinSize($owner);
			$ownerTrashSize += $size;
			$ownerTrashSize -= self::expire($ownerTrashSize, $owner);
		}

		return ($sizeOfAddedFiles === false) ? false : true;
	}

	/**
	 * Move file versions to trash so that they can be restored later
	 *
	 * @param string $file_path path to original file
	 * @param string $filename of deleted file
	 * @param integer $timestamp when the file was deleted
	 *
	 * @return int size of stored versions
	 */
	private static function retainVersions($file_path, $filename, $timestamp) {
		$size = 0;
		if (\OCP\App::isEnabled('files_versions')) {

			// disable proxy to prevent recursive calls
			$proxyStatus = \OC_FileProxy::$enabled;
			\OC_FileProxy::$enabled = false;

			$user = \OCP\User::getUser();
			$rootView = new \OC\Files\View('/');

			list($owner, $ownerPath) = self::getUidAndFilename($file_path);
			// file has been deleted in between
			if (empty($ownerPath)) {
				return 0;
			}

			if ($rootView->is_dir($owner . '/files_versions/' . $ownerPath)) {
				$size += self::calculateSize(new \OC\Files\View('/' . $owner . '/files_versions/' . $ownerPath));
				if ($owner !== $user) {
					self::copy_recursive($owner . '/files_versions/' . $ownerPath, $owner . '/files_trashbin/versions/' . basename($ownerPath) . '.d' . $timestamp, $rootView);
				}
				$rootView->rename($owner . '/files_versions/' . $ownerPath, $user . '/files_trashbin/versions/' . $filename . '.d' . $timestamp);
			} else if ($versions = \OCA\Files_Versions\Storage::getVersions($owner, $ownerPath)) {
				foreach ($versions as $v) {
					$size += $rootView->filesize($owner . '/files_versions' . $v['path'] . '.v' . $v['version']);
					if ($owner !== $user) {
						$rootView->copy($owner . '/files_versions' . $v['path'] . '.v' . $v['version'], $owner . '/files_trashbin/versions/' . $v['name'] . '.v' . $v['version'] . '.d' . $timestamp);
					}
					$rootView->rename($owner . '/files_versions' . $v['path'] . '.v' . $v['version'], $user . '/files_trashbin/versions/' . $filename . '.v' . $v['version'] . '.d' . $timestamp);
				}
			}

			// enable proxy
			\OC_FileProxy::$enabled = $proxyStatus;
		}

		return $size;
	}

	/**
	 * Move encryption keys to trash so that they can be restored later
	 *
	 * @param string $file_path path to original file
	 * @param string $filename of deleted file
	 * @param integer $timestamp when the file was deleted
	 *
	 * @return int size of encryption keys
	 */
	private static function retainEncryptionKeys($file_path, $filename, $timestamp) {
		$size = 0;

		if (\OCP\App::isEnabled('files_encryption')) {

			$user = \OCP\User::getUser();
			$rootView = new \OC\Files\View('/');

			list($owner, $ownerPath) = self::getUidAndFilename($file_path);

			// file has been deleted in between
			if (empty($ownerPath)) {
				return 0;
			}

			$util = new \OCA\Files_Encryption\Util($rootView, $user);

			$baseDir = '/files_encryption/';
			if (!$util->isSystemWideMountPoint($ownerPath)) {
				$baseDir = $owner . $baseDir;
			}

			$keyfiles = \OC\Files\Filesystem::normalizePath($baseDir . '/keys/' . $ownerPath);

			if ($rootView->is_dir($keyfiles)) {
				$size += self::calculateSize(new \OC\Files\View($keyfiles));
				if ($owner !== $user) {
					self::copy_recursive($keyfiles, $owner . '/files_trashbin/keys/' . basename($ownerPath) . '.d' . $timestamp, $rootView);
				}
				$rootView->rename($keyfiles, $user . '/files_trashbin/keys/' . $filename . '.d' . $timestamp);
			}

		}
		return $size;
	}

	/**
	 * restore files from trash bin
	 *
	 * @param string $file path to the deleted file
	 * @param string $filename name of the file
	 * @param int $timestamp time when the file was deleted
	 *
	 * @return bool
	 */
	public static function restore($file, $filename, $timestamp) {

		$user = \OCP\User::getUser();
		$view = new \OC\Files\View('/' . $user);

		$location = '';
		if ($timestamp) {
			$location = self::getLocation($user, $filename, $timestamp);
			if ($location === false) {
				\OC_Log::write('files_trashbin', 'trash bin database inconsistent!', \OC_Log::ERROR);
			} else {
				// if location no longer exists, restore file in the root directory
				if ($location !== '/' &&
					(!$view->is_dir('files/' . $location) ||
					!$view->isCreatable('files/' . $location))
				) {
					$location = '';
				}
			}
		}

		// we need a  extension in case a file/dir with the same name already exists
		$uniqueFilename = self::getUniqueFilename($location, $filename, $view);

		$source = \OC\Files\Filesystem::normalizePath('files_trashbin/files/' . $file);
		$target = \OC\Files\Filesystem::normalizePath('files/' . $location . '/' . $uniqueFilename);
		$mtime = $view->filemtime($source);

		// disable proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// restore file
		$restoreResult = $view->rename($source, $target);

		// handle the restore result
		if ($restoreResult) {
			$fakeRoot = $view->getRoot();
			$view->chroot('/' . $user . '/files');
			$view->touch('/' . $location . '/' . $uniqueFilename, $mtime);
			$view->chroot($fakeRoot);
			\OCP\Util::emitHook('\OCA\Files_Trashbin\Trashbin', 'post_restore', array('filePath' => \OC\Files\Filesystem::normalizePath('/' . $location . '/' . $uniqueFilename),
				'trashPath' => \OC\Files\Filesystem::normalizePath($file)));

			self::restoreVersions($view, $file, $filename, $uniqueFilename, $location, $timestamp);
			self::restoreEncryptionKeys($view, $file, $filename, $uniqueFilename, $location, $timestamp);

			if ($timestamp) {
				$query = \OC_DB::prepare('DELETE FROM `*PREFIX*files_trash` WHERE `user`=? AND `id`=? AND `timestamp`=?');
				$query->execute(array($user, $filename, $timestamp));
			}

			// enable proxy
			\OC_FileProxy::$enabled = $proxyStatus;

			return true;
		}

		// enable proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		return false;
	}

	/**
	 * restore versions from trash bin
	 *
	 * @param \OC\Files\View $view file view
	 * @param string $file complete path to file
	 * @param string $filename name of file once it was deleted
	 * @param string $uniqueFilename new file name to restore the file without overwriting existing files
	 * @param string $location location if file
	 * @param int $timestamp deletion time
	 * @return bool
	 */
	private static function restoreVersions(\OC\Files\View $view, $file, $filename, $uniqueFilename, $location, $timestamp) {

		if (\OCP\App::isEnabled('files_versions')) {
			// disable proxy to prevent recursive calls
			$proxyStatus = \OC_FileProxy::$enabled;
			\OC_FileProxy::$enabled = false;

			$user = \OCP\User::getUser();
			$rootView = new \OC\Files\View('/');

			$target = \OC\Files\Filesystem::normalizePath('/' . $location . '/' . $uniqueFilename);

			list($owner, $ownerPath) = self::getUidAndFilename($target);

			// file has been deleted in between
			if (empty($ownerPath)) {
				\OC_FileProxy::$enabled = $proxyStatus;
				return false;
			}

			if ($timestamp) {
				$versionedFile = $filename;
			} else {
				$versionedFile = $file;
			}

			if ($view->is_dir('/files_trashbin/versions/' . $file)) {
				$rootView->rename(\OC\Files\Filesystem::normalizePath($user . '/files_trashbin/versions/' . $file), \OC\Files\Filesystem::normalizePath($owner . '/files_versions/' . $ownerPath));
			} else if ($versions = self::getVersionsFromTrash($versionedFile, $timestamp)) {
				foreach ($versions as $v) {
					if ($timestamp) {
						$rootView->rename($user . '/files_trashbin/versions/' . $versionedFile . '.v' . $v . '.d' . $timestamp, $owner . '/files_versions/' . $ownerPath . '.v' . $v);
					} else {
						$rootView->rename($user . '/files_trashbin/versions/' . $versionedFile . '.v' . $v, $owner . '/files_versions/' . $ownerPath . '.v' . $v);
					}
				}
			}

			// enable proxy
			\OC_FileProxy::$enabled = $proxyStatus;
		}
	}

	/**
	 * restore encryption keys from trash bin
	 *
	 * @param \OC\Files\View $view
	 * @param string $file complete path to file
	 * @param string $filename name of file
	 * @param string $uniqueFilename new file name to restore the file without overwriting existing files
	 * @param string $location location of file
	 * @param int $timestamp deletion time
	 * @return bool
	 */
	private static function restoreEncryptionKeys(\OC\Files\View $view, $file, $filename, $uniqueFilename, $location, $timestamp) {

		if (\OCP\App::isEnabled('files_encryption')) {
			$user = \OCP\User::getUser();
			$rootView = new \OC\Files\View('/');

			$target = \OC\Files\Filesystem::normalizePath('/' . $location . '/' . $uniqueFilename);

			list($owner, $ownerPath) = self::getUidAndFilename($target);

			// file has been deleted in between
			if (empty($ownerPath)) {
				return false;
			}

			$util = new \OCA\Files_Encryption\Util($rootView, $user);

			$baseDir = '/files_encryption/';
			if (!$util->isSystemWideMountPoint($ownerPath)) {
				$baseDir = $owner . $baseDir;
			}

			$source_location = dirname($file);

			if ($view->is_dir('/files_trashbin/keys/' . $file)) {
				if ($source_location != '.') {
					$keyfile = \OC\Files\Filesystem::normalizePath($user . '/files_trashbin/keys/' . $source_location . '/' . $filename);
				} else {
					$keyfile = \OC\Files\Filesystem::normalizePath($user . '/files_trashbin/keys/' . $filename);
				}
			}

			if ($timestamp) {
				$keyfile .= '.d' . $timestamp;
			}

			if ($rootView->is_dir($keyfile)) {
				$rootView->rename($keyfile, $baseDir . '/keys/' . $ownerPath);
			}

		}
	}

	/**
	 * delete all files from the trash
	 */
	public static function deleteAll() {
		$user = \OCP\User::getUser();
		$view = new \OC\Files\View('/' . $user);
		$view->deleteAll('files_trashbin');
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*files_trash` WHERE `user`=?');
		$query->execute(array($user));

		return true;
	}


	/**
	 * delete file from trash bin permanently
	 *
	 * @param string $filename path to the file
	 * @param string $user
	 * @param int $timestamp of deletion time
	 *
	 * @return int size of deleted files
	 */
	public static function delete($filename, $user, $timestamp = null) {
		$view = new \OC\Files\View('/' . $user);
		$size = 0;

		if ($timestamp) {
			$query = \OC_DB::prepare('DELETE FROM `*PREFIX*files_trash` WHERE `user`=? AND `id`=? AND `timestamp`=?');
			$query->execute(array($user, $filename, $timestamp));
			$file = $filename . '.d' . $timestamp;
		} else {
			$file = $filename;
		}

		$size += self::deleteVersions($view, $file, $filename, $timestamp);
		$size += self::deleteEncryptionKeys($view, $file, $filename, $timestamp);

		if ($view->is_dir('/files_trashbin/files/' . $file)) {
			$size += self::calculateSize(new \OC\Files\View('/' . $user . '/files_trashbin/files/' . $file));
		} else {
			$size += $view->filesize('/files_trashbin/files/' . $file);
		}
		\OC_Hook::emit('\OCP\Trashbin', 'preDelete', array('path' => '/files_trashbin/files/' . $file));
		$view->unlink('/files_trashbin/files/' . $file);
		\OC_Hook::emit('\OCP\Trashbin', 'delete', array('path' => '/files_trashbin/files/' . $file));

		return $size;
	}

	/**
	 * @param \OC\Files\View $view
	 * @param $file
	 * @param $filename
	 * @param $timestamp
	 * @return int
	 */
	private static function deleteVersions(\OC\Files\View $view, $file, $filename, $timestamp) {
		$size = 0;
		if (\OCP\App::isEnabled('files_versions')) {
			$user = \OCP\User::getUser();
			if ($view->is_dir('files_trashbin/versions/' . $file)) {
				$size += self::calculateSize(new \OC\Files\view('/' . $user . '/files_trashbin/versions/' . $file));
				$view->unlink('files_trashbin/versions/' . $file);
			} else if ($versions = self::getVersionsFromTrash($filename, $timestamp)) {
				foreach ($versions as $v) {
					if ($timestamp) {
						$size += $view->filesize('/files_trashbin/versions/' . $filename . '.v' . $v . '.d' . $timestamp);
						$view->unlink('/files_trashbin/versions/' . $filename . '.v' . $v . '.d' . $timestamp);
					} else {
						$size += $view->filesize('/files_trashbin/versions/' . $filename . '.v' . $v);
						$view->unlink('/files_trashbin/versions/' . $filename . '.v' . $v);
					}
				}
			}
		}
		return $size;
	}

	/**
	 * @param \OC\Files\View $view
	 * @param $file
	 * @param $filename
	 * @param $timestamp
	 * @return int
	 */
	private static function deleteEncryptionKeys(\OC\Files\View $view, $file, $filename, $timestamp) {
		$size = 0;
		if (\OCP\App::isEnabled('files_encryption')) {
			$user = \OCP\User::getUser();

			$keyfiles = \OC\Files\Filesystem::normalizePath('files_trashbin/keys/' . $filename);

			if ($timestamp) {
				$keyfiles .= '.d' . $timestamp;
			}
			if ($view->is_dir($keyfiles)) {
				$size += self::calculateSize(new \OC\Files\View('/' . $user . '/' . $keyfiles));
				$view->deleteAll($keyfiles);

			}
		}
		return $size;
	}

	/**
	 * check to see whether a file exists in trashbin
	 *
	 * @param string $filename path to the file
	 * @param int $timestamp of deletion time
	 * @return bool true if file exists, otherwise false
	 */
	public static function file_exists($filename, $timestamp = null) {
		$user = \OCP\User::getUser();
		$view = new \OC\Files\View('/' . $user);

		if ($timestamp) {
			$filename = $filename . '.d' . $timestamp;
		} else {
			$filename = $filename;
		}

		$target = \OC\Files\Filesystem::normalizePath('files_trashbin/files/' . $filename);
		return $view->file_exists($target);
	}

	/**
	 * deletes used space for trash bin in db if user was deleted
	 *
	 * @param string $uid id of deleted user
	 * @return bool result of db delete operation
	 */
	public static function deleteUser($uid) {
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*files_trash` WHERE `user`=?');
		return $query->execute(array($uid));
	}

	/**
	 * calculate remaining free space for trash bin
	 *
	 * @param integer $trashbinSize current size of the trash bin
	 * @param string $user
	 * @return int available free space for trash bin
	 */
	private static function calculateFreeSpace($trashbinSize, $user) {
		$config = \OC::$server->getConfig();

		$softQuota = true;
		$quota = $config->getUserValue($user, 'files', 'quota', null);
		$view = new \OC\Files\View('/' . $user);
		if ($quota === null || $quota === 'default') {
			$quota = $config->getAppValue('files', 'default_quota', null);
		}
		if ($quota === null || $quota === 'none') {
			$quota = \OC\Files\Filesystem::free_space('/');
			$softQuota = false;
			if ($quota === \OCP\Files\FileInfo::SPACE_UNKNOWN) {
				$quota = 0;
			}
		} else {
			$quota = \OCP\Util::computerFileSize($quota);
		}

		// calculate available space for trash bin
		// subtract size of files and current trash bin size from quota
		if ($softQuota) {
			$rootInfo = $view->getFileInfo('/files/', false);
			$free = $quota - $rootInfo['size']; // remaining free space for user
			if ($free > 0) {
				$availableSpace = ($free * self::DEFAULTMAXSIZE / 100) - $trashbinSize; // how much space can be used for versions
			} else {
				$availableSpace = $free - $trashbinSize;
			}
		} else {
			$availableSpace = $quota;
		}

		return $availableSpace;
	}

	/**
	 * resize trash bin if necessary after a new file was added to ownCloud
	 * @param string $user user id
	 */
	public static function resizeTrash($user) {

		$size = self::getTrashbinSize($user);

		$freeSpace = self::calculateFreeSpace($size, $user);

		if ($freeSpace < 0) {
			self::expire($size, $user);
		}
	}

	/**
	 * clean up the trash bin
	 *
	 * @param int $trashbinSize current size of the trash bin
	 * @param string $user
	 * @return int size of expired files
	 */
	private static function expire($trashbinSize, $user) {

		// let the admin disable auto expire
		$autoExpire = \OC_Config::getValue('trashbin_auto_expire', true);
		if ($autoExpire === false) {
			return 0;
		}

		$availableSpace = self::calculateFreeSpace($trashbinSize, $user);
		$size = 0;

		$retention_obligation = \OC_Config::getValue('trashbin_retention_obligation', self::DEFAULT_RETENTION_OBLIGATION);

		$limit = time() - ($retention_obligation * 86400);

		$dirContent = Helper::getTrashFiles('/', $user, 'mtime');

		// delete all files older then $retention_obligation
		list($delSize, $count) = self::deleteExpiredFiles($dirContent, $user, $limit, $retention_obligation);

		$size += $delSize;
		$availableSpace += $size;

		// delete files from trash until we meet the trash bin size limit again
		$size += self::deleteFiles(array_slice($dirContent, $count), $user, $availableSpace);

		return $size;
	}

	/**
	 * if the size limit for the trash bin is reached, we delete the oldest
	 * files in the trash bin until we meet the limit again
	 * @param array $files
	 * @param string $user
	 * @param int $availableSpace available disc space
	 * @return int size of deleted files
	 */
	protected static function deleteFiles($files, $user, $availableSpace) {
		$size = 0;

		if ($availableSpace < 0) {
			foreach ($files as $file) {
				if ($availableSpace < 0) {
					$tmp = self::delete($file['name'], $user, $file['mtime']);
					\OC_Log::write('files_trashbin', 'remove "' . $file['name'] . '" (' . $tmp . 'B) to meet the limit of trash bin size (50% of available quota)', \OC_log::INFO);
					$availableSpace += $tmp;
					$size += $tmp;
				} else {
					break;
				}
			}
		}
		return $size;
	}

	/**
	 * delete files older then max storage time
	 *
	 * @param array $files list of files sorted by mtime
	 * @param string $user
	 * @param int $limit files older then limit should be deleted
	 * @param int $retention_obligation max age of file in days
	 * @return array size of deleted files and number of deleted files
	 */
	protected static function deleteExpiredFiles($files, $user, $limit, $retention_obligation) {
		$size = 0;
		$count = 0;
		foreach ($files as $file) {
			$timestamp = $file['mtime'];
			$filename = $file['name'];
			if ($timestamp <= $limit) {
				$count++;
				$size += self::delete($filename, $user, $timestamp);
				\OC_Log::write('files_trashbin', 'remove "' . $filename . '" from trash bin because it is older than ' . $retention_obligation, \OC_log::INFO);
			} else {
				break;
			}
		}

		return array($size, $count);
	}

	/**
	 * recursive copy to copy a whole directory
	 *
	 * @param string $source source path, relative to the users files directory
	 * @param string $destination destination path relative to the users root directoy
	 * @param \OC\Files\View $view file view for the users root directory
	 * @return int
	 * @throws Exceptions\CopyRecursiveException
	 */
	private static function copy_recursive($source, $destination, \OC\Files\View $view) {
		$size = 0;
		if ($view->is_dir($source)) {
			$view->mkdir($destination);
			$view->touch($destination, $view->filemtime($source));
			foreach ($view->getDirectoryContent($source) as $i) {
				$pathDir = $source . '/' . $i['name'];
				if ($view->is_dir($pathDir)) {
					$size += self::copy_recursive($pathDir, $destination . '/' . $i['name'], $view);
				} else {
					$size += $view->filesize($pathDir);
					$result = $view->copy($pathDir, $destination . '/' . $i['name']);
					if (!$result) {
						throw new \OCA\Files_Trashbin\Exceptions\CopyRecursiveException();
					}
					$view->touch($destination . '/' . $i['name'], $view->filemtime($pathDir));
				}
			}
		} else {
			$size += $view->filesize($source);
			$result = $view->copy($source, $destination);
			if (!$result) {
				throw new \OCA\Files_Trashbin\Exceptions\CopyRecursiveException();
			}
			$view->touch($destination, $view->filemtime($source));
		}
		return $size;
	}

	/**
	 * find all versions which belong to the file we want to restore
	 *
	 * @param string $filename name of the file which should be restored
	 * @param int $timestamp timestamp when the file was deleted
	 * @return array
	 */
	private static function getVersionsFromTrash($filename, $timestamp) {
		$view = new \OC\Files\View('/' . \OCP\User::getUser() . '/files_trashbin/versions');
		$versions = array();

		//force rescan of versions, local storage may not have updated the cache
		/** @var \OC\Files\Storage\Storage $storage */
		list($storage, ) = $view->resolvePath('/');
		$storage->getScanner()->scan('files_trashbin');

		if ($timestamp) {
			// fetch for old versions
			$matches = $view->searchRaw($filename . '.v%.d' . $timestamp);
			$offset = -strlen($timestamp) - 2;
		} else {
			$matches = $view->searchRaw($filename . '.v%');
		}

		if (is_array($matches)) {
			foreach ($matches as $ma) {
				if ($timestamp) {
					$parts = explode('.v', substr($ma['path'], 0, $offset));
					$versions[] = (end($parts));
				} else {
					$parts = explode('.v', $ma);
					$versions[] = (end($parts));
				}
			}
		}
		return $versions;
	}

	/**
	 * find unique extension for restored file if a file with the same name already exists
	 *
	 * @param string $location where the file should be restored
	 * @param string $filename name of the file
	 * @param \OC\Files\View $view filesystem view relative to users root directory
	 * @return string with unique extension
	 */
	private static function getUniqueFilename($location, $filename, \OC\Files\View $view) {
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$name = pathinfo($filename, PATHINFO_FILENAME);
		$l = \OC::$server->getL10N('files_trashbin');

		// if extension is not empty we set a dot in front of it
		if ($ext !== '') {
			$ext = '.' . $ext;
		}

		if ($view->file_exists('files' . $location . '/' . $filename)) {
			$i = 2;
			$uniqueName = $name . " (" . $l->t("restored") . ")" . $ext;
			while ($view->file_exists('files' . $location . '/' . $uniqueName)) {
				$uniqueName = $name . " (" . $l->t("restored") . " " . $i . ")" . $ext;
				$i++;
			}

			return $uniqueName;
		}

		return $filename;
	}

	/**
	 * get the size from a given root folder
	 * @param \OC\Files\View $view file view on the root folder
	 * @return integer size of the folder
	 */
	private static function calculateSize($view) {
		$root = \OC::$server->getConfig()->getSystemValue('datadirectory') . $view->getAbsolutePath('');
		if (!file_exists($root)) {
			return 0;
		}
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root), \RecursiveIteratorIterator::CHILD_FIRST);
		$size = 0;

                /**
		 * RecursiveDirectoryIterator on an NFS path isn't iterable with foreach
		 * This bug is fixed in PHP 5.5.9 or before
		 * See #8376
		 */
		$iterator->rewind();
		while ($iterator->valid()) {
			$path = $iterator->current();
			$relpath = substr($path, strlen($root) - 1);
			if (!$view->is_dir($relpath)) {
				$size += $view->filesize($relpath);
			}
			$iterator->next();
		}
		return $size;
	}

	/**
	 * get current size of trash bin from a given user
	 *
	 * @param string $user user who owns the trash bin
	 * @return integer trash bin size
	 */
	private static function getTrashbinSize($user) {
		$view = new \OC\Files\View('/' . $user);
		$fileInfo = $view->getFileInfo('/files_trashbin');
		return isset($fileInfo['size']) ? $fileInfo['size'] : 0;
	}

	/**
	 * register hooks
	 */
	public static function registerHooks() {
		// create storage wrapper on setup
		\OCP\Util::connectHook('OC_Filesystem', 'setup', 'OCA\Files_Trashbin\Storage', 'setupStorage');
		//Listen to delete user signal
		\OCP\Util::connectHook('OC_User', 'pre_deleteUser', 'OCA\Files_Trashbin\Hooks', 'deleteUser_hook');
		//Listen to post write hook
		\OCP\Util::connectHook('OC_Filesystem', 'post_write', 'OCA\Files_Trashbin\Hooks', 'post_write_hook');
		// pre and post-rename, disable trash logic for the copy+unlink case
		\OCP\Util::connectHook('OC_Filesystem', 'rename', 'OCA\Files_Trashbin\Storage', 'preRenameHook');
		\OCP\Util::connectHook('OC_Filesystem', 'post_rename', 'OCA\Files_Trashbin\Storage', 'postRenameHook');
	}

	/**
	 * check if trash bin is empty for a given user
	 * @param string $user
	 * @return bool
	 */
	public static function isEmpty($user) {

		$view = new \OC\Files\View('/' . $user . '/files_trashbin');
		if ($view->is_dir('/files') && $dh = $view->opendir('/files')) {
			while ($file = readdir($dh)) {
				if ($file !== '.' and $file !== '..') {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * @param $path
	 * @return string
	 */
	public static function preview_icon($path) {
		return \OC_Helper::linkToRoute('core_ajax_trashbin_preview', array('x' => 36, 'y' => 36, 'file' => $path));
	}
}
