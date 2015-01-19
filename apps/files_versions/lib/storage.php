<?php
/**
 * Copyright (c) 2012 Frank Karlitschek <frank@owncloud.org>
 *               2013 Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * Versions
 *
 * A class to handle the versioning of files.
 */

namespace OCA\Files_Versions;

class Storage {

	const DEFAULTENABLED=true;
	const DEFAULTMAXSIZE=50; // unit: percentage; 50% of available disk space/quota
	const VERSIONS_ROOT = 'files_versions/';

	// files for which we can remove the versions after the delete operation was successful
	private static $deletedFiles = array();

	private static $sourcePathAndUser = array();

	private static $max_versions_per_interval = array(
		//first 10sec, one version every 2sec
		1 => array('intervalEndsAfter' => 10,      'step' => 2),
		//next minute, one version every 10sec
		2 => array('intervalEndsAfter' => 60,      'step' => 10),
		//next hour, one version every minute
		3 => array('intervalEndsAfter' => 3600,    'step' => 60),
		//next 24h, one version every hour
		4 => array('intervalEndsAfter' => 86400,   'step' => 3600),
		//next 30days, one version per day
		5 => array('intervalEndsAfter' => 2592000, 'step' => 86400),
		//until the end one version per week
		6 => array('intervalEndsAfter' => -1,      'step' => 604800),
	);

	public static function getUidAndFilename($filename) {
		$uid = \OC\Files\Filesystem::getOwner($filename);
		\OC\Files\Filesystem::initMountPoints($uid);
		if ( $uid != \OCP\User::getUser() ) {
			$info = \OC\Files\Filesystem::getFileInfo($filename);
			$ownerView = new \OC\Files\View('/'.$uid.'/files');
			$filename = $ownerView->getPath($info['fileid']);
		}
		return array($uid, $filename);
	}

	/**
	 * Remember the owner and the owner path of the source file
	 *
	 * @param string $source source path
	 */
	public static function setSourcePathAndUser($source) {
		list($uid, $path) = self::getUidAndFilename($source);
		self::$sourcePathAndUser[$source] = array('uid' => $uid, 'path' => $path);
	}

	/**
	 * Gets the owner and the owner path from the source path
	 *
	 * @param string $source source path
	 * @return array with user id and path
	 */
	public static function getSourcePathAndUser($source) {

		if (isset(self::$sourcePathAndUser[$source])) {
			$uid = self::$sourcePathAndUser[$source]['uid'];
			$path = self::$sourcePathAndUser[$source]['path'];
			unset(self::$sourcePathAndUser[$source]);
		} else {
			$uid = $path = false;
		}
		return array($uid, $path);
	}

	/**
	 * get current size of all versions from a given user
	 *
	 * @param string $user user who owns the versions
	 * @return int versions size
	 */
	private static function getVersionsSize($user) {
		$view = new \OC\Files\View('/' . $user);
		$fileInfo = $view->getFileInfo('/files_versions');
		return isset($fileInfo['size']) ? $fileInfo['size'] : 0;
	}

	/**
	 * store a new version of a file.
	 */
	public static function store($filename) {
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {

			// if the file gets streamed we need to remove the .part extension
			// to get the right target
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			if ($ext === 'part') {
				$filename = substr($filename, 0, strlen($filename)-5);
			}

			list($uid, $filename) = self::getUidAndFilename($filename);

			$files_view = new \OC\Files\View('/'.$uid .'/files');
			$users_view = new \OC\Files\View('/'.$uid);

			// check if filename is a directory
			if($files_view->is_dir($filename)) {
				return false;
			}

			// we should have a source file to work with, and the file shouldn't
			// be empty
			$fileExists = $files_view->file_exists($filename);
			if (!($fileExists && $files_view->filesize($filename) > 0)) {
				return false;
			}

			// create all parent folders
			self::createMissingDirectories($filename, $users_view);

			$versionsSize = self::getVersionsSize($uid);

			// assumption: we need filesize($filename) for the new version +
			// some more free space for the modified file which might be
			// 1.5 times as large as the current version -> 2.5
			$neededSpace = $files_view->filesize($filename) * 2.5;

			self::expire($filename, $versionsSize, $neededSpace);

			// disable proxy to prevent multiple fopen calls
			$proxyStatus = \OC_FileProxy::$enabled;
			\OC_FileProxy::$enabled = false;

			// store a new version of a file
			$mtime = $users_view->filemtime('files/' . $filename);
			$users_view->copy('files/' . $filename, 'files_versions/' . $filename . '.v' . $mtime);
			// call getFileInfo to enforce a file cache entry for the new version
			$users_view->getFileInfo('files_versions/' . $filename . '.v' . $mtime);

			// reset proxy state
			\OC_FileProxy::$enabled = $proxyStatus;
		}
	}


