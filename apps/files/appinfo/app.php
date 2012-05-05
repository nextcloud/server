<?php


$l=OC_L10N::get('files');

OCP\App::register( array( "order" => 2, "id" => "files", "name" => "Files" ));
OCP\App::registerAdmin('files','admin');

OCP\App::addNavigationEntry( array( "id" => "files_index", "order" => 0, "href" => OCP\Util::linkTo( "files", "index.php" ), "icon" => OCP\Util::imagePath( "core", "places/home.svg" ), "name" => $l->t("Files") ));

OC_Search::registerProvider('OC_Search_Provider_File');
if(!file_exists(OC::$SERVERROOT.'/remote/webdav.php')){
	file_put_contents(OC::$SERVERROOT.'/remote/webdav.php', file_get_contents(OC::$APPSROOT . '/apps/files/appinfo/remote.php'));
}