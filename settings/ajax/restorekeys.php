<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$l = \OC::$server->getL10N('settings');

$util = new \OCA\Files_Encryption\Util(new \OC\Files\View(), \OC_User::getUser());
$result = $util->restoreBackup('decryptAll');

if ($result) {
	\OCP\JSON::success(array('data' => array('message' => $l->t('Backups restored successfully'))));
} else {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Couldn\'t restore your encryption keys, please check your owncloud.log or ask your administrator'))));
}