	/**
	 * mark file as deleted so that we can remove the versions if the file is gone
	 * @param string $path
	 */
	public static function markDeletedFile($path) {
		list($uid, $filename) = self::getUidAndFilename($path);
		self::$deletedFiles[$path] = array(
			'uid' => $uid,
			'filename' => $filename);
	}

	/**
	 * delete the version from the storage and cache
	 *
	 * @param \OC\Files\View $view
	 * @param string $path
	 */
	protected static function deleteVersion($view, $path) {
		$view->unlink($path);
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 * @var string $internalPath
		 */
		list($storage, $internalPath) = $view->resolvePath($path);
		$cache = $storage->getCache($internalPath);
		$cache->remove($internalPath);
	}

	/**
	 * Delete versions of a file
	 */
	public static function delete($path) {

		$deletedFile = self::$deletedFiles[$path];
		$uid = $deletedFile['uid'];
		$filename = $deletedFile['filename'];

		if (!\OC\Files\Filesystem::file_exists($path)) {

			$view = new \OC\Files\View('/' . $uid . '/files_versions');

			$versions = self::getVersions($uid, $filename);
			if (!empty($versions)) {
				foreach ($versions as $v) {
					\OC_Hook::emit('\OCP\Versions', 'preDelete', array('path' => $path . $v['version']));
					self::deleteVersion($view, $filename . '.v' . $v['version']);
					\OC_Hook::emit('\OCP\Versions', 'delete', array('path' => $path . $v['version']));
				}
			}
		}
		unset(self::$deletedFiles[$path]);
	}

	/**
	 * rename or copy versions of a file
	 * @param string $old_path
	 * @param string $new_path
	 * @param string $operation can be 'copy' or 'rename'
	 */
	public static function renameOrCopy($old_path, $new_path, $operation) {
		list($uid, $oldpath) = self::getSourcePathAndUser($old_path);

		// it was a upload of a existing file if no old path exists
		// in this case the pre-hook already called the store method and we can
		// stop here
		if ($oldpath === false) {
			return true;
		}

		list($uidn, $newpath) = self::getUidAndFilename($new_path);
		$versions_view = new \OC\Files\View('/'.$uid .'/files_versions');
		$files_view = new \OC\Files\View('/'.$uid .'/files');



		if ( $files_view->is_dir($oldpath) && $versions_view->is_dir($oldpath) ) {
			$versions_view->$operation($oldpath, $newpath);
		} else  if ( ($versions = Storage::getVersions($uid, $oldpath)) ) {
			// create missing dirs if necessary
			self::createMissingDirectories($newpath, new \OC\Files\View('/'. $uidn));

			foreach ($versions as $v) {
				$versions_view->$operation($oldpath.'.v'.$v['version'], $newpath.'.v'.$v['version']);
			}
		}

		if (!$files_view->is_dir($newpath)) {
			self::expire($newpath);
		}

	}

