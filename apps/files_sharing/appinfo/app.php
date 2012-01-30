<?php

require_once('apps/files_sharing/sharedstorage.php');

OC::$CLASSPATH['OC_Share'] = "apps/files_sharing/lib_share.php";
OC_Hook::connect("OC_Filesystem", "post_delete", "OC_Share", "deleteItem");
OC_Hook::connect("OC_Filesystem", "post_rename", "OC_Share", "renameItem");
OC_Hook::connect("OC_Filesystem", "post_write", "OC_Share", "updateItem");
OC_Filesystem::registerStorageType("shared", "OC_Filestorage_Shared", array("datadir" => "string"));
OC_Util::addScript("files_sharing", "share");
OC_Util::addScript("3rdparty", "chosen/chosen.jquery.min");
OC_Util::addStyle( 'files_sharing', 'sharing' );
OC_Util::addStyle("3rdparty", "chosen/chosen");

?>