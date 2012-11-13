<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2012 Bjoern Schiessle schiessle@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


class OC_Files_Sharing_Util {

	private static $files = array();

	/**
	 * @brief Get the source file path and the permissions granted for a shared file
	 * @param string Shared target file path
	 * @return Returns array with the keys path and permissions or false if not found
	 */
	private static function getFile($target) {
		$target = '/'.$target;
		$target = rtrim($target, '/');
		if (isset(self::$files[$target])) {
			return self::$files[$target];
		} else {
			$pos = strpos($target, '/', 1);
			// Get shared folder name
			if ($pos !== false) {
				$folder = substr($target, 0, $pos);
				if (isset(self::$files[$folder])) {
					$file = self::$files[$folder];
				} else {
					$file = OCP\Share::getItemSharedWith('folder', $folder, OC_Share_Backend_File::FORMAT_SHARED_STORAGE);
				}
				if ($file) {
					self::$files[$target]['path'] = $file['path'].substr($target, strlen($folder));
					self::$files[$target]['permissions'] = $file['permissions'];
					return self::$files[$target];
				}
			} else {
				$file = OCP\Share::getItemSharedWith('file', $target, OC_Share_Backend_File::FORMAT_SHARED_STORAGE);
				if ($file) {
					self::$files[$target] = $file;
					return self::$files[$target];
				}
			}
			OCP\Util::writeLog('files_sharing', 'File source not found for: '.$target, OCP\Util::ERROR);
			return false;
		}
	}

	/**
	 * @brief Get the source file path for a shared file
	 * @param string Shared target file path
	 * @return Returns source file path or false if not found
	 */
	public static function getSourcePath($target) {
		$file = self::getFile($target);
		if (isset($file['path'])) {
			$uid = substr($file['path'], 1, strpos($file['path'], '/', 1) - 1);
			OC_Filesystem::mount('OC_Filestorage_Local', array('datadir' => OC_User::getHome($uid)), $uid);
			return $file['path'];
		}
		return false;
	}
}