<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OCP\JSON::callCheck();
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_sharing');

$l = \OC::$server->getL10N('files_sharing');

// check if server admin allows to mount public links from other servers
if (OCA\Files_Sharing\Helper::isIncomingServer2serverShareEnabled() === false) {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Server to server sharing is not enabled on this server'))));
	exit();
}

$token = $_POST['token'];
$remote = $_POST['remote'];
$owner = $_POST['owner'];
$name = $_POST['name'];
$password = $_POST['password'];

// Check for invalid name
if(!\OCP\Util::isValidFileName($name)) {
	\OCP\JSON::error(array('data' => array('message' => $l->t('The mountpoint name contains invalid characters.'))));
	exit();
}

$externalManager = new \OCA\Files_Sharing\External\Manager(
		\OC::$server->getDatabaseConnection(),
		\OC\Files\Filesystem::getMountManager(),
		\OC\Files\Filesystem::getLoader(),
		\OC::$server->getHTTPHelper(),
		\OC::$server->getUserSession()->getUser()->getUID()
);

$name = OCP\Files::buildNotExistingFileName('/', $name);

// check for ssl cert
if (substr($remote, 0, 5) === 'https' and !OC_Util::getUrlContent($remote)) {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Invalid or untrusted SSL certificate'))));
	exit;
} else {
	$mount = $externalManager->addShare($remote, $token, $password, $name, $owner, true);

	/**
	 * @var \OCA\Files_Sharing\External\Storage $storage
	 */
	$storage = $mount->getStorage();
	try {
		// check if storage exists
		$storage->checkStorageAvailability();
	} catch (\OCP\Files\StorageInvalidException $e) {
		// note: checkStorageAvailability will already remove the invalid share
		\OCP\Util::writeLog(
			'files_sharing',
			'Invalid remote storage: ' . get_class($e) . ': ' . $e->getMessage(),
			\OCP\Util::DEBUG
		);
		\OCP\JSON::error(
			array(
				'data' => array(
					'message' => $l->t('Could not authenticate to remote share, password might be wrong')
				)
			)
		);
		exit();
	} catch (\Exception $e) {
		\OCP\Util::writeLog(
			'files_sharing',
			'Invalid remote storage: ' . get_class($e) . ': ' . $e->getMessage(),
			\OCP\Util::DEBUG
		);
		$externalManager->removeShare($mount->getMountPoint());
		\OCP\JSON::error(array('data' => array('message' => $l->t('Storage not valid'))));
		exit();
	}
	$result = $storage->file_exists('');
	if ($result) {
		try {
			$storage->getScanner()->scanAll();
			\OCP\JSON::success();
		} catch (\OCP\Files\StorageInvalidException $e) {
			\OCP\Util::writeLog(
				'files_sharing',
				'Invalid remote storage: ' . get_class($e) . ': ' . $e->getMessage(),
				\OCP\Util::DEBUG
			);
			\OCP\JSON::error(array('data' => array('message' => $l->t('Storage not valid'))));
		} catch (\Exception $e) {
			\OCP\Util::writeLog(
				'files_sharing',
				'Invalid remote storage: ' . get_class($e) . ': ' . $e->getMessage(),
				\OCP\Util::DEBUG
			);
			\OCP\JSON::error(array('data' => array('message' => $l->t('Couldn\'t add remote share'))));
		}
	} else {
		$externalManager->removeShare($mount->getMountPoint());
		\OCP\Util::writeLog(
			'files_sharing',
			'Couldn\'t add remote share',
			\OCP\Util::DEBUG
		);
		\OCP\JSON::error(array('data' => array('message' => $l->t('Couldn\'t add remote share'))));
	}
}
