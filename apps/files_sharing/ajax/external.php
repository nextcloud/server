<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OCP\JSON::callCheck();
OCP\JSON::checkLoggedIn();

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

$mount = $externalManager->addShare($remote, $token, $password, $name, $owner);
/**
 * @var \OCA\Files_Sharing\External\Storage $storage
 */
$storage = $mount->getStorage();
$result = $storage->file_exists('');
if($result){
	$storage->getScanner()->scanAll();
}

echo json_encode($result);
