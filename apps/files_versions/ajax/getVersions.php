<?php
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
OCP\JSON::checkAppEnabled('files_versions');

$source = $_GET['source'];
$start = $_GET['start'];
list ($uid, $filename) = OCA\Files_Versions\Storage::getUidAndFilename($source);
$count = 5; //show the newest revisions
$versions = OCA\Files_Versions\Storage::getVersions($uid, $filename, $source);
if( $versions ) {

	$endReached = false;
	if (count($versions) <= $start+$count) {
		$endReached = true;
	}

	$versions = array_slice($versions, $start, $count);

	\OCP\JSON::success(array('data' => array('versions' => $versions, 'endReached' => $endReached)));

} else {

	\OCP\JSON::success(array('data' => array('versions' => false, 'endReached' => true)));

}
