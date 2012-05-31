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
	//   - port to oc_filesystem to enable network transparency
	//   - check if it works well together with encryption
	//   - implement expire all function. And find a place to call it ;-)
	//   - add transparent compression. first test if it´s worth it.

	const DEFAULTENABLED=true; 
	const DEFAULTFOLDER='versions'; 
	const DEFAULTBLACKLIST='avi mp3 mpg mp4 ctmp'; 
	const DEFAULTMAXFILESIZE=1048576; // 10MB 
	const DEFAULTMININTERVAL=120; // 2 min
	const DEFAULTMAXVERSIONS=50; 

	/**
	 * init the versioning and create the versions folder.
	 */
	public static function init() {
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			// create versions folder
			$foldername=\OCP\Config::getSystemValue('datadirectory').'/'. \OCP\USER::getUser() .'/'.\OCP\Config::getSystemValue('files_versionsfolder', Storage::DEFAULTFOLDER);
			if(!is_dir($foldername)){
				mkdir($foldername);
			}
		}
	}


	/**
	 * listen to write event.
	 */
	public static function write_hook($params) {
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			$path = $params[\OC_Filesystem::signal_param_path];
			if($path<>'') Storage::store($path);
		}
	}



	/**
	 * store a new version of a file.
	 */
	public static function store($filename) {
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			if (\OCP\App::isEnabled('files_sharing') && $source = \OC_Share::getSource('/'.\OCP\User::getUser().'/files'.$filename)) {
				$pos = strpos($source, '/files', 1);
				$uid = substr($source, 1, $pos - 1);
				$filename = substr($source, $pos + 6);
			} else {
				$uid = \OCP\User::getUser();
			}
			$versionsfoldername=\OCP\Config::getSystemValue('datadirectory').'/'. $uid .'/'.\OCP\Config::getSystemValue('files_versionsfolder', Storage::DEFAULTFOLDER);
			$filesfoldername=\OCP\Config::getSystemValue('datadirectory').'/'. $uid .'/files';
			Storage::init();

			// check if filename is a directory
			if(is_dir($filesfoldername.'/'.$filename)){
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
			
			// check filesize
			if(filesize($filesfoldername.'/'.$filename)>\OCP\Config::getSystemValue('files_versionsmaxfilesize', Storage::DEFAULTMAXFILESIZE)){
				return false;
			}


			// check mininterval if the file is being modified by the owner (all shared files should be versioned despite mininterval)
			if ($uid == \OCP\User::getUser()) {
				$matches=glob($versionsfoldername.'/'.$filename.'.v*');
				sort($matches);
				$parts=explode('.v',end($matches));
				if((end($parts)+Storage::DEFAULTMININTERVAL)>time()){
					return false;
				}
			}


			// create all parent folders
			$info=pathinfo($filename);	
			@mkdir($versionsfoldername.'/'.$info['dirname'],0700,true);	

			// store a new version of a file
			copy($filesfoldername.'/'.$filename,$versionsfoldername.'/'.$filename.'.v'.time());
        
			// expire old revisions
			Storage::expire($filename);
		}
	}


	/**
	 * rollback to an old version of a file.
	 */
	public static function rollback($filename,$revision) {
	
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			if (\OCP\App::isEnabled('files_sharing') && $source = \OC_Share::getSource('/'.\OCP\User::getUser().'/files'.$filename)) {
				$pos = strpos($source, '/files', 1);
				$uid = substr($source, 1, $pos - 1);
				$filename = substr($source, $pos + 6);
			} else {
				$uid = \OCP\User::getUser();
			}
			$versionsfoldername=\OCP\Config::getSystemValue('datadirectory').'/'.$uid .'/'.\OCP\Config::getSystemValue('files_versionsfolder', Storage::DEFAULTFOLDER);
			
			$filesfoldername=\OCP\Config::getSystemValue('datadirectory').'/'. $uid .'/files';
			
			// rollback
			if ( @copy($versionsfoldername.'/'.$filename.'.v'.$revision,$filesfoldername.'/'.$filename) ) {
			
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
			if (\OCP\App::isEnabled('files_sharing') && $source = \OC_Share::getSource('/'.\OCP\User::getUser().'/files'.$filename)) {
				$pos = strpos($source, '/files', 1);
				$uid = substr($source, 1, $pos - 1);
				$filename = substr($source, $pos + 6);
			} else {
				$uid = \OCP\User::getUser();
			}
			$versionsfoldername=\OCP\Config::getSystemValue('datadirectory').'/'. $uid .'/'.\OCP\Config::getSystemValue('files_versionsfolder', Storage::DEFAULTFOLDER);

			// check for old versions
			$matches=glob($versionsfoldername.'/'.$filename.'.v*');
			if(count($matches)>1){
				return true;
			}else{
				return false;
			}
		}else{
			return(false);
		}
	}


        
        /**
         * get a list of old versions of a file.
         */
        public static function getversions($filename,$count=0) {
                if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			if (\OCP\App::isEnabled('files_sharing') && $source = \OC_Share::getSource('/'.\OCP\User::getUser().'/files'.$filename)) {
				$pos = strpos($source, '/files', 1);
				$uid = substr($source, 1, $pos - 1);
				$filename = substr($source, $pos + 6);
			} else {
				$uid = \OCP\User::getUser();
			}
			$versionsfoldername=\OCP\Config::getSystemValue('datadirectory').'/'. $uid .'/'.\OCP\Config::getSystemValue('files_versionsfolder', Storage::DEFAULTFOLDER);
			$versions=array();         
 
	              // fetch for old versions
			$matches=glob($versionsfoldername.'/'.$filename.'.v*');
			sort($matches);
			foreach($matches as $ma) {
				$parts=explode('.v',$ma);
				$versions[]=(end($parts));
			}
			
			// only show the newest commits
			if($count<>0 and (count($versions)>$count)) {
				$versions=array_slice($versions,count($versions)-$count);
			}
	
			return($versions);


                }else{
                        return(array());
                }
        }	


        
        /**
         * expire old versions of a file.
         */
        public static function expire($filename) {
                if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			if (\OCP\App::isEnabled('files_sharing') && $source = \OC_Share::getSource('/'.\OCP\User::getUser().'/files'.$filename)) {
				$pos = strpos($source, '/files', 1);
				$uid = substr($source, 1, $pos - 1);
				$filename = substr($source, $pos + 6);
			} else {
				$uid = \OCP\User::getUser();
			}
			$versionsfoldername=\OCP\Config::getSystemValue('datadirectory').'/'. $uid .'/'.\OCP\Config::getSystemValue('files_versionsfolder', Storage::DEFAULTFOLDER);

			// check for old versions
			$matches=glob($versionsfoldername.'/'.$filename.'.v*');
			if(count($matches)>\OCP\Config::getSystemValue('files_versionmaxversions', Storage::DEFAULTMAXVERSIONS)){
				$numbertodelete=count($matches-\OCP\Config::getSystemValue('files_versionmaxversions', Storage::DEFAULTMAXVERSIONS));

				// delete old versions of a file
				$deleteitems=array_slice($matches,0,$numbertodelete);
				foreach($deleteitems as $de){
					unlink($versionsfoldername.'/'.$filename.'.v'.$de);
				}
			}
                }
        }

        /**
         * expire all old versions.
         */
        public static function expireall($filename) {
		// todo this should go through all the versions directories and delete all the not needed files and not needed directories.
		// useful to be included in a cleanup cronjob.
        }


}
