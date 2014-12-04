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
		\OC::$server->getUserSession(),
		\OC::$server->getHTTPHelper()
);

$name = OCP\Files::buildNotExistingFileName('/', $name);

// check for ssl cert
if (substr($remote, 0, 5) === 'https' and !OC_Util::getUrlContent($remote)) {
	\OCP\JSON::error(array('data' => array('message' => $l->t("Invalid or untrusted SSL certificate"))));
	exit;
} else {
	$mount = $externalManager->addShare($remote, $token, $password, $name, $owner, true);
	/**
	 * @var \OCA\Files_Sharing\External\Storage $storage
	 */
	$storage = $mount->getStorage();
	$result = $storage->file_exists('');
	if ($result) {
		$storage->getScanner()->scanAll();
		\OCP\JSON::success();
	} else {
		$externalManager->removeShare($mount->getMountPoint());
		\OCP\JSON::error(array('data' => array('message' => $l->t("Couldn't add remote share"))));
	}
}
