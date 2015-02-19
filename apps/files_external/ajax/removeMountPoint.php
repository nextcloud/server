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

OC_Mount_Config::removeMountPoint((string)$_POST['mountPoint'], (string)$_POST['mountType'], (string)$_POST['applicable'], $isPersonal);
