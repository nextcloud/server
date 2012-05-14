<?php

require_once('apps/files_sharing/sharedstorage.php');

OC::$CLASSPATH['OC_Share'] = "apps/files_sharing/lib_share.php";
OCP\App::registerAdmin('files_sharing', 'settings');
OCP\Util::connectHook("OC_Filesystem", "post_delete", "OC_Share", "deleteItem");
OCP\Util::connectHook("OC_Filesystem", "post_rename", "OC_Share", "renameItem");
OCP\Util::connectHook("OC_Filesystem", "post_write", "OC_Share", "updateItem");
OCP\Util::connectHook('OC_User', 'post_deleteUser', 'OC_Share', 'removeUser');
OCP\Util::connectHook('OC_User', 'post_addToGroup', 'OC_Share', 'addToGroupShare');
OCP\Util::connectHook('OC_User', 'post_removeFromGroup', 'OC_Share', 'removeFromGroupShare');
$dir = isset($_GET['dir']) ? $_GET['dir'] : '/';
if ($dir != '/Shared' || OCP\Config::getAppValue('files_sharing', 'resharing', 'yes') == 'yes') {
	OCP\Util::addscript("files_sharing", "share");
}
OCP\Util::addscript("3rdparty", "chosen/chosen.jquery.min");
OCP\Util::addStyle( 'files_sharing', 'sharing' );
OCP\Util::addStyle("3rdparty", "chosen/chosen");
