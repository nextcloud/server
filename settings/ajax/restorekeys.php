<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$l = \OC::$server->getL10N('settings');
$user = \OC_User::getUser();
$view = new \OC\Files\View('/' . $user . '/files_encryption');

$keyfilesRestored = $view->rename('keyfiles.backup', 'keyfiles');
$sharekeysRestored = $view->rename('share-keys.backup' , 'share-keys');

if ($keyfilesRestored && $sharekeysRestored) {
	\OCP\JSON::success(array('data' => array('message' => $l->t('Backups restored successfully'))));
} else {
	// if one of the move operation was succesful we remove the files back to have a consistent state
	if($keyfilesRestored) {
		$view->rename('keyfiles', 'keyfiles.backup');
	}
	if($sharekeysRestored) {
		$view->rename('share-keys' , 'share-keys.backup');
	}
	\OCP\JSON::error(array('data' => array('message' => $l->t('Couldn\'t restore your encryption keys, please check your owncloud.log or ask your administrator'))));
}
