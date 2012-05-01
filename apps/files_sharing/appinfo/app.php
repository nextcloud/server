<?php

require_once('apps/files_sharing/sharedstorage.php');

OC::$CLASSPATH['OC_Share'] = "apps/files_sharing/lib_share.php";
OC_APP::registerAdmin('files_sharing', 'settings');
OC_Hook::connect("OC_Filesystem", "post_delete", "OC_Share", "deleteItem");
OC_Hook::connect("OC_Filesystem", "post_rename", "OC_Share", "renameItem");
OC_Hook::connect("OC_Filesystem", "post_write", "OC_Share", "updateItem");
OC_Hook::connect('OC_User', 'post_deleteUser', 'OC_Share', 'removeUser');
OC_Hook::connect('OC_User', 'post_addToGroup', 'OC_Share', 'addToGroupShare');
OC_Hook::connect('OC_User', 'post_removeFromGroup', 'OC_Share', 'removeFromGroupShare');
$dir = isset($_GET['dir']) ? $_GET['dir'] : '/';
if ($dir != '/Shared' || OC_Appconfig::getValue('files_sharing', 'resharing', 'yes') == 'yes') {
	OC_Util::addScript("files_sharing", "share");
}
OC_Util::addScript("3rdparty", "chosen/chosen.jquery.min");
OCP\Util::addStyle( 'files_sharing', 'sharing' );
OCP\Util::addStyle("3rdparty", "chosen/chosen");

?>
