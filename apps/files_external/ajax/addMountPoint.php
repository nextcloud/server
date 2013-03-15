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
$status = OC_Mount_Config::addMountPoint($_POST['mountPoint'],
							   $_POST['class'],
							   $_POST['classOptions'],
							   $_POST['mountType'],
							   $_POST['applicable'],
							   $isPersonal);
OCP\JSON::success(array('data' => array('message' => $status)));