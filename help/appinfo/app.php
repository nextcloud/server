<?php

OC_App::register( array( "order" => 1, "id" => "help", "name" => "Help" ));

// Workaround for having help as the last entry always
$entry = array( "id" => "help", "order" => 1000, "href" => OC_Helper::linkTo( "help", "index.php" ), "name" => "Help", "icon" => OC_Helper::imagePath( "help", "help.png" ));
if( isset( $_SESSION["user_id"] ) && OC_Group::inGroup( $_SESSION["user_id"], "admin" )){
	OC_App::addAdminPage( $entry );
}
else{
	OC_App::addSettingsPage( $entry );
}

?>
