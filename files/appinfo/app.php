<?php

OC_App::register( array( "order" => 2, "id" => "files", "name" => "Files" ));

OC_App::addNavigationEntry( array( "id" => "files_index", "order" => 1, "href" => OC_Helper::linkTo( "files", "index.php" ), "icon" => OC_Helper::imagePath( "files", "home.png" ), "name" => "Files" ));
OC_App::addAdminPage( array( "id" => "files_administration", "order" => 3, "href" => OC_Helper::linkTo( "files", "admin.php" ), "name" => "Files", "icon" => OC_Helper::imagePath( "files", "folder.png" )));


// To add navigation sub entries use
// OC_App::addNavigationSubEntry( "files_index", array( ... ));

?>
