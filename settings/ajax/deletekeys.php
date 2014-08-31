<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$l = \OC::$server->getL10N('settings');
$user = \OC_User::getUser();
$view = new \OC\Files\View('/' . $user . '/files_encryption');

$keyfilesDeleted = $view->deleteAll('keyfiles.backup');
$sharekeysDeleted = $view->deleteAll('share-keys.backup');

if ($keyfilesDeleted && $sharekeysDeleted) {
	\OCP\JSON::success(array('data' => array('message' => $l->t('Encryption keys deleted permanently'))));
} else {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Couldn\'t permanently delete your encryption keys, please check your owncloud.log or ask your administrator'))));
}
