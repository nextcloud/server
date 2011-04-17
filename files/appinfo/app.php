<?php

OC_APP::register( array( "order" => 2, "id" => "files", "name" => "Files" ));

OC_APP::addNavigationEntry( array( "id" => "files_index", "order" => 1, "href" => OC_HELPER::linkTo( "files", "index.php" ), "icon" => OC_HELPER::imagePath( "files", "navicon.png" ), "name" => "Files" ));
OC_APP::addSettingsPage( array( "id" => "files_administration", "order" => 1, "href" => OC_HELPER::linkTo( "files", "admin.php" ), "name" => "Files" ));

// To add navigation sub entries use
// OC_APP::addNavigationSubEntry( "files_index", array( ... ));

?>
