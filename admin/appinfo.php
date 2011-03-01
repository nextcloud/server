<?php

OC_UTIL::addApplication( array( "id" => "admin", "name" => "Administration" ));
if( OC_USER::ingroup( $_SESSION['username'], 'admin' ))
{
	OC_UTIL::addNavigationEntry( array( "app" => "admin", "file" => "index.php", "name" => "Administration" ));
}
OC_UTIL::addAdminPage( array( "app" => "admin", "file" => "system.php", "name" => "System Settings" ));
OC_UTIL::addAdminPage( array( "app" => "admin", "file" => "users.php", "name" => "Users" ));
OC_UTIL::addAdminPage( array( "app" => "admin", "file" => "plugins.php", "name" => "Plugins" ));

?>
