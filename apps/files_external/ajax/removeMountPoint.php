<?php

OCP\JSON::checkAppEnabled('files_external');
OCP\JSON::callCheck();

if (!isset($_POST['isPersonal']))
	return;
if (!isset($_POST['mountPoint']))
	return;
if (!isset($_POST['mountType']))
	return;
if (!isset($_POST['applicable']))
	return;

if ($_POST['isPersonal'] == 'true') {
	OCP\JSON::checkLoggedIn();
	$isPersonal = true;
} else {
	OCP\JSON::checkAdminUser();
	$isPersonal = false;
}

OC_Mount_Config::removeMountPoint($_POST['mountPoint'], $_POST['mountType'], $_POST['applicable'], $isPersonal);
