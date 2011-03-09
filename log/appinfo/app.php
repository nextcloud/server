<?php

OC_APP::register( array( "order" => 1, "id" => "log", "name" => "Log" ));
OC_UTIL::addPersonalMenuEntry( array( "order" => 2, "href" => OC_HELPER::linkTo( "log", "index.php" ), "name" => "Log" ));

?>
