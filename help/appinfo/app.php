<?php

OC_APP::register( array( "id" => "help", "name" => "Help" ));
OC_APP::addPersonalMenuEntry( array( "order" => 2, "href" => OC_HELPER::linkTo( "help", "index.php" ), "name" => "Help" ));

?>
