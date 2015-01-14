<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$l = \OC::$server->getL10N('settings');

$util = new \OCA\Files_Encryption\Util(new \OC\Files\View(), \OC_User::getUser());
$result = $util->deleteBackup('decryptAll');

if ($result) {
	\OCP\JSON::success(array('data' => array('message' => $l->t('Encryption keys deleted permanently'))));
} else {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Couldn\'t permanently delete your encryption keys, please check your owncloud.log or ask your administrator'))));
}
