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

	public static function connectHooks() {
		// Listen to write signals
		\OCP\Util::connectHook('OC_Filesystem', 'write', 'OCA\Files_Versions\Hooks', 'write_hook');
		// Listen to delete and rename signals
		\OCP\Util::connectHook('OC_Filesystem', 'post_delete', 'OCA\Files_Versions\Hooks', 'remove_hook');
		\OCP\Util::connectHook('OC_Filesystem', 'delete', 'OCA\Files_Versions\Hooks', 'pre_remove_hook');
		\OCP\Util::connectHook('OC_Filesystem', 'post_rename', 'OCA\Files_Versions\Hooks', 'rename_hook');
		\OCP\Util::connectHook('OC_Filesystem', 'post_copy', 'OCA\Files_Versions\Hooks', 'copy_hook');
		\OCP\Util::connectHook('OC_Filesystem', 'rename', 'OCA\Files_Versions\Hooks', 'pre_renameOrCopy_hook');
		\OCP\Util::connectHook('OC_Filesystem', 'copy', 'OCA\Files_Versions\Hooks', 'pre_renameOrCopy_hook');
	}

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

	/**
	 * Remember owner and the owner path of the source file.
	 * If the file already exists, then it was a upload of a existing file
	 * over the web interface and we call Storage::store() directly
	 *
	 * @param array $params array with oldpath and newpath
	 *
	 */
	public static function pre_renameOrCopy_hook($params) {
		if (\OCP\App::isEnabled('files_versions')) {

			// if we rename a movable mount point, then the versions don't have
			// to be renamed
			$absOldPath = \OC\Files\Filesystem::normalizePath('/' . \OCP\User::getUser() . '/files' . $params['oldpath']);
			$manager = \OC\Files\Filesystem::getMountManager();
			$mount = $manager->find($absOldPath);
			$internalPath = $mount->getInternalPath($absOldPath);
			if ($internalPath === '' and $mount instanceof \OC\Files\Mount\MoveableMount) {
				return;
			}

			$view = new \OC\Files\View(\OCP\User::getUser() . '/files');
			if ($view->file_exists($params['newpath'])) {
				Storage::store($params['newpath']);
			} else {
				Storage::setSourcePathAndUser($params['oldpath']);
			}

		}
	}

}
