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
	 * @brief get all shares
	 *
	 * @param array $params
	 * @return \OC_OCS_Result share information
	 */
	public static function getAllShare($params) {

		$share = \OCP\Share::getItemShared('file', null);

		if ($share !== null) {
			return new \OC_OCS_Result($share);
		} else {
			return new \OC_OCS_Result(null, 404, 'no shares available');
		}
	}

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
	 * @breif create a new share
	 * @param array $params 'path', 'shareWith', 'shareType'
	 * @return \OC_OCS_Result
	 */
	public static function createShare($params) {

		$path = isset($_POST['path']) ? $_POST['path'] : null;

		if($path === null) {
			return new \OC_OCS_Result(null, 404, "please specify a file or folder path");
		}

		$itemSource = self::getFileId($path);
		$itemType = self::getItemType($path);

		if($itemSource === null) {
			return new \OC_OCS_Result(null, 404, "wrong path, file/folder doesn't exist.");
		}

		$shareWith = isset($_POST['shareWith']) ? $_POST['shareWith'] : null;
		$shareType = isset($_POST['shareType']) ? (int)$_POST['shareType'] : null;

		switch($shareType) {
			case \OCP\Share::SHARE_TYPE_USER:
				$permissions = isset($_POST['permissions']) ? (int)$_POST['permissions'] : 31;
				break;
			case \OCP\Share::SHARE_TYPE_GROUP:
				$permissions = isset($_POST['permissions']) ? (int)$_POST['permissions'] : 31;
				break;
			case \OCP\Share::SHARE_TYPE_LINK:
				//allow password protection
				$shareWith = isset($_POST['password']) ? $_POST['password'] : null;
				//check public link share
				$publicUploadEnabled = \OC_Appconfig::getValue('core', 'shareapi_allow_public_upload', 'yes');
				$encryptionEnabled = \OC_App::isEnabled('files_encryption');
				if(isset($_POST['publicUpload']) &&
						($encryptionEnabled || $publicUploadEnabled !== 'yes')) {
					return new \OC_OCS_Result(null, 404, "public upload disabled by the administrator");
				}
				$publicUpload = isset($_POST['publicUpload']) ? $_POST['publicUpload'] : 'no';
				// read, create, update (7) if public upload is enabled or
				// read (1) if public upload is disabled
				$permissions = $publicUpload === 'yes' ? 7 : 1;
				break;
		}

		try	{
			$token = \OCP\Share::shareItem(
					$itemType,
					$itemSource,
					$shareType,
					$shareWith,
					$permissions
					);
		} catch (\Exception $e) {
			return new \OC_OCS_Result(null, 404, $e->getMessage());
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
			return new \OC_OCS_Result(null, 404, "couldn't share file");
		}
	}

	/**
	 * update shares, e.g. expire date, permissions, etc
	 * @param array $params 'path', 'shareWith', 'shareType' and
	 *                      'permissions' or 'expire' or 'password'
	 * @return \OC_OCS_Result
	 */
	public static function updateShare($params) {

		$path = $params['path'];

		$itemSource = self::getFileId($path);
		$itemType = self::getItemType($path);

		if($itemSource === null) {
			return new \OC_OCS_Result(null, 404, "wrong path, file/folder doesn't exist.");
		}

		try {
			if(isset($params['_put']['permissions'])) {
				return self::updatePermissions($itemSource, $itemType, $params);
			} elseif (isset($params['_put']['expire'])) {
				return self::updateExpire($itemSource, $itemType, $params);
			} elseif (isset($params['_put']['password'])) {
				return self::updatePassword($itemSource, $itemType, $params);
			}
		} catch (\Exception $e) {
			return new \OC_OCS_Result(null, 404, $e->getMessage());
		}

		return new \OC_OCS_Result(null, 404, "Couldn't find a parameter to update");

	}

	/**
	 * @brief update permissions for a share
	 * @param int $itemSource file ID
	 * @param string $itemType 'file' or 'folder'
	 * @param array $params contain 'shareWith', 'shareType', 'permissions'
	 * @return \OC_OCS_Result
	 */
	private static function updatePermissions($itemSource, $itemType, $params) {

		$shareWith = isset($params['_put']['shareWith']) ? $params['_put']['shareWith'] : null;
		$shareType = isset($params['_put']['shareType']) ? (int)$params['_put']['shareType'] : null;
		$permissions = isset($params['_put']['permissions']) ? (int)$params['_put']['permissions'] : null;

		try {
			$return = \OCP\Share::setPermissions(
					$itemType,
					$itemSource,
					$shareType,
					$shareWith,
					$permissions
					);
		} catch (\Exception $e) {
			return new \OC_OCS_Result(null, 404, $e->getMessage());
		}

		if ($return) {
			return new \OC_OCS_Result();
		} else {
			return new \OC_OCS_Result(null, 404, "couldn't set permissions");
		}
	}

	/**
	 * @brief update password for public link share
	 * @param int $itemSource file ID
	 * @param string $itemType 'file' or 'folder'
	 * @param type $params 'password'
	 * @return \OC_OCS_Result
	 */
	private static function updatePassword($itemSource, $itemType, $params) {
		error_log("update password");
		$shareWith = isset($params['_put']['password']) ? $params['_put']['password'] : null;

		if($shareWith === '') {
			$shareWith = null;
		}

		$items = \OCP\Share::getItemShared($itemType, $itemSource);

		$checkExists = false;
		foreach ($items as $item) {
			if($item['share_type'] === \OCP\Share::SHARE_TYPE_LINK) {
				$checkExists = true;
				$permissions = $item['permissions'];
			}
		}

		if (!$checkExists) {
			return  new \OC_OCS_Result(null, 404, "share doesn't exists, can't change password");
		}

		$result = \OCP\Share::shareItem(
				$itemType,
				$itemSource,
				\OCP\Share::SHARE_TYPE_LINK,
				$shareWith,
				$permissions
				);
		if($result) {
			return new \OC_OCS_Result();
		}

		return new \OC_OCS_Result(null, 404, "couldn't set password");
	}

	/**
	 * @brief set expire date, path to file is encoded in URL
	 * @param int $itemSource file ID
	 * @param string $itemType 'file' or 'folder'
	 * @param array $params contains 'expire' (format DD-MM-YYYY)
	 * @return \OC_OCS_Result
	 */
	private static function updateExpire($itemSource, $itemType, $params) {

		$expire = isset($params['_put']['expire']) ? (int)$params['_put']['expire'] : null;

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
	 * @brief unshare a file/folder
	 * @param array $params with following parameters 'shareWith', 'shareType', 'path'
	 * @return \OC_OCS_Result
	 */
	public static function deleteShare($params) {
		$path = $params['path'];
		$itemSource = self::getFileId($path);
		$itemType = self::getItemType($path);

		if($itemSource === null) {
			return new \OC_OCS_Result(null, 404, "wrong path, file/folder doesn't exist.");
		}

		$shareWith = isset($params['_delete']['shareWith']) ? $params['_delete']['shareWith'] : null;
		$shareType = isset($params['_delete']['shareType']) ? (int)$params['_delete']['shareType'] : null;

		if( $shareType == \OCP\Share::SHARE_TYPE_LINK) {
			$shareWith = null;
		}

		try {
			$return = \OCP\Share::unshare(
					$itemType,
					$itemSource,
					$shareType,
					$shareWith);
		} catch (\Exception $e) {
			return new \OC_OCS_Result(null, 404, $e->getMessage());
		}

		if ($return) {
			return new \OC_OCS_Result();
		} else {
			$msg = "Unshare Failed";
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
