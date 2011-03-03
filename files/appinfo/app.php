<?php

OC_APP::register( array( "id" => "files", "name" => "Files" ));
OC_UTIL::addNavigationEntry( array( "app" => "files", "file" => "index.php", "name" => "Files" ));
OC_UTIL::addAdminPage( array( "app" => "files", "file" => "admin.php", "name" => "Files" ));

?>
