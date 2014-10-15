<?php

namespace OCA\Files_Sharing;

use OC_Config;
use PasswordHash;

class Helper {

	public static function registerHooks() {
		\OCP\Util::connectHook('OC_Filesystem', 'setup', '\OC\Files\Storage\Shared', 'setup');
		\OCP\Util::connectHook('OC_Filesystem', 'setup', '\OCA\Files_Sharing\External\Manager', 'setup');
		\OCP\Util::connectHook('OC_Filesystem', 'post_write', '\OC\Files\Cache\Shared_Updater', 'writeHook');
		\OCP\Util::connectHook('OC_Filesystem', 'post_delete', '\OC\Files\Cache\Shared_Updater', 'postDeleteHook');
		\OCP\Util::connectHook('OC_Filesystem', 'delete', '\OC\Files\Cache\Shared_Updater', 'deleteHook');
		\OCP\Util::connectHook('OC_Filesystem', 'post_rename', '\OC\Files\Cache\Shared_Updater', 'renameHook');
		\OCP\Util::connectHook('OC_Appconfig', 'post_set_value', '\OCA\Files\Share\Maintainer', 'configChangeHook');

		\OCP\Util::connectHook('OCP\Share', 'post_shared', '\OC\Files\Cache\Shared_Updater', 'postShareHook');
		\OCP\Util::connectHook('OCP\Share', 'post_unshare', '\OC\Files\Cache\Shared_Updater', 'postUnshareHook');
		\OCP\Util::connectHook('OCP\Share', 'post_unshareFromSelf', '\OC\Files\Cache\Shared_Updater', 'postUnshareFromSelfHook');
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
			\OC_Log::write('core-preview', 'Passed token parameter is not valid', \OC_Log::DEBUG);
			exit;
		}

		if(!isset($linkItem['uid_owner']) || !isset($linkItem['file_source'])) {
			\OC_Response::setStatus(500);
			\OC_Log::write('core-preview', 'Passed token seems to be valid, but it does not contain all necessary information . ("' . $token . '")', \OC_Log::WARN);
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

		if (isset($linkItem['share_with'])) {
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
				$forcePortable = (CRYPT_BLOWFISH != 1);
				$hasher = new PasswordHash(8, $forcePortable);
				if (!($hasher->CheckPassword($password.OC_Config::getValue('passwordsalt', ''),
											 $linkItem['share_with']))) {
					return false;
				} else {
					// Save item id in session for future requests
					\OC::$server->getSession()->set('public_link_authenticated', $linkItem['id']);
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
		$shareFolder = \OCP\Config::getSystemValue('share_folder', '/');

		return \OC\Files\Filesystem::normalizePath($shareFolder);
	}

	/**
	 * set default share folder
	 *
	 * @param string $shareFolder
	 */
	public static function setShareFolder($shareFolder) {
		\OCP\Config::setSystemValue('share_folder', $shareFolder);
	}

}