	/**
	 * rollback to an old version of a file.
	 */
	public static function rollback($file, $revision) {

		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			list($uid, $filename) = self::getUidAndFilename($file);
			$users_view = new \OC\Files\View('/'.$uid);
			$files_view = new \OC\Files\View('/'.\OCP\User::getUser().'/files');
			$versionCreated = false;

			//first create a new version
			$version = 'files_versions'.$filename.'.v'.$users_view->filemtime('files'.$filename);
			if ( !$users_view->file_exists($version)) {

				// disable proxy to prevent multiple fopen calls
				$proxyStatus = \OC_FileProxy::$enabled;
				\OC_FileProxy::$enabled = false;

				$users_view->copy('files'.$filename, 'files_versions'.$filename.'.v'.$users_view->filemtime('files'.$filename));

				// reset proxy state
				\OC_FileProxy::$enabled = $proxyStatus;

				$versionCreated = true;
			}

			// rollback
			if( @$users_view->rename('files_versions'.$filename.'.v'.$revision, 'files'.$filename) ) {
				$files_view->touch($file, $revision);
				Storage::expire($file);
				return true;

			}else if ( $versionCreated ) {
				self::deleteVersion($users_view, $version);
			}
		}
		return false;

	}


	/**
	 * get a list of all available versions of a file in descending chronological order
	 * @param string $uid user id from the owner of the file
	 * @param string $filename file to find versions of, relative to the user files dir
	 * @param string $userFullPath
	 * @return array versions newest version first
	 */
	public static function getVersions($uid, $filename, $userFullPath = '') {
		$versions = array();
		// fetch for old versions
		$view = new \OC\Files\View('/' . $uid . '/');

		$pathinfo = pathinfo($filename);
		$versionedFile = $pathinfo['basename'];

		$dir = \OC\Files\Filesystem::normalizePath(self::VERSIONS_ROOT . '/' . $pathinfo['dirname']);

		$dirContent = false;
		if ($view->is_dir($dir)) {
			$dirContent = $view->opendir($dir);
		}

		if ($dirContent === false) {
			return $versions;
		}

		if (is_resource($dirContent)) {
			while (($entryName = readdir($dirContent)) !== false) {
				if (!\OC\Files\Filesystem::isIgnoredDir($entryName)) {
					$pathparts = pathinfo($entryName);
					$filename = $pathparts['filename'];
					if ($filename === $versionedFile) {
						$pathparts = pathinfo($entryName);
						$timestamp = substr($pathparts['extension'], 1);
						$filename = $pathparts['filename'];
						$key = $timestamp . '#' . $filename;
						$versions[$key]['version'] = $timestamp;
						$versions[$key]['humanReadableTimestamp'] = self::getHumanReadableTimestamp($timestamp);
						if (empty($userFullPath)) {
							$versions[$key]['preview'] = '';
						} else {
							$versions[$key]['preview'] = \OCP\Util::linkToRoute('core_ajax_versions_preview', array('file' => $userFullPath, 'version' => $timestamp));
						}
						$versions[$key]['path'] = \OC\Files\Filesystem::normalizePath($pathinfo['dirname'] . '/' . $filename);
						$versions[$key]['name'] = $versionedFile;
						$versions[$key]['size'] = $view->filesize($dir . '/' . $entryName);
					}
				}
			}
			closedir($dirContent);
		}

		// sort with newest version first
		krsort($versions);

		return $versions;
	}

	/**
	 * translate a timestamp into a string like "5 days ago"
	 * @param int $timestamp
	 * @return string for example "5 days ago"
	 */
	private static function getHumanReadableTimestamp($timestamp) {

		$diff = time() - $timestamp;

		if ($diff < 60) { // first minute
			return  $diff . " seconds ago";
		} elseif ($diff < 3600) { //first hour
			return round($diff / 60) . " minutes ago";
		} elseif ($diff < 86400) { // first day
			return round($diff / 3600) . " hours ago";
		} elseif ($diff < 604800) { //first week
			return round($diff / 86400) . " days ago";
		} elseif ($diff < 2419200) { //first month
			return round($diff / 604800) . " weeks ago";
		} elseif ($diff < 29030400) { // first year
			return round($diff / 2419200) . " months ago";
		} else {
			return round($diff / 29030400) . " years ago";
		}

	}

	/**
	 * returns all stored file versions from a given user
	 * @param string $uid id of the user
	 * @return array with contains two arrays 'all' which contains all versions sorted by age and 'by_file' which contains all versions sorted by filename
	 */
	private static function getAllVersions($uid) {
		$view = new \OC\Files\View('/' . $uid . '/');
		$dirs = array(self::VERSIONS_ROOT);
		$versions = array();

		while (!empty($dirs)) {
			$dir = array_pop($dirs);
			$files = $view->getDirectoryContent($dir);

			foreach ($files as $file) {
				if ($file['type'] === 'dir') {
					array_push($dirs, $file['path']);
				} else {
					$versionsBegin = strrpos($file['path'], '.v');
					$relPathStart = strlen(self::VERSIONS_ROOT);
					$version = substr($file['path'], $versionsBegin + 2);
					$relpath = substr($file['path'], $relPathStart, $versionsBegin - $relPathStart);
					$key = $version . '#' . $relpath;
					$versions[$key] = array('path' => $relpath, 'timestamp' => $version);
				}
			}
		}

		// newest version first
		krsort($versions);

		$result = array();

		foreach ($versions as $key => $value) {
			$size = $view->filesize(self::VERSIONS_ROOT.'/'.$value['path'].'.v'.$value['timestamp']);
			$filename = $value['path'];

			$result['all'][$key]['version'] = $value['timestamp'];
			$result['all'][$key]['path'] = $filename;
			$result['all'][$key]['size'] = $size;

			$result['by_file'][$filename][$key]['version'] = $value['timestamp'];
			$result['by_file'][$filename][$key]['path'] = $filename;
			$result['by_file'][$filename][$key]['size'] = $size;
		}

		return $result;
	}

	/**
	 * get list of files we want to expire
	 * @param array $versions list of versions
	 * @param integer $time
	 * @return array containing the list of to deleted versions and the size of them
	 */
	protected static function getExpireList($time, $versions) {

		$size = 0;
		$toDelete = array();  // versions we want to delete

		$interval = 1;
		$step = Storage::$max_versions_per_interval[$interval]['step'];
		if (Storage::$max_versions_per_interval[$interval]['intervalEndsAfter'] == -1) {
			$nextInterval = -1;
		} else {
			$nextInterval = $time - Storage::$max_versions_per_interval[$interval]['intervalEndsAfter'];
		}

		$firstVersion = reset($versions);
		$firstKey = key($versions);
		$prevTimestamp = $firstVersion['version'];
		$nextVersion = $firstVersion['version'] - $step;
		unset($versions[$firstKey]);

		foreach ($versions as $key => $version) {
			$newInterval = true;
			while ($newInterval) {
				if ($nextInterval == -1 || $prevTimestamp > $nextInterval) {
					if ($version['version'] > $nextVersion) {
						//distance between two version too small, mark to delete
						$toDelete[$key] = $version['path'] . '.v' . $version['version'];
						$size += $version['size'];
						\OCP\Util::writeLog('files_versions', 'Mark to expire '. $version['path'] .' next version should be ' . $nextVersion . " or smaller. (prevTimestamp: " . $prevTimestamp . "; step: " . $step, \OCP\Util::DEBUG);
					} else {
						$nextVersion = $version['version'] - $step;
						$prevTimestamp = $version['version'];
					}
					$newInterval = false; // version checked so we can move to the next one
				} else { // time to move on to the next interval
					$interval++;
					$step = Storage::$max_versions_per_interval[$interval]['step'];
					$nextVersion = $prevTimestamp - $step;
					if (Storage::$max_versions_per_interval[$interval]['intervalEndsAfter'] == -1) {
						$nextInterval = -1;
					} else {
						$nextInterval = $time - Storage::$max_versions_per_interval[$interval]['intervalEndsAfter'];
					}
					$newInterval = true; // we changed the interval -> check same version with new interval
				}
			}
		}

		return array($toDelete, $size);

	}

	/**
	 * Erase a file's versions which exceed the set quota
	 */
	private static function expire($filename, $versionsSize = null, $offset = 0) {
		$config = \OC::$server->getConfig();
		if($config->getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			list($uid, $filename) = self::getUidAndFilename($filename);
			$versionsFileview = new \OC\Files\View('/'.$uid.'/files_versions');

			// get available disk space for user
			$softQuota = true;
			$quota = $config->getUserValue($uid, 'files', 'quota', null);
			if ( $quota === null || $quota === 'default') {
				$quota = $config->getAppValue('files', 'default_quota', null);
			}
			if ( $quota === null || $quota === 'none' ) {
				$quota = \OC\Files\Filesystem::free_space('/');
				$softQuota = false;
			} else {
				$quota = \OCP\Util::computerFileSize($quota);
			}

			// make sure that we have the current size of the version history
			if ( $versionsSize === null ) {
				$versionsSize = self::getVersionsSize($uid);
			}

			// calculate available space for version history
			// subtract size of files and current versions size from quota
			if ($softQuota) {
				$files_view = new \OC\Files\View('/'.$uid.'/files');
				$rootInfo = $files_view->getFileInfo('/', false);
				$free = $quota-$rootInfo['size']; // remaining free space for user
				if ( $free > 0 ) {
					$availableSpace = ($free * self::DEFAULTMAXSIZE / 100) - ($versionsSize + $offset); // how much space can be used for versions
				} else {
					$availableSpace = $free - $versionsSize - $offset;
				}
			} else {
				$availableSpace = $quota - $offset;
			}

			$allVersions = Storage::getVersions($uid, $filename);

			$time = time();
			list($toDelete, $sizeOfDeletedVersions) = self::getExpireList($time, $allVersions);

			$availableSpace = $availableSpace + $sizeOfDeletedVersions;
			$versionsSize = $versionsSize - $sizeOfDeletedVersions;

			// if still not enough free space we rearrange the versions from all files
			if ($availableSpace <= 0) {
				$result = Storage::getAllVersions($uid);
				$allVersions = $result['all'];

				foreach ($result['by_file'] as $versions) {
					list($toDeleteNew, $size) = self::getExpireList($time, $versions);
					$toDelete = array_merge($toDelete, $toDeleteNew);
					$sizeOfDeletedVersions += $size;
				}
				$availableSpace = $availableSpace + $sizeOfDeletedVersions;
				$versionsSize = $versionsSize - $sizeOfDeletedVersions;
			}

			foreach($toDelete as $key => $path) {
				\OC_Hook::emit('\OCP\Versions', 'preDelete', array('path' => $path));
				self::deleteVersion($versionsFileview, $path);
				\OC_Hook::emit('\OCP\Versions', 'delete', array('path' => $path));
				unset($allVersions[$key]); // update array with the versions we keep
				\OCP\Util::writeLog('files_versions', "Expire: " . $path, \OCP\Util::DEBUG);
			}

			// Check if enough space is available after versions are rearranged.
			// If not we delete the oldest versions until we meet the size limit for versions,
			// but always keep the two latest versions
			$numOfVersions = count($allVersions) -2 ;
			$i = 0;
			// sort oldest first and make sure that we start at the first element
			ksort($allVersions);
			reset($allVersions);
			while ($availableSpace < 0 && $i < $numOfVersions) {
				$version = current($allVersions);
				\OC_Hook::emit('\OCP\Versions', 'preDelete', array('path' => $version['path'].'.v'.$version['version']));
				self::deleteVersion($versionsFileview, $version['path'] . '.v' . $version['version']);
				\OC_Hook::emit('\OCP\Versions', 'delete', array('path' => $version['path'].'.v'.$version['version']));
				\OCP\Util::writeLog('files_versions', 'running out of space! Delete oldest version: ' . $version['path'].'.v'.$version['version'] , \OCP\Util::DEBUG);
				$versionsSize -= $version['size'];
				$availableSpace += $version['size'];
				next($allVersions);
				$i++;
			}

			return $versionsSize; // finally return the new size of the version history
		}

		return false;
	}

	/**
	 * create recursively missing directories
	 * @param string $filename $path to a file
	 * @param \OC\Files\View $view view on data/user/
	 */
	private static function createMissingDirectories($filename, $view) {
		$dirname = \OC\Files\Filesystem::normalizePath(dirname($filename));
		$dirParts = explode('/', $dirname);
		$dir = "/files_versions";
		foreach ($dirParts as $part) {
			$dir = $dir . '/' . $part;
			if (!$view->file_exists($dir)) {
				$view->mkdir($dir);
			}
		}
	}

}
