<?php

namespace OCA\Files_Sharing;

class Helper {

	/**
	 * Sets up the filesystem and user for public sharing
	 * @param string $token string share token
	 * @param string $relativePath optional path relative to the share
	 * @param string $password optional password
	 */
	public static function setupFromToken($token, $relativePath = null, $password = null) {
		\OC_User::setIncognitoMode(true);

		$linkItem = \OCP\Share::getShareByToken($token);
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

		$type = $linkItem['item_type'];
		$fileSource = $linkItem['file_source'];
		$shareOwner = $linkItem['uid_owner'];
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
		$rootName = basename($path);

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
	 * @return true if authorized, false otherwise
	 */
	public static function authenticate($linkItem, $password) {
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
					\OC::$session->set('public_link_authenticated', $linkItem['id']);
				}
			} else {
				\OCP\Util::writeLog('share', 'Unknown share type '.$linkItem['share_type']
					.' for share id '.$linkItem['id'], \OCP\Util::ERROR);
				return false;
			}

		}
		else {
			// not authenticated ?
			if ( ! \OC::$session->exists('public_link_authenticated')
				|| \OC::$session->get('public_link_authenticated') !== $linkItem['id']) {
				return false;
			}
		}
		return true;
	}
}
