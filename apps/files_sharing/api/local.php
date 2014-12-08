<?php
/**
 * ownCloud - OCS API for local shares
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

namespace OCA\Files_Sharing\API;

class Local {

	/**
	 * get all shares
	 *
	 * @param array $params option 'file' to limit the result to a specific file/folder
	 * @return \OC_OCS_Result share information
	 */
	public static function getAllShares($params) {
		if (isset($_GET['shared_with_me']) && $_GET['shared_with_me'] !== 'false') {
				return self::getFilesSharedWithMe();
			}
		// if a file is specified, get the share for this file
		if (isset($_GET['path'])) {
			$params['itemSource'] = self::getFileId($_GET['path']);
			$params['path'] = $_GET['path'];
			$params['itemType'] = self::getItemType($_GET['path']);

			if ( isset($_GET['reshares']) && $_GET['reshares'] !== 'false' ) {
				$params['reshares'] = true;
			} else {
				$params['reshares'] = false;
			}

			if (isset($_GET['subfiles']) && $_GET['subfiles'] !== 'false') {
				return self::getSharesFromFolder($params);
			}
			return self::collectShares($params);
		}

		$shares = \OCP\Share::getItemShared('file', null);

		if ($shares === false) {
			return new \OC_OCS_Result(null, 404, 'could not get shares');
		} else {
			foreach ($shares as &$share) {
				if ($share['item_type'] === 'file' && isset($share['path'])) {
					$share['mimetype'] = \OC_Helper::getFileNameMimeType($share['path']);
					if (\OC::$server->getPreviewManager()->isMimeSupported($share['mimetype'])) {
						$share['isPreviewAvailable'] = true;
					}
				}
			}
			return new \OC_OCS_Result($shares);
		}

	}

	/**
	 * get share information for a given share
	 *
	 * @param array $params which contains a 'id'
	 * @return \OC_OCS_Result share information
	 */
	public static function getShare($params) {

		$s = self::getShareFromId($params['id']);
		$params['itemSource'] = $s['file_source'];
		$params['itemType'] = $s['item_type'];
		$params['specificShare'] = true;

		return self::collectShares($params);
	}

	/**
	 * collect all share information, either of a specific share or all
	 *        shares for a given path
	 * @param array $params
	 * @return \OC_OCS_Result
	 */
	private static function collectShares($params) {

		$itemSource = $params['itemSource'];
		$itemType = $params['itemType'];
		$getSpecificShare = isset($params['specificShare']) ? $params['specificShare'] : false;

		if ($itemSource !== null) {
			$shares = \OCP\Share::getItemShared($itemType, $itemSource);
			$receivedFrom = \OCP\Share::getItemSharedWithBySource($itemType, $itemSource);
			// if a specific share was specified only return this one
			if ($getSpecificShare === true) {
				foreach ($shares as $share) {
					if ($share['id'] === (int) $params['id']) {
						$shares = array('element' => $share);
						break;
					}
				}
			} else {
				$path = $params['path'];
				foreach ($shares as $key => $share) {
					$shares[$key]['path'] = $path;
				}
			}


			// include also reshares in the lists. This means that the result
			// will contain every user with access to the file.
			if (isset($params['reshares']) && $params['reshares'] === true) {
				$shares = self::addReshares($shares, $itemSource);
			}

			if ($receivedFrom) {
				foreach ($shares as $key => $share) {
					$shares[$key]['received_from'] = $receivedFrom['uid_owner'];
					$shares[$key]['received_from_displayname'] = \OCP\User::getDisplayName($receivedFrom['uid_owner']);
				}
			}
		} else {
			$shares = null;
		}

		if ($shares === null || empty($shares)) {
			return new \OC_OCS_Result(null, 404, 'share doesn\'t exist');
		} else {
			return new \OC_OCS_Result($shares);
		}
	}

	/**
	 * add reshares to a array of shares
	 * @param array $shares array of shares
	 * @param int $itemSource item source ID
	 * @return array new shares array which includes reshares
	 */
	private static function addReshares($shares, $itemSource) {

		// if there are no shares than there are also no reshares
		$firstShare = reset($shares);
		if ($firstShare) {
			$path = $firstShare['path'];
		} else {
			return $shares;
		}

		$select = '`*PREFIX*share`.`id`, `item_type`, `*PREFIX*share`.`parent`, `share_type`, `share_with`, `file_source`, `path` , `*PREFIX*share`.`permissions`, `stime`, `expiration`, `token`, `storage`, `mail_send`, `mail_send`';
		$getReshares = \OC_DB::prepare('SELECT ' . $select . ' FROM `*PREFIX*share` INNER JOIN `*PREFIX*filecache` ON `file_source` = `*PREFIX*filecache`.`fileid` WHERE `*PREFIX*share`.`file_source` = ? AND `*PREFIX*share`.`item_type` IN (\'file\', \'folder\') AND `uid_owner` != ?');
		$reshares = $getReshares->execute(array($itemSource, \OCP\User::getUser()))->fetchAll();

		foreach ($reshares as $key => $reshare) {
			if (isset($reshare['share_with']) && $reshare['share_with'] !== '') {
				$reshares[$key]['share_with_displayname'] = \OCP\User::getDisplayName($reshare['share_with']);
			}
			// add correct path to the result
			$reshares[$key]['path'] = $path;
		}

		return array_merge($shares, $reshares);
	}

	/**
	 * get share from all files in a given folder (non-recursive)
	 * @param array $params contains 'path' to the folder
	 * @return \OC_OCS_Result
	 */
	private static function getSharesFromFolder($params) {
		$path = $params['path'];
		$view = new \OC\Files\View('/'.\OCP\User::getUser().'/files');

		if(!$view->is_dir($path)) {
			return new \OC_OCS_Result(null, 400, "not a directory");
		}

		$content = $view->getDirectoryContent($path);

		$result = array();
		foreach ($content as $file) {
			// workaround because folders are named 'dir' in this context
			$itemType = $file['type'] === 'file' ? 'file' : 'folder';
			$share = \OCP\Share::getItemShared($itemType, $file['fileid']);
			if($share) {
				$receivedFrom =  \OCP\Share::getItemSharedWithBySource($itemType, $file['fileid']);
				reset($share);
				$key = key($share);
				if ($receivedFrom) {
					$share[$key]['received_from'] = $receivedFrom['uid_owner'];
					$share[$key]['received_from_displayname'] = \OCP\User::getDisplayName($receivedFrom['uid_owner']);
				}
				$result = array_merge($result, $share);
			}
		}

		return new \OC_OCS_Result($result);
	}

	/**
	 * get files shared with the user
	 * @return \OC_OCS_Result
	 */
	private static function getFilesSharedWithMe() {
		try	{
			$shares = \OCP\Share::getItemsSharedWith('file');
			foreach ($shares as &$share) {
				if ($share['item_type'] === 'file') {
					$share['mimetype'] = \OC_Helper::getFileNameMimeType($share['file_target']);
					if (\OC::$server->getPreviewManager()->isMimeSupported($share['mimetype'])) {
						$share['isPreviewAvailable'] = true;
					}
				}
			}
			$result = new \OC_OCS_Result($shares);
		} catch (\Exception $e) {
			$result = new \OC_OCS_Result(null, 403, $e->getMessage());
		}

		return $result;

	}

	/**
	 * create a new share
	 * @param array $params
	 * @return \OC_OCS_Result
	 */
	public static function createShare($params) {

		$path = isset($_POST['path']) ? $_POST['path'] : null;

		if($path === null) {
			return new \OC_OCS_Result(null, 400, "please specify a file or folder path");
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
				$publicUploadEnabled = \OC::$server->getAppConfig()->getValue('core', 'shareapi_allow_public_upload', 'yes');
				if(isset($_POST['publicUpload']) && $publicUploadEnabled !== 'yes') {
					return new \OC_OCS_Result(null, 403, "public upload disabled by the administrator");
				}
				$publicUpload = isset($_POST['publicUpload']) ? $_POST['publicUpload'] : 'false';
				// read, create, update (7) if public upload is enabled or
				// read (1) if public upload is disabled
				$permissions = $publicUpload === 'true' ? 7 : 1;
				break;
			default:
				return new \OC_OCS_Result(null, 400, "unknown share type");
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
			return new \OC_OCS_Result(null, 403, $e->getMessage());
		}

		if ($token) {
			$data = array();
			$data['id'] = 'unknown';
			$shares = \OCP\Share::getItemShared($itemType, $itemSource);
			if(is_string($token)) { //public link share
				foreach ($shares as $share) {
					if ($share['token'] === $token) {
						$data['id'] = $share['id'];
						break;
					}
				}
				$url = \OCP\Util::linkToPublic('files&t='.$token);
				$data['url'] = $url; // '&' gets encoded to $amp;
				$data['token'] = $token;

			} else {
				foreach ($shares as $share) {
					if ($share['share_with'] === $shareWith && $share['share_type'] === $shareType) {
						$data['id'] = $share['id'];
						break;
					}
				}
			}
			return new \OC_OCS_Result($data);
		} else {
			return new \OC_OCS_Result(null, 404, "couldn't share file");
		}
	}

	/**
	 * update shares, e.g. password, permissions, etc
	 * @param array $params shareId 'id' and the parameter we want to update
	 *                      currently supported: permissions, password, publicUpload
	 * @return \OC_OCS_Result
	 */
	public static function updateShare($params) {

		$share = self::getShareFromId($params['id']);

		if(!isset($share['file_source'])) {
			return new \OC_OCS_Result(null, 404, "wrong share Id, share doesn't exist.");
		}

		try {
			if(isset($params['_put']['permissions'])) {
				return self::updatePermissions($share, $params);
			} elseif (isset($params['_put']['password'])) {
				return self::updatePassword($share, $params);
			} elseif (isset($params['_put']['publicUpload'])) {
				return self::updatePublicUpload($share, $params);
			} elseif (isset($params['_put']['expireDate'])) {
				return self::updateExpireDate($share, $params);
			}
		} catch (\Exception $e) {

			return new \OC_OCS_Result(null, 400, $e->getMessage());
		}

		return new \OC_OCS_Result(null, 400, "Wrong or no update parameter given");

	}

	/**
	 * update permissions for a share
	 * @param array $share information about the share
	 * @param array $params contains 'permissions'
	 * @return \OC_OCS_Result
	 */
	private static function updatePermissions($share, $params) {

		$itemSource = $share['item_source'];
		$itemType = $share['item_type'];
		$shareWith = $share['share_with'];
		$shareType = $share['share_type'];
		$permissions = isset($params['_put']['permissions']) ? (int)$params['_put']['permissions'] : null;

		$publicUploadStatus = \OC::$server->getAppConfig()->getValue('core', 'shareapi_allow_public_upload', 'yes');
		$publicUploadEnabled = ($publicUploadStatus === 'yes') ? true : false;


		// only change permissions for public shares if public upload is enabled
		// and we want to set permissions to 1 (read only) or 7 (allow upload)
		if ( (int)$shareType === \OCP\Share::SHARE_TYPE_LINK ) {
			if ($publicUploadEnabled === false || ($permissions !== 7 && $permissions !== 1)) {
				return new \OC_OCS_Result(null, 400, "can't change permission for public link share");
			}
		}

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
	 * enable/disable public upload
	 * @param array $share information about the share
	 * @param array $params contains 'publicUpload' which can be 'yes' or 'no'
	 * @return \OC_OCS_Result
	 */
	private static function updatePublicUpload($share, $params) {

		$publicUploadEnabled = \OC::$server->getAppConfig()->getValue('core', 'shareapi_allow_public_upload', 'yes');
		if($publicUploadEnabled !== 'yes') {
			return new \OC_OCS_Result(null, 403, "public upload disabled by the administrator");
		}

		if ($share['item_type'] !== 'folder' ||
				(int)$share['share_type'] !== \OCP\Share::SHARE_TYPE_LINK ) {
			return new \OC_OCS_Result(null, 400, "public upload is only possible for public shared folders");
		}

		// read, create, update (7) if public upload is enabled or
		// read (1) if public upload is disabled
		$params['_put']['permissions'] = $params['_put']['publicUpload'] === 'true' ? 7 : 1;

		return self::updatePermissions($share, $params);

	}

	/**
	 * set expire date for public link share
	 * @param array $share information about the share
	 * @param array $params contains 'expireDate' which needs to be a well formated date string, e.g DD-MM-YYYY
	 * @return \OC_OCS_Result
	 */
	private static function updateExpireDate($share, $params) {
		// only public links can have a expire date
		if ((int)$share['share_type'] !== \OCP\Share::SHARE_TYPE_LINK ) {
			return new \OC_OCS_Result(null, 400, "expire date only exists for public link shares");
		}

		try {
			$expireDateSet = \OCP\Share::setExpirationDate($share['item_type'], $share['item_source'], $params['_put']['expireDate'], (int)$share['stime']);
			$result = ($expireDateSet) ? new \OC_OCS_Result() : new \OC_OCS_Result(null, 404, "couldn't set expire date");
		} catch (\Exception $e) {
			$result = new \OC_OCS_Result(null, 404, $e->getMessage());
		}

		return $result;

	}

	/**
	 * update password for public link share
	 * @param array $share information about the share
	 * @param array $params 'password'
	 * @return \OC_OCS_Result
	 */
	private static function updatePassword($share, $params) {

		$itemSource = $share['item_source'];
		$itemType = $share['item_type'];

		if( (int)$share['share_type'] !== \OCP\Share::SHARE_TYPE_LINK) {
			return  new \OC_OCS_Result(null, 400, "password protection is only supported for public shares");
		}

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

		try {
			$result = \OCP\Share::shareItem(
					$itemType,
					$itemSource,
					\OCP\Share::SHARE_TYPE_LINK,
					$shareWith,
					$permissions
					);
		} catch (\Exception $e) {
			return new \OC_OCS_Result(null, 403, $e->getMessage());
		}

		if($result) {
			return new \OC_OCS_Result();
		}

		return new \OC_OCS_Result(null, 404, "couldn't set password");
	}

	/**
	 * unshare a file/folder
	 * @param array $params contains the shareID 'id' which should be unshared
	 * @return \OC_OCS_Result
	 */
	public static function deleteShare($params) {

		$share = self::getShareFromId($params['id']);
		$fileSource = isset($share['file_source']) ? $share['file_source'] : null;
		$itemType = isset($share['item_type']) ? $share['item_type'] : null;;

		if($fileSource === null) {
			return new \OC_OCS_Result(null, 404, "wrong share ID, share doesn't exist.");
		}

		$shareWith = isset($share['share_with']) ? $share['share_with'] : null;
		$shareType = isset($share['share_type']) ? (int)$share['share_type'] : null;

		if( $shareType === \OCP\Share::SHARE_TYPE_LINK) {
			$shareWith = null;
		}

		try {
			$return = \OCP\Share::unshare(
					$itemType,
					$fileSource,
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
	 * get file ID from a given path
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
	 * get itemType
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

	/**
	 * get some information from a given share
	 * @param int $shareID
	 * @return array with: item_source, share_type, share_with, item_type, permissions
	 */
	private static function getShareFromId($shareID) {
		$sql = 'SELECT `file_source`, `item_source`, `share_type`, `share_with`, `item_type`, `permissions`, `stime` FROM `*PREFIX*share` WHERE `id` = ?';
		$args = array($shareID);
		$query = \OCP\DB::prepare($sql);
		$result = $query->execute($args);

		if (\OCP\DB::isError($result)) {
			\OCP\Util::writeLog('files_sharing', \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
			return null;
		}
		if ($share = $result->fetchRow()) {
			return $share;
		}

		return null;

	}

}
