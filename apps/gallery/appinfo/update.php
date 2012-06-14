<?php

$currentVersion=OC_Appconfig::getValue('gallery', 'installed_version');
if (version_compare($currentVersion, '0.5.0', '<')) {
	$stmt = OCP\DB::prepare('DROP TABLE IF EXISTS *PREFIX*gallery_photos');
	$stmt->execute();
	$stmt = OCP\DB::prepare('DROP TABLE IF EXISTS *PREFIX*gallery_albums');
	$stmt->execute();

	\OC_DB::createDbFromStructure(OC::$APPSROOT.'/apps/'.$appid.'/appinfo/database.xml');
}
