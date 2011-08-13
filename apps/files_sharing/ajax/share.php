<?php
$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');
require_once('../lib_share.php');

$sources = explode(";", $_POST['sources']);
$uid_shared_with = $_POST['uid_shared_with'];
$permissions = $_POST['permissions'];
foreach ($sources as $source) {
	if ($source && OC_FILESYSTEM::file_exists($source) && OC_FILESYSTEM::is_readable($source)) {
		$source = "/".OC_User::getUser()."/files".$source;
		try {
			if (OC_Group::groupExists($uid_shared_with)) {
				new OC_Share($source, $uid_shared_with, $permissions, true);
			} else {
				new OC_Share($source, $uid_shared_with, $permissions);
			}
		} catch (Exception $exception) {
			echo "false";
		}
	}
}

?>