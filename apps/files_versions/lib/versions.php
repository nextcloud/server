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

namespace OCA_Versions;

class Storage {

	const DEFAULTENABLED=true;
	const DEFAULTMAXSIZE=50; // unit: percentage; 50% of available disk space/quota
	
	private static $max_versions_per_interval = array(
														1 => array('intervalEndsAfter' => 10,     //first 10sec, one version every 2sec
																	'step' => 2),
														2 => array('intervalEndsAfter' => 60,     //next minute, one version every 10sec
																	'step' => 10),
														3 => array('intervalEndsAfter' => 3600,     //next hour, one version every minute
																	'step' => 60),
														4 => array('intervalEndsAfter' => 86400,   //next 24h, one version every hour
																	'step' => 3600),
														5 => array('intervalEndsAfter' => 2592000,   //next 30days, one version per day
																	'step' => 86400),
														6 => array('intervalEndsAfter' => -1,      //until the end one version per week
																	'step' => 604800),
			);	

	private static function getUidAndFilename($filename)
	{
		if (\OCP\App::isEnabled('files_sharing')
		    && substr($filename, 0, 7) == '/Shared'
		    && $source = \OCP\Share::getItemSharedWith('file',
					substr($filename, 7),
					\OC_Share_Backend_File::FORMAT_SHARED_STORAGE)) {
			$filename = $source['path'];
			$pos = strpos($filename, '/files', 1);
			$uid = substr($filename, 1, $pos - 1);
			$filename = substr($filename, $pos + 6);
		} else {
			$uid = \OCP\User::getUser();
		}
		return array($uid, $filename);
	}

