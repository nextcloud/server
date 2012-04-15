<?php


$l=OC_L10N::get('files');

OC_App::register( array( "order" => 2, "id" => "files", "name" => "Files" ));

OC_App::addNavigationEntry( array( "id" => "files_index", "order" => 0, "href" => OC_Helper::linkTo( "files", "index.php" ), "icon" => OC_Helper::imagePath( "core", "places/home.svg" ), "name" => $l->t("Files") ));

OC_Search::registerProvider('OC_Search_Provider_File');
