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

		if (\OCP\App::isEnabled('files_versions')) {
			$path = $params[\OC\Files\Filesystem::signal_param_path];
			if($path<>'') {
				Storage::store($path);
			}
		}
	}


	/**
	 * Erase versions of deleted file
	 * @param array $params
	 *
	 * This function is connected to the delete signal of OC_Filesystem
	 * cleanup the versions directory if the actual file gets deleted
	 */
	public static function remove_hook($params) {

		if (\OCP\App::isEnabled('files_versions')) {
			$path = $params[\OC\Files\Filesystem::signal_param_path];
			if($path<>'') {
				Storage::delete($path);
			}
		}
	}

	/**
	 * mark file as "deleted" so that we can clean up the versions if the file is gone
	 * @param array $params
	 */
	public static function pre_remove_hook($params) {
		$path = $params[\OC\Files\Filesystem::signal_param_path];
			if($path<>'') {
				Storage::markDeletedFile($path);
			}
	}

	/**
	 * rename/move versions of renamed/moved files
	 * @param array $params array with oldpath and newpath
	 *
	 * This function is connected to the rename signal of OC_Filesystem and adjust the name and location
	 * of the stored versions along the actual file
	 */
	public static function rename_hook($params) {

		if (\OCP\App::isEnabled('files_versions')) {
			$oldpath = $params['oldpath'];
			$newpath = $params['newpath'];
			if($oldpath<>'' && $newpath<>'') {
				Storage::renameOrCopy($oldpath, $newpath, 'rename');
			}
		}
	}

	/**
	 * copy versions of copied files
	 * @param array $params array with oldpath and newpath
	 *
	 * This function is connected to the copy signal of OC_Filesystem and copies the
	 * the stored versions to the new location
	 */
	public static function copy_hook($params) {

		if (\OCP\App::isEnabled('files_versions')) {
			$oldpath = $params['oldpath'];
			$newpath = $params['newpath'];
			if($oldpath<>'' && $newpath<>'') {
				Storage::renameOrCopy($oldpath, $newpath, 'copy');
			}
		}
	}

}
