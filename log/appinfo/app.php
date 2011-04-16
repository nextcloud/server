<?php

OC_APP::register( array( "order" => 1, "id" => "log", "name" => "Log" ));
OC_APP::addSettingsPage( array( "order" => 2, "href" => OC_HELPER::linkTo( "log", "index.php" ), "name" => "Log", "icon" => OC_HELPER::imagePath( "admin", "navicon.png" )));

?>
