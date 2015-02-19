<?php

OCP\JSON::checkAppEnabled('files_external');
OCP\JSON::callCheck();

if ($_POST['isPersonal'] == 'true') {
	OCP\JSON::checkLoggedIn();
	$isPersonal = true;
} else {
	OCP\JSON::checkAdminUser();
	$isPersonal = false;
}

$mountPoint = (string)$_POST['mountPoint'];
$oldMountPoint = (string)$_POST['oldMountPoint'];
$class = (string)$_POST['class'];
$options = (string)$_POST['classOptions'];
$type = (string)$_POST['mountType'];
$applicable = (string)$_POST['applicable'];

if ($oldMountPoint and $oldMountPoint !== $mountPoint) {
	OC_Mount_Config::removeMountPoint($oldMountPoint, $type, $applicable, $isPersonal);
}

$status = OC_Mount_Config::addMountPoint($mountPoint, $class, $options, $type, $applicable, $isPersonal);
OCP\JSON::success(array('data' => array('message' => $status)));
