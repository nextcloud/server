<?php
$l = OC_L10N::get('files');

OCP\App::registerAdmin('files', 'admin');

OCP\App::addNavigationEntry( array( "id" => "files_index",
									"order" => 0,
									"href" => OCP\Util::linkTo( "files", "index.php" ),
									"icon" => OCP\Util::imagePath( "core", "places/home.svg" ),
									"name" => $l->t("Files") ));

OC_Search::registerProvider('OC_Search_Provider_File');

if (OC_User::isLoggedIn()) {
	// update OC4.5 filecache to OC5 filecache, can't do this in update.php since it needs to happen for each user individually
	$cacheVersion = (int)OCP\Config::getUserValue(OC_User::getUser(), 'files', 'cache_version', 4);
	if ($cacheVersion < 5) {
		\OC_Log::write('files', 'updating filecache to 5.0 for user ' . OC_User::getUser(), \OC_Log::INFO);
		\OC\Files\Cache\Upgrade::upgrade();
		OCP\Config::setUserValue(OC_User::getUser(), 'files', 'cache_version', 5);
	}
}
