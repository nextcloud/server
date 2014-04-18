<?php

OCP\JSON::checkAppEnabled('files_external');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

if ($_POST['isPersonal'] == 'true') {
	// Check whether the user has permissions to add personal storage backends
	if(OCP\Config::getAppValue('files_external', 'allow_user_mounting', 'yes') !== 'yes') {
		OCP\JSON::error(array('data' => array('message' => 'no permission')));
		return;
	}
	$isPersonal = true;
} else {
	OCP\JSON::checkAdminUser();
	$isPersonal = false;
}
$status = OC_Mount_Config::addMountPoint($_POST['mountPoint'],
							   $_POST['class'],
							   $_POST['classOptions'],
							   $_POST['mountType'],
							   $_POST['applicable'],
							   $isPersonal);
OCP\JSON::success(array('data' => array('message' => $status)));
