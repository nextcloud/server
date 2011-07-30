<?php

require_once('apps/files_sharing/lib_share.php');

OC_Util::addScript("files_sharing", "share");
OC_App::addNavigationSubEntry("files_index", array(
  "id" => "files_sharing_list",
  "order" => 10, 
  "href" => OC_Helper::linkTo( "files_sharing", "list.php" ),
  "name" => "Shared"));

?>
