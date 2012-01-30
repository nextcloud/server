<?php
//$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');
OC_JSON::checkAppEnabled('files_sharing');
require_once('../lib_share.php');

$userDirectory = "/".OC_User::getUser()."/files";
$sources = explode(";", $_POST['sources']);
$uid_shared_with = $_POST['uid_shared_with'];
$permissions = $_POST['permissions'];
foreach ($sources as $source) {
	// Make sure file exists and can be shared
	if ($source && OC_FILESYSTEM::file_exists($source) && OC_FILESYSTEM::is_readable($source)) {
		$source = $userDirectory.$source;
	// If the file doesn't exist, it may be shared with the current user
	} else if (!$source = OC_Share::getSource($userDirectory.$source)) {
		OC_Log::write('files_sharing',"Shared file doesn't exists :".$source,OC_Log::ERROR);
		echo "false";
	}
	try {
		$shared = new OC_Share($source, $uid_shared_with, $permissions);
		if ($uid_shared_with == OC_Share::PUBLICLINK) {
			echo $shared->getToken();
		}
	} catch (Exception $exception) {
		OC_Log::write('files_sharing',"Unexpected Error : ".$exception->getMessage(),OC_Log::ERROR);
		echo "false";
	}
}

?>
