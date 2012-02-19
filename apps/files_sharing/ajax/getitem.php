<?php
//$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');
OC_JSON::checkAppEnabled('files_sharing');
require_once('../lib_share.php');

$userDirectory = "/".OC_User::getUser()."/files";
$source = $userDirectory.$_GET['source'];
$path = $source;
$users = array();
if ($users = OC_Share::getMySharedItem($source)) {
	for ($i = 0; $i < count($users); $i++) {
		if ($users[$i]['uid_shared_with'] == OC_Share::PUBLICLINK) {
			$users[$i]['token'] = OC_Share::getTokenFromSource($source);
		}
	}
}
$source = dirname($source);
while ($source != "" && $source != "/" && $source != "." && $source != $userDirectory) {
	if ($values = OC_Share::getMySharedItem($source)) {
		$values = array_values($values);
		$parentUsers = array();
		for ($i = 0; $i < count($values); $i++) {
			if ($values[$i]['uid_shared_with'] == OC_Share::PUBLICLINK) {
				$values[$i]['token'] = OC_Share::getTokenFromSource($source)."&path=".substr($path, strlen($source));
			}
			$parentUsers[basename($source)."-".$i] = $values[$i];
		}
		$users = array_merge($users, $parentUsers);
	}
	$source = dirname($source);
}
if (!empty($users)) {
	OC_JSON::encodedPrint($users);
}
