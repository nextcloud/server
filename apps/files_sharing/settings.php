<?php

OCP\User::checkAdminUser();
OCP\Util::addscript('files_sharing', 'settings');
$tmpl = new OC_Template('files_sharing', 'settings');
$tmpl->assign('allowResharing', OC_Appconfig::getValue('files_sharing', 'resharing', 'yes'));
return $tmpl->fetchPage();

?>