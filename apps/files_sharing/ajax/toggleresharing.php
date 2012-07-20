<?php

OCP\JSON::callCheck();

OCP\JSON::checkAppEnabled('files_sharing');
OCP\JSON::checkAdminUser();
if ($_POST['resharing'] == true) {
	OCP\Config::setAppValue('files_sharing', 'resharing', 'yes');
} else {
	OCP\Config::setAppValue('files_sharing', 'resharing', 'no');
}

?>
