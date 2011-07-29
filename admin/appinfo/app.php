<?php

OC_App::register( array( "order" => 1, "id" => "admin", "name" => "Administration" ));

// OC_App::addAdminPage( array( "id" => "core_system", "order" => 1, "href" => OC_Helper::linkTo( "admin", "system.php" ), "name" =>"System", "icon" => OC_Helper::imagePath( "admin", "administration.png" )));
OC_App::addAdminPage( array( "id" => "core_users", "order" => 2, "href" => OC_Helper::linkTo( "admin", "users.php" ), "name" => "Users", "icon" => OC_Helper::imagePath( "admin", "users.png" )));
OC_App::addAdminPage( array( "id" => "core_apps", "order" => 3, "href" => OC_Helper::linkTo( "admin", "apps.php?installed" ), "name" => "Apps", "icon" => OC_Helper::imagePath( "admin", "apps.png" )));

// Add subentries for App installer
OC_App::addNavigationSubEntry( "core_apps", array( "id" => "core_apps_get", "order" => 4, "href" => OC_Helper::linkTo( "admin", "apps.php" ), "name" => "Get new apps", "icon" => OC_Helper::imagePath( "admin", "navicon.png" )));

?>
