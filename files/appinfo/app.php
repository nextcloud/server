<?php

OC_APP::register( array( "order" => 2, "id" => "files", "name" => "Files" ));

OC_UTIL::addNavigationEntry( array( "id" => "files_index", "order" => 1, "href" => OC_HELPER::linkTo( "files", "index.php" ), "icon" => OC_HELPER::imagePath( "files", "navicon.png" ), "name" => "Files" ));
OC_UTIL::addAdminPage( array( "order" => 1, "href" => OC_HELPER::linkTo( "files", "admin.php" ), "name" => "Files" ));

?>
