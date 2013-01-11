<?php
OCP\JSON::checkAppEnabled('files_versions');

$userDirectory = "/".OCP\USER::getUser()."/files";
$source = $_GET['source'];

$count = 5; //show the newest revisions
if( ($versions = OCA_Versions\Storage::getVersions( $source, $count)) ) {

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
