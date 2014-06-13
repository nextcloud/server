<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OCP\JSON::callCheck();
OCP\JSON::checkLoggedIn();

$l = OC_L10N::get('files_sharing');

// check if server admin allows to mount public links from other servers
// check if files_external is enabled
// FIXME file_external check no longer needed if we use the webdav implementation from core
if (OCA\Files_Sharing\Helper::isIncomingServer2serverShareEnabled() === false ||
		\OC_App::isEnabled('files_external') === false) {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Server to server sharing is not enabled on this server'))));
	exit();
}

$token = $_POST['token'];
$remote = $_POST['remote'];
$owner = $_POST['owner'];
$name = $_POST['name'];
$password = $_POST['password'];

$externalManager = new \OCA\Files_Sharing\External\Manager(
	\OC::$server->getDatabaseConnection(),
	\OC\Files\Filesystem::getMountManager(),
	\OC\Files\Filesystem::getLoader(),
	\OC::$server->getUserSession()
);

$name = OCP\Files::buildNotExistingFileName('/', $name);

$mount = $externalManager->addShare($remote, $token, $password, $name, $owner);
/**
 * @var \OCA\Files_Sharing\External\Storage $storage
 */
$storage = $mount->getStorage();
$result = $storage->file_exists('');
if($result){
	$storage->getScanner()->scanAll();
	\OCP\JSON::success();
} else {
	$externalManager->removeShare($mount->getMountPoint());
	\OCP\JSON::error(array('data' => array('message' => $l->t("Couldn't add remote share"))));
}
