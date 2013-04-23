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
	 * get current size of all versions from a given user
	 * 
	 * @param $user user who owns the versions
	 * @return mixed versions size or false if no versions size is stored
	 */
	private static function getVersionsSize($user) {
		$query = \OC_DB::prepare('SELECT `size` FROM `*PREFIX*files_versions` WHERE `user`=?');
		$result = $query->execute(array($user))->fetchAll();
		
		if ($result) {
			return $result[0]['size'];
		}
		return false;
	}

	/**
	 * write to the database how much space is in use for versions
	 * 
	 * @param $user owner of the versions
	 * @param $size size of the versions
	 */
	private static function setVersionsSize($user, $size) {
		if ( self::getVersionsSize($user) === false) {
			$query = \OC_DB::prepare('INSERT INTO `*PREFIX*files_versions` (`size`, `user`) VALUES (?, ?)');
		}else {
			$query = \OC_DB::prepare('UPDATE `*PREFIX*files_versions` SET `size`=? WHERE `user`=?');
		}
		$query->execute(array($size, $user));
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
			$versions_view = new \OC\Files\View('/'.$uid.'/files_versions');

			// check if filename is a directory
			if($files_view->is_dir($filename)) {
				return false;
			}

			// we should have a source file to work with
			if (!$files_view->file_exists($filename)) {
				return false;
			}

			// create all parent folders
			$info=pathinfo($filename);
			$versionsFolderName=$versions_view->getLocalFolder('');
			if(!file_exists($versionsFolderName.'/'.$info['dirname'])) {
				mkdir($versionsFolderName.'/'.$info['dirname'], 0750, true);
			}

			// store a new version of a file
			$users_view->copy('files'.$filename, 'files_versions'.$filename.'.v'.$users_view->filemtime('files'.$filename));
			$versionsSize = self::getVersionsSize($uid);
			if (  $versionsSize === false || $versionsSize < 0 ) {
				$versionsSize = self::calculateSize($uid);
			}

			$versionsSize += $users_view->filesize('files'.$filename);

			// expire old revisions if necessary
			$newSize = self::expire($filename, $versionsSize);

			if ( $newSize != $versionsSize ) {
				self::setVersionsSize($uid, $newSize);
			}
		}
	}


	/**
	 * Delete versions of a file
	 */
	public static function delete($filename) {
		list($uid, $filename) = self::getUidAndFilename($filename);
		$versions_fileview = new \OC\Files\View('/'.$uid .'/files_versions');

		$abs_path = $versions_fileview->getLocalFile($filename.'.v');
		if( ($versions = self::getVersions($uid, $filename)) ) {
			$versionsSize = self::getVersionsSize($uid);
			if ( $versionsSize === false || $versionsSize < 0 ) {
				$versionsSize = self::calculateSize($uid);
			}
			foreach ($versions as $v) {
				unlink($abs_path . $v['version']);
				$versionsSize -= $v['size'];
			}
			self::setVersionsSize($uid, $versionsSize);
		}
	}

	/**
	 * rename versions of a file
	 */
	public static function rename($old_path, $new_path) {
		list($uid, $oldpath) = self::getUidAndFilename($old_path);
		list($uidn, $newpath) = self::getUidAndFilename($new_path);
		$versions_view = new \OC\Files\View('/'.$uid .'/files_versions');
		$files_view = new \OC\Files\View('/'.$uid .'/files');
		
		// if the file already exists than it was a upload of a existing file
		// over the web interface -> store() is the right function we need here
		if ($files_view->file_exists($newpath)) {
			return self::store($new_path);
		}
		
		$abs_newpath = $versions_view->getLocalFile($newpath);

		if ( $files_view->is_dir($oldpath) && $versions_view->is_dir($oldpath) ) {
			$versions_view->rename($oldpath, $newpath);
		} else 	if ( ($versions = Storage::getVersions($uid, $oldpath)) ) {
			$info=pathinfo($abs_newpath);
			if(!file_exists($info['dirname'])) mkdir($info['dirname'], 0750, true);
			foreach ($versions as $v) {
				$versions_view->rename($oldpath.'.v'.$v['version'], $newpath.'.v'.$v['version']);
			}
		}
	}

	/**
	 * rollback to an old version of a file.
	 */
	public static function rollback($filename, $revision) {

		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			list($uid, $filename) = self::getUidAndFilename($filename);
			$users_view = new \OC\Files\View('/'.$uid);
			$versionCreated = false;

			//first create a new version
			$version = 'files_versions'.$filename.'.v'.$users_view->filemtime('files'.$filename);
			if ( !$users_view->file_exists($version)) {
				$users_view->copy('files'.$filename, 'files_versions'.$filename.'.v'.$users_view->filemtime('files'.$filename));
				$versionCreated = true;
			}

			// rollback
			if( @$users_view->copy('files_versions'.$filename.'.v'.$revision, 'files'.$filename) ) {
				$users_view->touch('files'.$filename, $revision);
				Storage::expire($filename);
				return true;

			}else if ( $versionCreated ) {
				$users_view->unlink($version);
			}
		}
		return false;

	}


	/**
	 * @brief get a list of all available versions of a file in descending chronological order
	 * @param $uid user id from the owner of the file
	 * @param $filename file to find versions of, relative to the user files dir
	 * @param $count number of versions to return
	 * @returns array
	 */
	public static function getVersions($uid, $filename, $count = 0 ) {
		if( \OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true' ) {
			$versions_fileview = new \OC\Files\View('/' . $uid . '/files_versions');
			$versionsName = $versions_fileview->getLocalFile($filename);
			
			$versions = array();
			// fetch for old versions
			$matches = glob(preg_quote($versionsName).'.v*' );

			if ( !$matches ) {
				return $versions;
			}

			sort( $matches );

			$files_view = new \OC\Files\View('/'.$uid.'/files');
			$local_file = $files_view->getLocalFile($filename);
			$local_file_md5 = \md5_file( $local_file );

			foreach( $matches as $ma ) {
				$parts = explode( '.v', $ma );
				$version = ( end( $parts ) );
				$key = $version.'#'.$filename;
				$versions[$key]['cur'] = 0;
				$versions[$key]['version'] = $version;
				$versions[$key]['path'] = $filename;
				$versions[$key]['size'] = $versions_fileview->filesize($filename.'.v'.$version);

				// if file with modified date exists, flag it in array as currently enabled version
				( \md5_file( $ma ) == $local_file_md5 ? $versions[$key]['fileMatch'] = 1 : $versions[$key]['fileMatch'] = 0 );

			}

			$versions = array_reverse( $versions );

			foreach( $versions as $key => $value ) {
				// flag the first matched file in array (which will have latest modification date) as current version
				if ( $value['fileMatch'] ) {
					$value['cur'] = 1;
					break;
				}
			}

			$versions = array_reverse( $versions );

			// only show the newest commits
			if( $count != 0 and ( count( $versions )>$count ) ) {
				$versions = array_slice( $versions, count( $versions ) - $count );
			}

			return( $versions );

		} else {
			// if versioning isn't enabled then return an empty array
			return( array() );
		}

	}


	/**
	 * @brief deletes used space for files versions in db if user was deleted
	 *
	 * @param type $uid id of deleted user
	 * @return result of db delete operation
	 */
	public static function deleteUser($uid) {
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*files_versions` WHERE `user`=?');
		return $query->execute(array($uid));
	}

	/**
	 * @brief get the size of all stored versions from a given user
	 * @param $uid id from the user
	 * @return size of vesions
	 */
	private static function calculateSize($uid) {
		if( \OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true' ) {
			$versions_fileview = new \OC\Files\View('/'.$uid.'/files_versions');
			$versionsRoot = $versions_fileview->getLocalFolder('');

			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($versionsRoot),
				\RecursiveIteratorIterator::CHILD_FIRST
			);

			$size = 0;

			foreach ($iterator as $path) {
				if ( preg_match('/^.+\.v(\d+)$/', $path, $match) ) {
					$relpath = substr($path, strlen($versionsRoot)-1);
					$size += $versions_fileview->filesize($relpath);
				}
			}

			return $size;
		}
	}

	/**
	 * @brief returns all stored file versions from a given user
	 * @param $uid id to the user
	 * @return array with contains two arrays 'all' which contains all versions sorted by age and 'by_file' which contains all versions sorted by filename
	 */
	private static function getAllVersions($uid) {
		if( \OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true' ) {
			$versions_fileview = new \OC\Files\View('/'.$uid.'/files_versions');
			$versionsRoot = $versions_fileview->getLocalFolder('');

			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($versionsRoot),
				\RecursiveIteratorIterator::CHILD_FIRST
			);

			$versions = array();

			foreach ($iterator as $path) {
				if ( preg_match('/^.+\.v(\d+)$/', $path, $match) ) {
					$relpath = substr($path, strlen($versionsRoot)-1);
					$versions[$match[1].'#'.$relpath] = array('path' => $relpath, 'timestamp' => $match[1]);
				}
			}

			ksort($versions);

			$i = 0;

			$result = array();

			foreach( $versions as $key => $value ) {
				$i++;
				$size = $versions_fileview->filesize($value['path']);
				$filename = substr($value['path'], 0, -strlen($value['timestamp'])-2);

				$result['all'][$key]['version'] = $value['timestamp'];
				$result['all'][$key]['path'] = $filename;
				$result['all'][$key]['size'] = $size;

				$filename = substr($value['path'], 0, -strlen($value['timestamp'])-2);
				$result['by_file'][$filename][$key]['version'] = $value['timestamp'];
				$result['by_file'][$filename][$key]['path'] = $filename;
				$result['by_file'][$filename][$key]['size'] = $size;

			}

			return $result;
		}
	}

	/**
	 * @brief Erase a file's versions which exceed the set quota
	 */
	private static function expire($filename, $versionsSize = null) {
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			list($uid, $filename) = self::getUidAndFilename($filename);
			$versions_fileview = new \OC\Files\View('/'.$uid.'/files_versions');

			// get available disk space for user
			$softQuota = true;
			$quota = \OC_Preferences::getValue($uid, 'files', 'quota');
			if ( $quota === null || $quota === 'default') {
				$quota = \OC_Appconfig::getValue('files', 'default_quota');
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
				if (  $versionsSize === false || $versionsSize < 0 ) {
					$versionsSize = self::calculateSize($uid);
				}
			}

			// calculate available space for version history
			// subtract size of files and current versions size from quota
			if ($softQuota) {
				$files_view = new \OC\Files\View('/'.$uid.'/files');
				$rootInfo = $files_view->getFileInfo('/');
				$free = $quota-$rootInfo['size']; // remaining free space for user
				if ( $free > 0 ) {
					$availableSpace = ($free * self::DEFAULTMAXSIZE / 100) - $versionsSize; // how much space can be used for versions
				} else {
					$availableSpace = $free-$versionsSize;
				}
			} else {
				$availableSpace = $quota;
			}


			// after every 1000s run reduce the number of all versions not only for the current file
			$random = rand(0, 1000);
			if ($random == 0) {
				$result = Storage::getAllVersions($uid);
				$versions_by_file = $result['by_file'];
				$all_versions = $result['all'];
			} else {
				$all_versions = Storage::getVersions($uid, $filename);
				$versions_by_file[$filename] = $all_versions;
			}

			$time = time();

			// it is possible to expire versions from more than one file
			// iterate through all given files
			foreach ($versions_by_file as $filename => $versions) {
				$versions = array_reverse($versions);	// newest version first

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
				$remaining_versions[$firstKey] = $firstVersion;
				unset($versions[$firstKey]);

				foreach ($versions as $key => $version) {
					$newInterval = true;
					while ( $newInterval ) {
						if ( $nextInterval == -1 || $version['version'] >= $nextInterval ) {
							if ( $version['version'] > $nextVersion ) {
								//distance between two version too small, delete version
								$versions_fileview->unlink($version['path'].'.v'.$version['version']);
								$availableSpace += $version['size'];
								$versionsSize -= $version['size'];
								unset($all_versions[$key]); // update array with all versions
							} else {
								$nextVersion = $version['version'] - $step;
							}
							$newInterval = false; // version checked so we can move to the next one
						} else { // time to move on to the next interval
							$interval++;
							$step = Storage::$max_versions_per_interval[$interval]['step'];
							$nextVersion = $prevTimestamp - $step;
							if ( Storage::$max_versions_per_interval[$interval]['intervalEndsAfter'] == -1 ) {
								$nextInterval = -1;
							} else {
								$nextInterval = $time - Storage::$max_versions_per_interval[$interval]['intervalEndsAfter'];
							}
							$newInterval = true; // we changed the interval -> check same version with new interval
						}
					}
					$prevTimestamp = $version['version'];
				}
			}

			// Check if enough space is available after versions are rearranged.
			// If not we delete the oldest versions until we meet the size limit for versions,
			// but always keep the two latest versions
			$numOfVersions = count($all_versions) -2 ;
			$i = 0;
			while ($availableSpace < 0 && $i < $numOfVersions) {
				$versions_fileview->unlink($all_versions[$i]['path'].'.v'.$all_versions[$i]['version']);
				$versionsSize -= $all_versions[$i]['size'];
				$availableSpace += $all_versions[$i]['size'];
				$i++;
			}

			return $versionsSize; // finally return the new size of the version history
		}

		return false;
	}
}
