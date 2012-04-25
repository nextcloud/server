<?php

OC_Util::checkAdminUser();
OC_Util::addScript('files_sharing', 'settings');
$tmpl = new OC_Template('files_sharing', 'settings');
$tmpl->assign('allowResharing', OC_Appconfig::getValue('files_sharing', 'resharing', 'yes'));
return $tmpl->fetchPage();

?>