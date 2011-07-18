<?php

require_once('apps/files_sharing/lib_share.php');

OC_APP::addSettingsPage( array( "id" => "files_sharing_administration", 
  "order" => 10, 
  "href" => OC_HELPER::linkTo( "files_sharing", "admin.php" ), 
  "name" => "Share", 
  "icon" => OC_HELPER::imagePath( "files_sharing", "share.png" )));

?>