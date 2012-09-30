<?php
/**
 * Copyright (c) 2012 Frank Karlitschek <frank@owncloud.org>
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


	// config.php configuration:
	//   - files_versions
	//   - files_versionsfolder
	//   - files_versionsblacklist
	//   - files_versionsmaxfilesize
	//   - files_versionsinterval
	//   - files_versionmaxversions
	//
	// todo:
	//   - finish porting to OC_FilesystemView to enable network transparency
	//   - add transparent compression. first test if it´s worth it.

	const DEFAULTENABLED=true;
	const DEFAULTBLACKLIST='avi mp3 mpg mp4 ctmp';
	const DEFAULTMAXFILESIZE=1048576; // 10MB
	const DEFAULTMININTERVAL=60; // 1 min
	const DEFAULTMAXVERSIONS=50;

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
			$files_view = new \OC_FilesystemView('/'.$uid.'/files');
			$users_view = new \OC_FilesystemView('/'.$uid);

			//check if source file already exist as version to avoid recursions.
			// todo does this check work?
			if ($users_view->file_exists($filename)) {
				return false;
			}

			// check if filename is a directory
			if($files_view->is_dir($filename)) {
				return false;
			}

			// check filetype blacklist
			$blacklist=explode(' ',\OCP\Config::getSystemValue('files_versionsblacklist', Storage::DEFAULTBLACKLIST));
			foreach($blacklist as $bl) {
				$parts=explode('.', $filename);
				$ext=end($parts);
				if(strtolower($ext)==$bl) {
					return false;
				}
			}
			// we should have a source file to work with
			if (!$files_view->file_exists($filename)) {
				return false;
			}

			// check filesize
			if($files_view->filesize($filename)>\OCP\Config::getSystemValue('files_versionsmaxfilesize', Storage::DEFAULTMAXFILESIZE)) {
				return false;
			}


			// check mininterval if the file is being modified by the owner (all shared files should be versioned despite mininterval)
			if ($uid == \OCP\User::getUser()) {
				$versions_fileview = new \OC_FilesystemView('/'.$uid.'/files_versions');
				$versionsFolderName=\OCP\Config::getSystemValue('datadirectory'). $versions_fileview->getAbsolutePath('');
				$matches=glob($versionsFolderName.'/'.$filename.'.v*');
				sort($matches);
				$parts=explode('.v',end($matches));
				if((end($parts)+Storage::DEFAULTMININTERVAL)>time()) {
					return false;
				}
			}


			// create all parent folders
			$dirname = dirname($filename);
			if(!$users_view->file_exists('/files_versions/'.$dirname)) {
				$users_view->mkdir('/files_versions/'.$dirname,0700,true);
			}

			// store a new version of a file
			$users_view->copy('files'.$filename, 'files_versions'.$filename.'.v'.time());

			// expire old revisions if necessary
			Storage::expire($filename);
		}
	}


	/**
	 * rollback to an old version of a file.
	 */
	public static function rollback($filename,$revision) {

		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			list($uid, $filename) = self::getUidAndFilename($filename);
			$users_view = new \OC_FilesystemView('/'.$uid);

			// rollback
			if( @$users_view->copy('files_versions'.$filename.'.v'.$revision, 'files'.$filename) ) {

				return true;

			}else{

				return false;

			}

		}

	}

	/**
	 * check if old versions of a file exist.
	 */
	public static function isversioned($filename) {
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			list($uid, $filename) = self::getUidAndFilename($filename);
			$versions_fileview = new \OC_FilesystemView('/'.$uid.'/files_versions');

			$versionsFolderName=\OCP\Config::getSystemValue('datadirectory'). $versions_fileview->getAbsolutePath('');

			// check for old versions
			$matches=glob($versionsFolderName.$filename.'.v*');
			if(count($matches)>0) {
				return true;
			}else{
				return false;
			}
		}else{
			return(false);
		}
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
			$versions_fileview = new \OC_FilesystemView('/'.$uid.'/files_versions');

			$versionsFolderName = \OCP\Config::getSystemValue('datadirectory'). $versions_fileview->getAbsolutePath('');
			$versions = array();

			// fetch for old versions
			$matches = glob( $versionsFolderName.'/'.$filename.'.v*' );

			sort( $matches );

			$i = 0;

			$files_view = new \OC_FilesystemView('/'.$uid.'/files');
			$local_file = $files_view->getLocalFile($filename);
			foreach( $matches as $ma ) {

				$i++;
				$versions[$i]['cur'] = 0;
				$parts = explode( '.v', $ma );
				$versions[$i]['version'] = ( end( $parts ) );

				// if file with modified date exists, flag it in array as currently enabled version
				( \md5_file( $ma ) == \md5_file( $local_file ) ? $versions[$i]['fileMatch'] = 1 : $versions[$i]['fileMatch'] = 0 );

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
	 * @brief Erase a file's versions which exceed the set quota
	 */
	public static function expire($filename) {
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			list($uid, $filename) = self::getUidAndFilename($filename);
			$versions_fileview = new \OC_FilesystemView('/'.$uid.'/files_versions');

			$versionsFolderName=\OCP\Config::getSystemValue('datadirectory'). $versions_fileview->getAbsolutePath('');

			// check for old versions
			$matches = glob( $versionsFolderName.'/'.$filename.'.v*' );

			if( count( $matches ) > \OCP\Config::getSystemValue( 'files_versionmaxversions', Storage::DEFAULTMAXVERSIONS ) ) {

				$numberToDelete = count($matches) - \OCP\Config::getSystemValue( 'files_versionmaxversions', Storage::DEFAULTMAXVERSIONS );

				// delete old versions of a file
				$deleteItems = array_slice( $matches, 0, $numberToDelete );

				foreach( $deleteItems as $de ) {

					unlink( $versionsFolderName.'/'.$filename.'.v'.$de );

				}
			}
		}
	}

	/**
	 * @brief Erase all old versions of all user files
	 * @return true/false
	 */
	public function expireAll() {
		$view = \OCP\Files::getStorage('files_versions');
		return $view->deleteAll('', true);
	}
}
