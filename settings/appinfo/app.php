<?php

OC_App::register( array( "id" => "settings", "name" => "Settings" ));
OC_App::addSettingsPage( array( "id" => "settings", "order" => -1000, "href" => OC_Helper::linkTo( "settings", "index.php" ), "name" => "Personal", "icon" => OC_Helper::imagePath( "settings", "personal.png" )));

?>
