<?php

OC_APP::register( array( "order" => 1, "id" => "help", "name" => "Help" ));
OC_APP::addSettingsPage( array( "id" => "help", "order" => 2, "href" => OC_HELPER::linkTo( "help", "index.php" ), "name" => "Help", "icon" => OC_HELPER::imagePath( "settings", "information.png" )));

?>
