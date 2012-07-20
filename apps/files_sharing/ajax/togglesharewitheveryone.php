<?php

OCP\JSON::callCheck();

OCP\JSON::checkAppEnabled('files_sharing');
OCP\JSON::checkAdminUser();
if ($_POST['allowSharingWithEveryone'] == true) {
	OCP\Config::setAppValue('files_sharing', 'allowSharingWithEveryone', 'yes');
} else {
	OCP\Config::setAppValue('files_sharing', 'allowSharingWithEveryone', 'no');
}