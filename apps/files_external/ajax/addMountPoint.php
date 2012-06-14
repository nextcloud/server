<?php

OCP\JSON::checkAppEnabled('files_external');
if ($_POST['isPersonal'] == 'true') {
	OCP\JSON::checkLoggedIn();
	$isPersonal = true;
} else {
	OCP\JSON::checkAdminUser();
	$isPersonal = false;
}
OC_Mount_Config::addMountPoint($_POST['mountPoint'], $_POST['class'], $_POST['classOptions'], $_POST['mountType'], $_POST['applicable'], $isPersonal);

?>
