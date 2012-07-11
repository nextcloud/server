<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * This class contains all hooks.
 */
 
namespace OCA_Versions;

class Hooks {

	/**
	 * listen to write event.
	 */
	public static function write_hook( $params ) {
	
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			
			$versions = new Storage( new \OC_FilesystemView('') );
			
			$path = $params[\OC_Filesystem::signal_param_path];
			
			if($path<>'') $versions->store( $path );
			
		}
	}
	
	/**
	 * @brief Erase versions of deleted file
	 * @param array
	 *
	 * This function is connected to the delete signal of OC_Filesystem
	 * cleanup the versions directory if the actual file gets deleted
	 */
	public static function remove_hook($params) {
		$rel_path =  $params['path'];
		$abs_path = \OCP\Config::getSystemValue('datadirectory').'/'.\OCP\User::getUser()."/versions".$rel_path.'.v';
		if(Storage::isversioned($rel_path)) {
			$versions = Storage::getVersions($rel_path);
			foreach ($versions as $v){
				unlink($abs_path . $v['version']);
			}
		}
	}
	
	/**
	 * @brief rename/move versions of renamed/moved files
	 * @param array with oldpath and newpath
	 *
	 * This function is connected to the rename signal of OC_Filesystem and adjust the name and location
	 * of the stored versions along the actual file
	 */
	public static function rename_hook($params) {
		$rel_oldpath =  $params['oldpath'];
		$abs_oldpath = \OCP\Config::getSystemValue('datadirectory').'/'.\OCP\User::getUser()."/versions".$rel_oldpath.'.v';
		$abs_newpath = \OCP\Config::getSystemValue('datadirectory').'/'.\OCP\User::getUser()."/versions".$params['newpath'].'.v';
		if(Storage::isversioned($rel_oldpath)) {
			$versions = Storage::getVersions($rel_oldpath);
			foreach ($versions as $v){
				rename($abs_oldpath.$v['version'], $abs_newpath.$v['version']);
			}
		}
	}
	
}
