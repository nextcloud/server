<?php

OC_APP::register( array( "order" => 1, "id" => "admin", "name" => "Administration" ));

OC_APP::addAdminPage( array( "id" => "core_system", "order" => 1, "href" => OC_HELPER::linkTo( "admin", "system.php" ), "name" =>"System", "icon" => OC_HELPER::imagePath( "admin", "administration.png" )));
OC_APP::addAdminPage( array( "id" => "core_users", "order" => 2, "href" => OC_HELPER::linkTo( "admin", "users.php" ), "name" => "Users", "icon" => OC_HELPER::imagePath( "admin", "users.png" )));
OC_APP::addAdminPage( array( "id" => "core_apps", "order" => 3, "href" => OC_HELPER::linkTo( "admin", "apps.php?installed" ), "name" => "Apps", "icon" => OC_HELPER::imagePath( "admin", "apps.png" )));

// Add subentries for App installer
OC_APP::addNavigationSubEntry( "core_apps", array( "id" => "core_apps_get", "order" => 4, "href" => OC_HELPER::linkTo( "admin", "apps.php" ), "name" => "Get new apps", "icon" => OC_HELPER::imagePath( "admin", "navicon.png" )));

?>
