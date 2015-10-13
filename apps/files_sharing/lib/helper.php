<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_Sharing;

class Helper {

	public static function registerHooks() {
		\OCP\Util::connectHook('OC_Filesystem', 'delete', '\OC\Files\Cache\Shared_Updater', 'deleteHook');
		\OCP\Util::connectHook('OC_Filesystem', 'post_rename', '\OC\Files\Cache\Shared_Updater', 'renameHook');
		\OCP\Util::connectHook('OC_Filesystem', 'post_delete', '\OCA\Files_Sharing\Hooks', 'unshareChildren');
		\OCP\Util::connectHook('OC_Appconfig', 'post_set_value', '\OCA\Files\Share\Maintainer', 'configChangeHook');

		\OCP\Util::connectHook('OCP\Share', 'post_shared', '\OC\Files\Cache\Shared_Updater', 'postShareHook');
		\OCP\Util::connectHook('OCP\Share', 'post_unshare', '\OC\Files\Cache\Shared_Updater', 'postUnshareHook');
		\OCP\Util::connectHook('OCP\Share', 'post_unshareFromSelf', '\OC\Files\Cache\Shared_Updater', 'postUnshareFromSelfHook');

		\OCP\Util::connectHook('OC_User', 'post_deleteUser', '\OCA\Files_Sharing\Hooks', 'deleteUser');
	}

	/**
	 * Sets up the filesystem and user for public sharing
	 * @param string $token string share token
	 * @param string $relativePath optional path relative to the share
	 * @param string $password optional password
	 */
	public static function setupFromToken($token, $relativePath = null, $password = null) {
		\OC_User::setIncognitoMode(true);

		$linkItem = \OCP\Share::getShareByToken($token, !$password);
		if($linkItem === false || ($linkItem['item_type'] !== 'file' && $linkItem['item_type'] !== 'folder')) {
			\OC_Response::setStatus(404);
			\OCP\Util::writeLog('core-preview', 'Passed token parameter is not valid', \OCP\Util::DEBUG);
			exit;
		}

		if(!isset($linkItem['uid_owner']) || !isset($linkItem['file_source'])) {
			\OC_Response::setStatus(500);
			\OCP\Util::writeLog('core-preview', 'Passed token seems to be valid, but it does not contain all necessary information . ("' . $token . '")', \OCP\Util::WARN);
			exit;
		}

		$rootLinkItem = \OCP\Share::resolveReShare($linkItem);
		$path = null;
		if (isset($rootLinkItem['uid_owner'])) {
			\OCP\JSON::checkUserExists($rootLinkItem['uid_owner']);
			\OC_Util::tearDownFS();
			\OC_Util::setupFS($rootLinkItem['uid_owner']);
			$path = \OC\Files\Filesystem::getPath($linkItem['file_source']);
		}

		if ($path === null) {
			\OCP\Util::writeLog('share', 'could not resolve linkItem', \OCP\Util::DEBUG);
			\OC_Response::setStatus(404);
			\OCP\JSON::error(array('success' => false));
			exit();
		}

		if (!isset($linkItem['item_type'])) {
			\OCP\Util::writeLog('share', 'No item type set for share id: ' . $linkItem['id'], \OCP\Util::ERROR);
			\OC_Response::setStatus(404);
			\OCP\JSON::error(array('success' => false));
			exit();
		}

		if (isset($linkItem['share_with']) && (int)$linkItem['share_type'] === \OCP\Share::SHARE_TYPE_LINK) {
			if (!self::authenticate($linkItem, $password)) {
				\OC_Response::setStatus(403);
				\OCP\JSON::error(array('success' => false));
				exit();
			}
		}

		$basePath = $path;

		if ($relativePath !== null && \OC\Files\Filesystem::isReadable($basePath . $relativePath)) {
			$path .= \OC\Files\Filesystem::normalizePath($relativePath);
		}

		return array(
			'linkItem' => $linkItem,
			'basePath' => $basePath,
			'realPath' => $path
		);
	}

	/**
	 * Authenticate link item with the given password
	 * or with the session if no password was given.
	 * @param array $linkItem link item array
	 * @param string $password optional password
	 *
	 * @return boolean true if authorized, false otherwise
	 */
	public static function authenticate($linkItem, $password = null) {
		if ($password !== null) {
			if ($linkItem['share_type'] == \OCP\Share::SHARE_TYPE_LINK) {
				// Check Password
				$newHash = '';
				if(\OC::$server->getHasher()->verify($password, $linkItem['share_with'], $newHash)) {
					// Save item id in session for future requests
					\OC::$server->getSession()->set('public_link_authenticated', $linkItem['id']);

					/**
					 * FIXME: Migrate old hashes to new hash format
					 * Due to the fact that there is no reasonable functionality to update the password
					 * of an existing share no migration is yet performed there.
					 * The only possibility is to update the existing share which will result in a new
					 * share ID and is a major hack.
					 *
					 * In the future the migration should be performed once there is a proper method
					 * to update the share's password. (for example `$share->updatePassword($password)`
					 *
					 * @link https://github.com/owncloud/core/issues/10671
					 */
					if(!empty($newHash)) {

					}
				} else {
					return false;
				}
			} else {
				\OCP\Util::writeLog('share', 'Unknown share type '.$linkItem['share_type']
					.' for share id '.$linkItem['id'], \OCP\Util::ERROR);
				return false;
			}

		}
		else {
			// not authenticated ?
			if ( ! \OC::$server->getSession()->exists('public_link_authenticated')
				|| \OC::$server->getSession()->get('public_link_authenticated') !== $linkItem['id']) {
				return false;
			}
		}
		return true;
	}

