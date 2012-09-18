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

	private $view;

	function __construct() {

		$this->view = \OCP\Files::getStorage('files_versions');

	}

	/**
	 * listen to write event.
	 */
	public static function write_hook($params) {
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			$path = $params[\OC_Filesystem::signal_param_path];
			if($path<>'') $this->store($path);
		}
	}



	/**
	 * store a new version of a file.
	 */
	public function store($filename) {
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {

			$files_view = \OCP\Files::getStorage("files");
			$users_view = \OCP\Files::getStorage("files_versions");
			$users_view->chroot(\OCP\User::getUser().'/');

			//FIXME OC_Share no longer exists
			//if (\OCP\App::isEnabled('files_sharing') && $source = \OC_Share::getSource('/'.\OCP\User::getUser().'/files'.$filename)) {
			//	$pos = strpos($source, '/files', 1);
			//	$uid = substr($source, 1, $pos - 1);
			//	$filename = substr($source, $pos + 6);
			//} else {
				$uid = \OCP\User::getUser();
			//}

			$versionsFolderName=\OCP\Config::getSystemValue('datadirectory') .  $this->view->getAbsolutePath('');

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
				$matches=glob($versionsFolderName.'/'.$filename.'.v*');
				sort($matches);
				$parts=explode('.v',end($matches));
				if((end($parts)+Storage::DEFAULTMININTERVAL)>time()) {
					return false;
				}
			}


			// create all parent folders
			$info=pathinfo($filename);
			if(!file_exists($versionsFolderName.'/'.$info['dirname'])) mkdir($versionsFolderName.'/'.$info['dirname'],0700,true);

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
			$users_view = \OCP\Files::getStorage("files_versions");
			$users_view->chroot(\OCP\User::getUser().'/');

			//FIXME OC_Share no longer exists
			//if (\OCP\App::isEnabled('files_sharing') && $source = \OC_Share::getSource('/'.\OCP\User::getUser().'/files'.$filename)) {
			//	$pos = strpos($source, '/files', 1);
			//	$uid = substr($source, 1, $pos - 1);
			//	$filename = substr($source, $pos + 6);
			//} else {
				$uid = \OCP\User::getUser();
			//}

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
			$versions_fileview = \OCP\Files::getStorage("files_versions");
			//FIXME OC_Share no longer exists
			//if (\OCP\App::isEnabled('files_sharing') && $source = \OC_Share::getSource('/'.\OCP\User::getUser().'/files'.$filename)) {
			//	$pos = strpos($source, '/files', 1);
			//	$filename = substr($source, $pos + 6);
			//}

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

			//FIXME OC_Share no longer exists
			//if (\OCP\App::isEnabled('files_sharing') && $source = \OC_Share::getSource('/'.\OCP\User::getUser().'/files'.$filename)) {
			//	$pos = strpos($source, '/files', 1);
			//	$uid = substr($source, 1, $pos - 1);
			//	$filename = substr($source, $pos + 6);
			//} else {
				$uid = \OCP\User::getUser();
			//}
			$versions_fileview = \OCP\Files::getStorage('files_versions');
			$versionsFolderName = \OCP\Config::getSystemValue('datadirectory'). $versions_fileview->getAbsolutePath('');
			$versions = array();

			// fetch for old versions
			$matches = glob( $versionsFolderName.'/'.$filename.'.v*' );

			sort( $matches );

			$i = 0;

			$files_view = \OCP\Files::getStorage('files');
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

			//FIXME OC_Share no longer exists
			//if (\OCP\App::isEnabled('files_sharing') && $source = \OC_Share::getSource('/'.\OCP\User::getUser().'/files'.$filename)) {
			//	$pos = strpos($source, '/files', 1);
			//	$uid = substr($source, 1, $pos - 1);
			//	$filename = substr($source, $pos + 6);
			//} else {
				$uid = \OCP\User::getUser();
			//}
			$versions_fileview = \OCP\Files::getStorage("files_versions");
			$versionsFolderName=\OCP\Config::getSystemValue('datadirectory'). $versions_fileview->getAbsolutePath('');

			// check for old versions
			$matches = glob( $versionsFolderName.'/'.$filename.'.v*' );

			if( count( $matches ) > \OCP\Config::getSystemValue( 'files_versionmaxversions', Storage::DEFAULTMAXVERSIONS ) ) {

				$numberToDelete = count( $matches-\OCP\Config::getSystemValue( 'files_versionmaxversions', Storage::DEFAULTMAXVERSIONS ) );

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
		return $this->view->deleteAll('', true);
	}
}
