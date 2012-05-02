<?php

OC_JSON::checkAppEnabled('files_sharing');
OC_JSON::checkAdminUser();
if ($_POST['resharing'] == true) {
	OCP\Config::setAppValue('files_sharing', 'resharing', 'yes');
} else {
	OCP\Config::setAppValue('files_sharing', 'resharing', 'no');
}

?>