	public static function getSharesFromItem($target) {
		$result = array();
		$owner = \OC\Files\Filesystem::getOwner($target);
		\OC\Files\Filesystem::initMountPoints($owner);
		$info = \OC\Files\Filesystem::getFileInfo($target);
		$ownerView = new \OC\Files\View('/'.$owner.'/files');
		if ( $owner != \OCP\User::getUser() ) {
			$path = $ownerView->getPath($info['fileid']);
		} else {
			$path = $target;
		}


		$ids = array();
		while ($path !== dirname($path)) {
			$info = $ownerView->getFileInfo($path);
			if ($info instanceof \OC\Files\FileInfo) {
				$ids[] = $info['fileid'];
			} else {
				\OCP\Util::writeLog('sharing', 'No fileinfo available for: ' . $path, \OCP\Util::WARN);
			}
			$path = dirname($path);
		}

		if (!empty($ids)) {

			$idList = array_chunk($ids, 99, true);

			foreach ($idList as $subList) {
				$statement = "SELECT `share_with`, `share_type`, `file_target` FROM `*PREFIX*share` WHERE `file_source` IN (" . implode(',', $subList) . ") AND `share_type` IN (0, 1, 2)";
				$query = \OCP\DB::prepare($statement);
				$r = $query->execute();
				$result = array_merge($result, $r->fetchAll());
			}
		}

		return $result;
	}

	public static function getUidAndFilename($filename) {
		$uid = \OC\Files\Filesystem::getOwner($filename);
		\OC\Files\Filesystem::initMountPoints($uid);
		if ( $uid != \OCP\User::getUser() ) {
			$info = \OC\Files\Filesystem::getFileInfo($filename);
			$ownerView = new \OC\Files\View('/'.$uid.'/files');
			$filename = $ownerView->getPath($info['fileid']);
		}
		return array($uid, $filename);
	}

	/**
	 * Format a path to be relative to the /user/files/ directory
	 * @param string $path the absolute path
	 * @return string e.g. turns '/admin/files/test.txt' into 'test.txt'
	 */
	public static function stripUserFilesPath($path) {
		$trimmed = ltrim($path, '/');
		$split = explode('/', $trimmed);

		// it is not a file relative to data/user/files
		if (count($split) < 3 || $split[1] !== 'files') {
			return false;
		}

		$sliced = array_slice($split, 2);
		$relPath = implode('/', $sliced);

		return $relPath;
	}

	/**
	 * check if file name already exists and generate unique target
	 *
	 * @param string $path
	 * @param array $excludeList
	 * @param \OC\Files\View $view
	 * @return string $path
	 */
	public static function generateUniqueTarget($path, $excludeList, $view) {
		$pathinfo = pathinfo($path);
		$ext = (isset($pathinfo['extension'])) ? '.'.$pathinfo['extension'] : '';
		$name = $pathinfo['filename'];
		$dir = $pathinfo['dirname'];
		$i = 2;
		while ($view->file_exists($path) || in_array($path, $excludeList)) {
			$path = \OC\Files\Filesystem::normalizePath($dir . '/' . $name . ' ('.$i.')' . $ext);
			$i++;
		}

		return $path;
	}

	/**
	 * allow users from other ownCloud instances to mount public links share by this instance
	 * @return bool
	 */
	public static function isOutgoingServer2serverShareEnabled() {
		$appConfig = \OC::$server->getAppConfig();
		$result = $appConfig->getValue('files_sharing', 'outgoing_server2server_share_enabled', 'yes');
		return ($result === 'yes') ? true : false;
	}

	/**
	 * allow user to mount public links from onther ownClouds
	 * @return bool
	 */
	public static function isIncomingServer2serverShareEnabled() {
		$appConfig = \OC::$server->getAppConfig();
		$result = $appConfig->getValue('files_sharing', 'incoming_server2server_share_enabled', 'yes');
		return ($result === 'yes') ? true : false;
	}

	/**
	 * get default share folder
	 *
	 * @return string
	 */
	public static function getShareFolder() {
		$shareFolder = \OC::$server->getConfig()->getSystemValue('share_folder', '/');
		$shareFolder = \OC\Files\Filesystem::normalizePath($shareFolder);

		if (!\OC\Files\Filesystem::file_exists($shareFolder)) {
			$dir = '';
			$subdirs = explode('/', $shareFolder);
			foreach ($subdirs as $subdir) {
				$dir = $dir . '/' . $subdir;
				if (!\OC\Files\Filesystem::is_dir($dir)) {
					\OC\Files\Filesystem::mkdir($dir);
				}
			}
		}

		return $shareFolder;

	}

	/**
	 * set default share folder
	 *
	 * @param string $shareFolder
	 */
	public static function setShareFolder($shareFolder) {
		\OC::$server->getConfig()->setSystemValue('share_folder', $shareFolder);
	}

	/**
	 * remove protocol from URL
	 *
	 * @param string $url
	 * @return string
	 */
	public static function removeProtocolFromUrl($url) {
		if (strpos($url, 'https://') === 0) {
			return substr($url, strlen('https://'));
		} else if (strpos($url, 'http://') === 0) {
			return substr($url, strlen('http://'));
		}

		return $url;
	}

}
