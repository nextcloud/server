<?php

OC_APP::register( array( "order" => 1, "id" => "admin", "name" => "Administration" ));

OC_APP::addAdminPage( array( "id" => "core_system", "order" => 1, "href" => OC_HELPER::linkTo( "admin", "system.php" ), "name" => "System settings", "icon" => OC_HELPER::imagePath( "admin", "navicon.png" )));
OC_APP::addAdminPage( array( "id" => "core_users", "order" => 2, "href" => OC_HELPER::linkTo( "admin", "users.php" ), "name" => "Users", "icon" => OC_HELPER::imagePath( "admin", "navicon.png" )));
OC_APP::addAdminPage( array( "id" => "core_apps", "order" => 3, "href" => OC_HELPER::linkTo( "admin", "apps.php" ), "name" => "Apps", "icon" => OC_HELPER::imagePath( "admin", "navicon.png" )));
OC_APP::addAdminPage( array( "id" => "core_plugins", "order" => 4, "href" => OC_HELPER::linkTo( "admin", "plugins.php" ), "name" => "Plugins", "icon" => OC_HELPER::imagePath( "admin", "navicon.png" )));

// Add subentries for App installer
OC_APP::addNavigationSubEntry( "core_apps", array( "id" => "core_apps_installed", "order" => 4, "href" => OC_HELPER::linkTo( "admin", "apps.php?add=some&parameters=here" ), "name" => "Installed apps", "icon" => OC_HELPER::imagePath( "admin", "navicon.png" )));

?>
