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
	//   - implement expire all function. And find a place to call it ;-)
	//   - add transparent compression. first test if it´s worth it.

	const DEFAULTENABLED=true; 
	const DEFAULTFOLDER='versions'; 
	const DEFAULTBLACKLIST='avi mp3 mpg mp4 ctmp'; 
	const DEFAULTMAXFILESIZE=1048576; // 10MB 
	const DEFAULTMININTERVAL=1; // 2 min
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
			$versionsFolderName=\OCP\Config::getSystemValue('datadirectory').'/'. $uid .'/'.\OCP\Config::getSystemValue('files_versionsfolder', Storage::DEFAULTFOLDER);
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
				$matches=glob($versionsFolderName.'/'.$filename.'.v*');
				sort($matches);
				$parts=explode('.v',end($matches));
				if((end($parts)+Storage::DEFAULTMININTERVAL)>time()){
					return false;
				}
			}


			// create all parent folders
			$info=pathinfo($filename);	
			if(!file_exists($versionsFolderName.'/'.$info['dirname'])) mkdir($versionsFolderName.'/'.$info['dirname'],0700,true);	

			// store a new version of a file
			copy($filesfoldername.'/'.$filename,$versionsFolderName.'/'.$filename.'.v'.time());
        
			// expire old revisions if necessary
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
			$versionsFolderName=\OCP\Config::getSystemValue('datadirectory').'/'.$uid .'/'.\OCP\Config::getSystemValue('files_versionsfolder', Storage::DEFAULTFOLDER);
			
			$filesfoldername=\OCP\Config::getSystemValue('datadirectory').'/'. $uid .'/files';
			
			// rollback
			if ( @copy($versionsFolderName.'/'.$filename.'.v'.$revision,$filesfoldername.'/'.$filename) ) {
			
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
			$versionsFolderName=\OCP\Config::getSystemValue('datadirectory').'/'. $uid .'/'.\OCP\Config::getSystemValue('files_versionsfolder', Storage::DEFAULTFOLDER);

			// check for old versions
			$matches=glob($versionsFolderName.'/'.$filename.'.v*');
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
         * @brief get a list of all available versions of a file in descending chronological order
         * @param $filename file to find versions of, relative to the user files dir
         * @param $count number of versions to return
         * @returns array
         */
        public static function getVersions( $filename, $count = 0 ) {
        
                if( \OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true' ) {
                
			if (\OCP\App::isEnabled('files_sharing') && $source = \OC_Share::getSource('/'.\OCP\User::getUser().'/files'.$filename)) {
				$pos = strpos($source, '/files', 1);
				$uid = substr($source, 1, $pos - 1);
				$filename = substr($source, $pos + 6);
			} else {
				$uid = \OCP\User::getUser();
			}
			$versionsFolderName = \OCP\Config::getSystemValue('datadirectory').'/'. $uid .'/'.\OCP\Config::getSystemValue('files_versionsfolder', Storage::DEFAULTFOLDER);
			$versions = array();         
			
			// fetch for old versions
			$matches = glob( $versionsFolderName.'/'.$filename.'.v*' );
			
			sort( $matches );
			
			$i = 0;
			
			foreach( $matches as $ma ) {
				
				$i++;
				$versions[$i]['cur'] = 0;
				$parts = explode( '.v', $ma );
				$versions[$i]['version'] = ( end( $parts ) );
				
				// if file with modified date exists, flag it in array as currently enabled version
				$curFile['fileName'] = basename( $parts[0] );
				$curFile['filePath'] = \OCP\Config::getSystemValue('datadirectory').\OC_Filesystem::getRoot().'/'.$curFile['fileName'];
				
				( \md5_file( $ma ) == \md5_file( $curFile['filePath'] ) ? $versions[$i]['fileMatch'] = 1 : $versions[$i]['fileMatch'] = 0 );
				
			}
			
			$versions = array_reverse( $versions );
			
			foreach( $versions as $key => $value ) {
				
				// flag the first matched file in array (which will have latest modification date) as current version
				if ( $versions[$key]['fileMatch'] ) {
				
					$versions[$key]['cur'] = 1;
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
			$versionsFolderName=\OCP\Config::getSystemValue('datadirectory').'/'. $uid .'/'.\OCP\Config::getSystemValue('files_versionsfolder', Storage::DEFAULTFOLDER);

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
         * @brief erase all old versions of all user files
         * @return 
         */
        public static function expireAll() {
	
		function deleteAll($directory, $empty = false) {
		
			if(substr($directory,-1) == "/") {
				$directory = substr($directory,0,-1);
			}

			if(!file_exists($directory) || !is_dir($directory)) {
			
				return false;
				
			} elseif(!is_readable($directory)) {
			
				return false;
				
			} else {
			
				$directoryHandle = opendir($directory);
			
				while ($contents = readdir($directoryHandle)) {
				
					if( $contents != '.' && $contents != '..') {
						
						$path = $directory . "/" . $contents;
					
						if( is_dir($path) ) {
							
							deleteAll($path);
						
						} else {
						
							unlink($path);
						
						}
					}
				
				}
			
				closedir( $directoryHandle );

				if( $empty == false ) {
				
					if(!rmdir($directory)) {
					
						return false;
						
					}
					
				}
			
				return true;
			}
			
		}
	
		/*	
		// FIXME: make this path dynamic
		$dir = '/home/samtuke/owncloud/git/oc5/data/admin/versions';

		( deleteAll( $dir, 1 ) ? return true : return false );
		*/
        }


}
