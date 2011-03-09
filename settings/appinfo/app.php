<?php

OC_APP::register( array( "id" => "settings", "name" => "Settings" ));
OC_UTIL::addPersonalMenuEntry( array( "order" => 1, "href" => OC_HELPER::linkTo( "settings", "index.php" ), "name" => "Settings" ));

?>