	/**
	 * store a new version of a file.
	 */
	public function store($filename) {
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			list($uid, $filename) = self::getUidAndFilename($filename);
			$files_view = new \OC\Files\View('/'.\OCP\User::getUser() .'/files');
			$users_view = new \OC\Files\View('/'.\OCP\User::getUser());

			//check if source file already exist as version to avoid recursions.
			// todo does this check work?
			if ($users_view->file_exists($filename)) {
				return false;
			}

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
			$versionsFolderName=\OCP\Config::getSystemValue('datadirectory').$users_view->getAbsolutePath('files_versions/');
			if(!file_exists($versionsFolderName.'/'.$info['dirname'])) {
				mkdir($versionsFolderName.'/'.$info['dirname'], 0750, true);
			}

			// store a new version of a file
			$result = $users_view->copy('files'.$filename, 'files_versions'.$filename.'.v'.$users_view->filemtime('files'.$filename));
			if (  ($versionsSize = \OCP\Config::getAppValue('files_versions', 'size')) === null ) {
				$versionsSize = self::calculateSize($uid);
			}
			$versionsSize += $users_view->filesize('files'.$filename);
			
			// expire old revisions if necessary
			$newSize = self::expire($filename, $versionsSize);
			
			if ( $newSize != $versionsSize )  {
				\OCP\Config::setAppValue('files_versions', 'size', $versionsSize);
			}
		}
	}


	/**
	 * Delete versions of a file
	 */
	public static function delete($filename) {
		list($uid, $filename) = self::getUidAndFilename($filename);
		$versions_fileview = new \OC_FilesystemView('/'.$uid .'/files_versions');
		
		$abs_path = \OCP\Config::getSystemValue('datadirectory').$versions_fileview->getAbsolutePath('').$filename.'.v';
		if( ($versions = self::getVersions($filename)) ) {
			if (  ($versionsSize = \OCP\Config::getAppValue('files_versions', 'size')) === null ) {
				$versionsSize = self::calculateSize($uid);
			}
			foreach ($versions as $v) {
				unlink($abs_path . $v['version']);
				$versionsSize -= $v['size'];
			}
			\OCP\Config::setAppValue('files_versions', 'size', $versionsSize);
		}
	}
	
	/**
	 * rename versions of a file
	 */
	public static function rename($oldpath, $newpath) {
		list($uid, $oldpath) = self::getUidAndFilename($oldpath);
		list($uidn, $newpath) = self::getUidAndFilename($newpath);
		$versions_view = new \OC_FilesystemView('/'.$uid .'/files_versions');
		$files_view = new \OC_FilesystemView('/'.$uid .'/files');
		$abs_newpath = \OCP\Config::getSystemValue('datadirectory').$versions_view->getAbsolutePath('').$newpath;
		
		if ( $files_view->is_dir($oldpath) && $versions_view->is_dir($oldpath) ) {
			$versions_view->rename($oldpath, $newpath);
		} else 	if ( ($versions = Storage::getVersions($oldpath)) ) {
			$info=pathinfo($abs_newpath);
			if(!file_exists($info['dirname'])) mkdir($info['dirname'], 0750, true);
			$versions = Storage::getVersions($oldpath);
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
	 * @param $filename file to find versions of, relative to the user files dir
	 * @param $count number of versions to return
	 * @returns array
	 */
	public static function getVersions( $filename, $count = 0 ) {
		if( \OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true' ) {
			list($uid, $filename) = self::getUidAndFilename($filename);
			$versions_fileview = new \OC\Files\View('/' . \OCP\User::getUser() . '/files_versions');

			$versionsName = \OCP\Config::getSystemValue('datadirectory').$versions_fileview->getAbsolutePath($filename);
			$versions = array();
			// fetch for old versions
			$matches = glob( $versionsName.'.v*' );

			sort( $matches );

			$files_view = new \OC_FilesystemView('/'.$uid.'/files');
			$local_file = $files_view->getLocalFile($filename);

			foreach( $matches as $ma ) {
				$parts = explode( '.v', $ma );
				$version = ( end( $parts ) );
				$key = $version.'#'.$filename;
				$versions[$key]['cur'] = 0;
				$versions[$key]['version'] = $version;
				$versions[$key]['path'] = $filename;
				$versions[$key]['size'] = $versions_fileview->filesize($filename.'.v'.$version);

				// if file with modified date exists, flag it in array as currently enabled version
				( \md5_file( $ma ) == \md5_file( $local_file ) ? $versions[$key]['fileMatch'] = 1 : $versions[$key]['fileMatch'] = 0 );

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
	 * @brief get the size of all stored versions from a given user
	 * @param $uid id from the user
	 * @return size of vesions
	 */
	private static function calculateSize($uid) {
		if( \OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true' ) {
			$versions_fileview = new \OC_FilesystemView('/'.$uid.'/files_versions');
			$versionsRoot = \OCP\Config::getSystemValue('datadirectory').$versions_fileview->getAbsolutePath('');
				
			$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($versionsRoot), \RecursiveIteratorIterator::CHILD_FIRST);
				
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
			$versions_fileview = new \OC_FilesystemView('/'.$uid.'/files_versions');
			$versionsRoot = \OCP\Config::getSystemValue('datadirectory').$versions_fileview->getAbsolutePath('');
			
			$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($versionsRoot), \RecursiveIteratorIterator::CHILD_FIRST);
			
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
			$versions_fileview = new \OC_FilesystemView('/'.$uid.'/files_versions');
			
			// get available disk space for user
			$quota = \OCP\Util::computerFileSize(\OC_Preferences::getValue($uid, 'files', 'quota'));
			if ( $quota == null ) {
				$quota = \OCP\Util::computerFileSize(\OC_Appconfig::getValue('files', 'default_quota'));
			}
			if ( $quota == null ) {
				$quota = \OC\Files\Filesystem::free_space('/');
			}
			
			// make sure that we have the current size of the version history
			if ( $versionsSize === null ) {
				if (  ($versionsSize = \OCP\Config::getAppValue('files_versions', 'size')) === null ) {
					$versionsSize = self::calculateSize($uid);
				}
			}

			// calculate available space for version history
			$rootInfo = \OC_FileCache::get('', '/'. $uid . '/files');
			$free = $quota-$rootInfo['size']; // remaining free space for user
			if ( $free > 0 ) {
				$availableSpace = ($free * self::DEFAULTMAXSIZE / 100) - $versionsSize; // how much space can be used for versions
			} else {
				$availableSpace = $free-$versionsSize;
			} 

			// after every 1000s run reduce the number of all versions not only for the current file 
			$random = rand(0, 1000);
			if ($random == 0) {
				$result = Storage::getAllVersions($uid);
				$versions_by_file = $result['by_file'];
				$all_versions = $result['all'];
			} else {
				$all_versions = Storage::getVersions($filename);
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
			
			// check if enough space is available after versions are rearranged.
			// if not we delete the oldest versions until we meet the size limit for versions
			$numOfVersions = count($all_versions);
			$i = 0; 
			while ($availableSpace < 0) {
				if ($i = $numOfVersions-2) break; // keep at least the last version
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
