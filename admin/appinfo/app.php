<?php

OC_APP::register( array( "order" => 1, "id" => "admin", "name" => "Administration" ));
if( OC_GROUP::inGroup( $_SESSION['user_id'], 'admin' ))
{
	OC_APP::addNavigationEntry( array( "id" => "admin_index", "order" => 1, "href" => OC_HELPER::linkTo( "admin", "index.php" ), "icon" => OC_HELPER::imagePath( "admin", "navicon.png" ), "name" => "Administration" ));
}
OC_APP::addAdminPage( array( "id" => "core_basic", "order" => 1, "href" => OC_HELPER::linkTo( "admin", "basic.php" ), "name" => "Basic Settings" ));
OC_APP::addAdminPage( array( "id" => "core_system", "order" => 2, "href" => OC_HELPER::linkTo( "admin", "system.php" ), "name" => "System settings" ));
OC_APP::addAdminPage( array( "id" => "core_users", "order" => 3, "href" => OC_HELPER::linkTo( "admin", "users.php" ), "name" => "Users" ));
OC_APP::addAdminPage( array( "id" => "core_apps", "order" => 4, "href" => OC_HELPER::linkTo( "admin", "apps.php" ), "name" => "Apps" ));
OC_APP::addAdminPage( array( "id" => "core_plugins", "order" => 5, "href" => OC_HELPER::linkTo( "admin", "plugins.php" ), "name" => "Plugins" ));

?>
