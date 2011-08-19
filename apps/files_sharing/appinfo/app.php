<?php

require_once('apps/files_sharing/lib_share.php');
require_once('apps/files_sharing/sharedstorage.php');

OC_Filesystem::registerStorageType("shared", "OC_Filestorage_Shared", array("datadir"=>"string"));
OC_Util::addScript("files_sharing", "share");
OC_Util::addScript("3rdparty", "chosen/chosen.jquery.min");
OC_Util::addStyle( 'files_sharing', 'sharing' );
OC_Util::addStyle("3rdparty", "chosen/chosen");

?>