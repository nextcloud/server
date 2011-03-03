<?php
/*
 * This file is required. It makes owncloud aware of the app.
 */

// Hello, we are here
OC_APP::register( array( "id" => "skeleton", "name" => "Files", "order" => 1000 ));

// Add application to navigation
OC_UTIL::addNavigationEntry( array( "id" => "skeleton_index", "order" => 1000, "href" => OC_HELPER::linkTo( "skeleton", "index.php" ), "icon" => OC_HELPER::imagePath( "skeleton", "app.png" ), "name" => "Example app" ));

// Add an admin page
OC_UTIL::addAdminPage( array( "order" => 1, "href" => OC_HELPER::linkTo( "skeleton", "admin.php" ), "name" => "Example app options" ));

?>
