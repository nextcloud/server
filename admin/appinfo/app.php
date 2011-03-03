<?php

OC_APP::register( array( "order" => 1, "id" => "admin", "name" => "Administration" ));
if( OC_USER::ingroup( $_SESSION['username'], 'admin' ))
{
	OC_UTIL::addNavigationEntry( array( "id" => "admin_index", "order" => 1, "href" => OC_HELPER::linkTo( "admin", "index.php" ), "icon" => OC_HELPER::imagePath( "admin", "navicon.png" ), "name" => "Administration" ));
}
OC_UTIL::addAdminPage( array( "order" => 1, "href" => OC_HELPER::linkTo( "admin", "system.php" ), "name" => "System settings" ));
OC_UTIL::addAdminPage( array( "order" => 2, "href" => OC_HELPER::linkTo( "admin", "users.php" ), "name" => "Users" ));
OC_UTIL::addAdminPage( array( "order" => 3, "href" => OC_HELPER::linkTo( "admin", "plugins.php" ), "name" => "Plugins" ));

?>
