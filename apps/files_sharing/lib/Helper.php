<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
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

use OC\Files\Filesystem;
use OC\Files\View;
use OCP\Files\NotFoundException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\User;

class Helper {

	public static function registerHooks() {
		\OCP\Util::connectHook('OC_Filesystem', 'post_rename', '\OCA\Files_Sharing\Updater', 'renameHook');
		\OCP\Util::connectHook('OC_Filesystem', 'post_delete', '\OCA\Files_Sharing\Hooks', 'unshareChildren');
		\OCP\Util::connectHook('OC_Appconfig', 'post_set_value', '\OCA\Files_Sharing\Maintainer', 'configChangeHook');

		\OCP\Util::connectHook('OC_User', 'post_deleteUser', '\OCA\Files_Sharing\Hooks', 'deleteUser');
	}

	/**
	 * Sets up the filesystem and user for public sharing
	 * @param string $token string share token
	 * @param string $relativePath optional path relative to the share
	 * @param string $password optional password
	 * @return array
	 */
	public static function setupFromToken($token, $relativePath = null, $password = null) {
		\OC_User::setIncognitoMode(true);

		$shareManager = \OC::$server->getShareManager();

		try {
			$share = $shareManager->getShareByToken($token);
		} catch (ShareNotFound $e) {
			\OC_Response::setStatus(404);
			\OCP\Util::writeLog('core-preview', 'Passed token parameter is not valid', \OCP\Util::DEBUG);
			exit;
		}

		\OCP\JSON::checkUserExists($share->getShareOwner());
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($share->getShareOwner());


		try {
			$path = Filesystem::getPath($share->getNodeId());
		} catch (NotFoundException $e) {
			\OCP\Util::writeLog('share', 'could not resolve linkItem', \OCP\Util::DEBUG);
			\OC_Response::setStatus(404);
			\OCP\JSON::error(array('success' => false));
			exit();
		}

		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK && $share->getPassword() !== null) {
			if (!self::authenticate($share, $password)) {
				\OC_Response::setStatus(403);
				\OCP\JSON::error(array('success' => false));
				exit();
			}
		}

		$basePath = $path;

		if ($relativePath !== null && Filesystem::isReadable($basePath . $relativePath)) {
			$path .= Filesystem::normalizePath($relativePath);
		}

		return array(
			'share' => $share,
			'basePath' => $basePath,
			'realPath' => $path
		);
	}

	/**
	 * Authenticate link item with the given password
	 * or with the session if no password was given.
	 * @param \OCP\Share\IShare $share
	 * @param string $password optional password
	 *
	 * @return boolean true if authorized, false otherwise
	 */
	public static function authenticate($share, $password = null) {
		$shareManager = \OC::$server->getShareManager();

		if ($password !== null) {
			if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK) {
				if ($shareManager->checkPassword($share, $password)) {
					\OC::$server->getSession()->set('public_link_authenticated', (string)$share->getId());
					return true;
				}
			}
		} else {
			// not authenticated ?
			if (\OC::$server->getSession()->exists('public_link_authenticated')
				&& \OC::$server->getSession()->get('public_link_authenticated') !== (string)$share->getId()) {
				return true;
			}
		}
		return false;
	}

	public static function getSharesFromItem($target) {
		$result = array();
		$owner = Filesystem::getOwner($target);
		Filesystem::initMountPoints($owner);
		$info = Filesystem::getFileInfo($target);
		$ownerView = new View('/'.$owner.'/files');
		if ( $owner != User::getUser() ) {
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

	/**
	 * get the UID of the owner of the file and the path to the file relative to
	 * owners files folder
	 *
	 * @param $filename
	 * @return array
	 * @throws \OC\User\NoUserException
	 */
	public static function getUidAndFilename($filename) {
		$uid = Filesystem::getOwner($filename);
		$userManager = \OC::$server->getUserManager();
		// if the user with the UID doesn't exists, e.g. because the UID points
		// to a remote user with a federated cloud ID we use the current logged-in
		// user. We need a valid local user to create the share
		if (!$userManager->userExists($uid)) {
			$uid = User::getUser();
		}
		Filesystem::initMountPoints($uid);
		if ( $uid != User::getUser() ) {
			$info = Filesystem::getFileInfo($filename);
			$ownerView = new View('/'.$uid.'/files');
			try {
				$filename = $ownerView->getPath($info['fileid']);
			} catch (NotFoundException $e) {
				$filename = null;
			}
		}
		return [$uid, $filename];
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
	 * @param View $view
	 * @return string $path
	 */
	public static function generateUniqueTarget($path, $excludeList, $view) {
		$pathinfo = pathinfo($path);
		$ext = (isset($pathinfo['extension'])) ? '.'.$pathinfo['extension'] : '';
		$name = $pathinfo['filename'];
		$dir = $pathinfo['dirname'];
		$i = 2;
		while ($view->file_exists($path) || in_array($path, $excludeList)) {
			$path = Filesystem::normalizePath($dir . '/' . $name . ' ('.$i.')' . $ext);
			$i++;
		}

		return $path;
	}

	/**
	 * get default share folder
	 *
	 * @param \OC\Files\View
	 * @return string
	 */
	public static function getShareFolder($view = null) {
		if ($view === null) {
			$view = Filesystem::getView();
		}
		$shareFolder = \OC::$server->getConfig()->getSystemValue('share_folder', '/');
		$shareFolder = Filesystem::normalizePath($shareFolder);

		if (!$view->file_exists($shareFolder)) {
			$dir = '';
			$subdirs = explode('/', $shareFolder);
			foreach ($subdirs as $subdir) {
				$dir = $dir . '/' . $subdir;
				if (!$view->is_dir($dir)) {
					$view->mkdir($dir);
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

}
