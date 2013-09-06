<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2013 Bjoern Schiessle schiessle@owncloud.com
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

namespace OCA\Files\Share;

class Api {

	/**
	 * @brief get share information for a given file/folder
	 *
	 * @param array $params which contains a 'path' to a file/folder
	 * @return \OC_OCS_Result share information
	 */
	public static function getShare($params) {
		$path = $params['path'];

		$fileId = self::getFileId($path);
		if ($fileId !== null) {
			$share = \OCP\Share::getItemShared('file', $fileId);
		} else {
			$share = null;
		}

		if ($share !== null) {
			return new \OC_OCS_Result($share);
		} else {
			return new \OC_OCS_Result(null, 404, 'file/folder doesn\'t exists');
		}
	}

	/**
	 * @brief share file with a user/group
	 *
	 * @param array $params which contains a 'path' to a file/folder
	 * @return \OC_OCS_Result result of share operation
	 */
	public static function setShare($params) {
		$path = $params['path'];
		$errorMessage = '';

		$itemSource = self::getFileId($path);
		$itemType = self::getItemType($path);

		$shareWith = isset($_POST['shareWith']) ? $_POST['shareWith'] : null;
		$shareType = isset($_POST['shareType']) ? (int)$_POST['shareType'] : null;

		if($shareType === \OCP\Share::SHARE_TYPE_LINK) {
			$permissions = 1;
			$shareWith = null;
		} else {
			$permissions = 31;
		}


		$token = null;
		if (($shareWith !== null || $shareType === \OCP\Share::SHARE_TYPE_LINK)
				&& $shareType !== false
				&& $itemType !== false) {
			$token = \OCP\Share::shareItem(
					$itemType,
					$itemSource,
					$shareType,
					$shareWith,
					$permissions
					);
		} else {
			$errorMessage = "You need to specify at least 'shareType' and provide a correct file/folder path."
				. " For non public shares you also need specify 'shareWith'.";
		}


		if ($token) {
			$data = null;
			if(is_string($token)) { //public link share
				$url = \OCP\Util::linkToPublic('files&t='.$token);
				$data = array('url' => $url, // '&' gets encoded to $amp;
					'token' => $token);

			}
			return new \OC_OCS_Result($data);
		} else {
			return new \OC_OCS_Result(null, 404, $errorMessage);
		}
	}

	/**
	 * @brief get file ID from a given path
	 * @param string $path
	 * @return string fileID or null
	 */
	private static function getFileId($path) {
		$view = new \OC\Files\View('/'.\OCP\User::getUser().'/files');
		$fileId = null;

		$fileInfo = $view->getFileInfo($path);
		if ($fileInfo) {
			$fileId = $fileInfo['fileid'];
		}

		return $fileId;
	}

	/**
	 * @brief get itemType
	 * @param string $path
	 * @return string type 'file', 'folder' or null of file/folder doesn't exists
	 */
	private static function getItemType($path) {
		$view = new \OC\Files\View('/'.\OCP\User::getUser().'/files');
		$itemType = null;

		if ($view->is_dir($path)) {
			$itemType = "folder";
		} elseif ($view->is_file($path)) {
			$itemType = "file";
		}

		return $itemType;
	}

}