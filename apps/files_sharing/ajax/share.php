<?php
//$RUNTIME_NOAPPS = true;

 
OCP\JSON::checkAppEnabled('files_sharing');
require_once(OC::$APPSROOT . '/apps/files_sharing/lib_share.php');

$userDirectory = "/".OCP\USER::getUser()."/files";
$sources = explode(";", $_POST['sources']);
$uid_shared_with = $_POST['uid_shared_with'];
$permissions = $_POST['permissions'];
foreach ($sources as $source) {
	// Make sure file exists and can be shared
	if ($source && OC_FILESYSTEM::file_exists($source) && OC_FILESYSTEM::is_readable($source)) {
		$source = $userDirectory.$source;
	// If the file doesn't exist, it may be shared with the current user
	} else if (!$source = OC_Share::getSource($userDirectory.$source)) {
		OCP\Util::writeLog('files_sharing',"Shared file doesn't exists :".$source,OCP\Util::ERROR);
		echo "false";
	}
	try {
		$shared = new OC_Share($source, $uid_shared_with, $permissions);
		if ($uid_shared_with == OC_Share::PUBLICLINK) {
			echo $shared->getToken();
		}
	} catch (Exception $exception) {
		OCP\Util::writeLog('files_sharing',"Unexpected Error : ".$exception->getMessage(),OCP\Util::ERROR);
		echo "false";
	}
}

?>
