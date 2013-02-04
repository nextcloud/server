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

			$versions = new Storage( new \OC\Files\View('') );

			$path = $params[\OC\Files\Filesystem::signal_param_path];

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
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
		
			$versions = new Storage( new \OC_FilesystemView('') );
		
			$path = $params[\OC\Files\Filesystem::signal_param_path];
		
			if($path<>'') $versions->delete( $path );
		
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
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
		
			$versions = new Storage( new \OC_FilesystemView('') );
		
			$oldpath = $params['oldpath'];
			$newpath = $params['newpath'];
		
			if($oldpath<>'' && $newpath<>'') $versions->rename( $oldpath, $newpath );
		
		}
	}
	
}
