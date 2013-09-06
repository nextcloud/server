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
	 * @brief get share information for a given file/folder path is encoded in URL
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
	 * @brief share file with a user/group, path to file is encoded in URL
	 *
	 * @param array $params with following parameters 'shareWith', 'shareType'
	 * @return \OC_OCS_Result result of share operation
	 */
	public static function setShare($params) {
		$path = $params['path'];

		$itemSource = self::getFileId($path);
		$itemType = self::getItemType($path);

		if($itemSource === null) {
			return new \OC_OCS_Result(null, 404, "wrong path, file/folder doesn't exist.");
		}

		$shareWith = isset($_POST['shareWith']) ? $_POST['shareWith'] : null;
		$shareType = isset($_POST['shareType']) ? (int)$_POST['shareType'] : null;

		switch($shareType) {
			case \OCP\Share::SHARE_TYPE_USER:
				$permission = 31;
				if (!\OCP\User::userExists($shareWith)) {
					return new \OC_OCS_Result(null, 404, "user doesn't exist");
				}
				break;
			case \OCP\Share::SHARE_TYPE_GROUP:
				$permission = 31;
				if (!\OC_Group::groupExists($shareWith)) {
					return new \OC_OCS_Result(null, 404, "group doesn't exist");
				}
				break;
			case \OCP\Share::SHARE_TYPE_LINK:
				$permission = 1;
				$shareWith = null;
				break;
			default:
				return new \OC_OCS_Result(null, 404, "unknown share type");
		}


		$token = \OCP\Share::shareItem(
					$itemType,
					$itemSource,
					$shareType,
					$shareWith,
					$permission
					);

		if ($token) {
			$data = null;
			if(is_string($token)) { //public link share
				$url = \OCP\Util::linkToPublic('files&t='.$token);
				$data = array('url' => $url, // '&' gets encoded to $amp;
					'token' => $token);

			}
			return new \OC_OCS_Result($data);
		} else {
			return new \OC_OCS_Result(null, 404, "couldn't share file");
		}
	}
	/**
	 * @brief set permission for a share, path to file is encoded in URL
	 * @param array $params contain 'shareWith', 'shareType', 'permission'
	 * @return \OC_OCS_Result
	 */
	public static function setPermission($params) {
		$path = $params['path'];
		$itemSource = self::getFileId($path);
		$itemType = self::getItemType($path);

		if($itemSource === null) {
			return new \OC_OCS_Result(null, 404, "wrong path, file/folder doesn't exist.");
		}

		$shareWith = isset($_POST['shareWith']) ? $_POST['shareWith'] : null;
		$shareType = isset($_POST['shareType']) ? (int)$_POST['shareType'] : null;
		$permission = isset($_POST['permission']) ? (int)$_POST['permission'] : null;

		switch($shareType) {
			case \OCP\Share::SHARE_TYPE_USER:
				if (!\OCP\User::userExists($shareWith)) {
					return new \OC_OCS_Result(null, 404, "user doesn't exist");
				}
				break;
			case \OCP\Share::SHARE_TYPE_GROUP:
				if (!\OC_Group::groupExists($shareWith)) {
					return new \OC_OCS_Result(null, 404, "group doesn't exist");
				}
				break;
			case \OCP\Share::SHARE_TYPE_LINK:
				break;
			default:
				return new \OC_OCS_Result(null, 404, "unknown share type");
		}


		$return = \OCP\Share::setPermissions(
				$itemType,
				$itemSource,
				$shareType,
				$shareWith,
				$permission
				);

		if ($return) {
			return new \OC_OCS_Result();
		} else {
			return new \OC_OCS_Result(null, 404, "couldn't set permissions");
		}
	}

	/**
	 * @brief set expire date, path to file is encoded in URL
	 * @param array $params contains 'expire' (format DD-MM-YYYY)
	 * @return \OC_OCS_Result
	 */
	public static function setExpire($params) {
		$path = $params['path'];
		$itemSource = self::getFileId($path);
		$itemType = self::getItemType($path);

		if($itemSource === null) {
			return new \OC_OCS_Result(null, 404, "wrong path, file/folder doesn't exist.");
		}

		$expire = isset($_POST['expire']) ? (int)$_POST['expire'] : null;

		$return = false;
		if ($expire) {
			$return = \OCP\Share::setExpirationDate($itemType, $itemSource, $expire);
		}

		if ($return) {
			return new \OC_OCS_Result();
		} else {
			$msg = "Failed, please check the expire date, expected format 'DD-MM-YYYY'.";
			return new \OC_OCS_Result(null, 404, $msg);
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