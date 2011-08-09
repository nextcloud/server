<?php

$l=new OC_L10N('files');

OC_App::register( array( "order" => 2, "id" => "files", "name" => "Files" ));

OC_App::addNavigationEntry( array( "id" => "files_index", "order" => 1, "href" => OC_Helper::linkTo( "files", "index.php" ), "icon" => OC_Helper::imagePath( "files", "home.png" ), "name" => $l->t("Files") ));

?>
