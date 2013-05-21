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

namespace OCA\Files_Versions;

class Hooks {

	/**
	 * listen to write event.
	 */
	public static function write_hook( $params ) {

		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			$path = $params[\OC\Files\Filesystem::signal_param_path];
			if($path<>'') {
				Storage::store($path);
			}
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
			$path = $params[\OC\Files\Filesystem::signal_param_path];
			if($path<>'') {
				Storage::delete($path);
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
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			$oldpath = $params['oldpath'];
			$newpath = $params['newpath'];
			if($oldpath<>'' && $newpath<>'') {
				Storage::rename( $oldpath, $newpath );
			}

		}
	}

	/**
	 * @brief clean up user specific settings if user gets deleted
	 * @param array with uid
	 *
	 * This function is connected to the pre_deleteUser signal of OC_Users
	 * to remove the used space for versions stored in the database
	 */
	public static function deleteUser_hook($params) {
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			$uid = $params['uid'];
			Storage::deleteUser($uid);
			}
	}

}
