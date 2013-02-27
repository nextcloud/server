<?php
OCP\JSON::checkAppEnabled('files_versions');

$source = $_GET['source'];
list ($uid, $filename) = OCA\Files_Versions\Storage::getUidAndFilename($source);
$count = 5; //show the newest revisions
if( ($versions = OCA\Files_Versions\Storage::getVersions($uid, $filename, $count)) ) {

	$versionsFormatted = array();

	foreach ( $versions AS $version ) {
		$versionsFormatted[] = OCP\Util::formatDate( $version['version'] );
	}

	$versionsSorted = array_reverse( $versions );

	if ( !empty( $versionsSorted ) ) {
		OCP\JSON::encodedPrint($versionsSorted);
	}

} else {

	return;

}
