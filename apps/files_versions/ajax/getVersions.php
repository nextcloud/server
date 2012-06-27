<?php
OCP\JSON::checkAppEnabled('files_versions');

require_once('apps/files_versions/versions.php');

$userDirectory = "/".OCP\USER::getUser()."/files";
$source = $_GET['source'];

if( OCA_Versions\Storage::isversioned( $source ) ) {

	$count=5; //show the newest revisions
	$versions = OCA_Versions\Storage::getversions( $source, $count);

	$versionsSorted = array_reverse( $versions );
	
	if ( !empty( $versionsSorted ) ) {
		OCP\JSON::encodedPrint($versionsSorted);
	}
	
} else {

	return;
	
}
