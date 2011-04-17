<?php

OC_APP::register( array( "order" => 1, "id" => "help", "name" => "Help" ));

// Workaround for having help as the last entry always
$entry = array( "id" => "help", "order" => 1000, "href" => OC_HELPER::linkTo( "help", "index.php" ), "name" => "Help", "icon" => OC_HELPER::imagePath( "help", "help.png" ));
if( OC_GROUP::inGroup( $_SESSION["user_id"], "admin" )){
	OC_APP::addAdminPage( $entry );
}
else{
	OC_APP::addSettingsPage( $entry );
}

?>
