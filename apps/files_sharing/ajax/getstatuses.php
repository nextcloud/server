<?php
require_once(OC::$APPSROOT . '/apps/files_sharing/lib_share.php');

OCP\JSON::checkAppEnabled('files_sharing');
OCP\JSON::checkLoggedIn();

$items = array();
$userDirectory = '/'.OCP\USER::getUser().'/files';
$dirLength = strlen($userDirectory);
if ($rows = OC_Share::getMySharedItems()) {
	for ($i = 0; $i < count($rows); $i++) {
		$source = $rows[$i]['source'];
		// Strip out user directory
		$item = substr($source, $dirLength);
		if ($rows[$i]['uid_shared_with'] == OC_Share::PUBLICLINK) {
			$items[$item] = true;
		} else if (!isset($items[$item])) {
			$items[$item] = false;
		}
	}
}

OCP\JSON::success(array('data' => $items));

?>