<?php

OCP\User::checkAdminUser();
OCP\Util::addscript('files_sharing', 'settings');
$tmpl = new OCP\Template('files_sharing', 'settings');
$tmpl->assign('allowResharing', OCP\Config::getAppValue('files_sharing', 'resharing', 'yes'));
$tmpl->assign('allowSharingWithEveryone', OCP\Config::getAppValue('files_sharing', 'allowSharingWithEveryone', 'no'));
return $tmpl->fetchPage();

?>