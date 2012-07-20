<?php
require_once(OC::$APPSROOT . '/apps/files_sharing/lib_share.php');

OCP\JSON::checkAppEnabled('files_sharing');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$userDirectory = '/'.OCP\USER::getUser().'/files';
$sources = explode(';', $_POST['sources']);
$uid_shared_with = $_POST['uid_shared_with'];
$permissions = $_POST['permissions'];
foreach ($sources as $source) {
	$file = OC_FileCache::get($source);
	$path = ltrim($source, '/'); 
	$source = $userDirectory.$source;
	// Check if the file exists or if the file is being reshared
	if ($source && $file['encrypted'] == false && (OC_FILESYSTEM::file_exists($path) && OC_FILESYSTEM::is_readable($path) || OC_Share::getSource($source))) {
		try {
			$shared = new OC_Share($source, $uid_shared_with, $permissions);
			// If this is a private link, return the token
			if ($uid_shared_with == OC_Share::PUBLICLINK) {
				OCP\JSON::success(array('data' => $shared->getToken()));
			} else {
				OCP\JSON::success();
			}
		} catch (Exception $exception) {
			OCP\Util::writeLog('files_sharing', 'Unexpected Error : '.$exception->getMessage(), OCP\Util::ERROR);
			OCP\JSON::error(array('data' => array('message' => $exception->getMessage())));
		}
	} else {
		if ($file['encrypted'] == true) {
			OCP\JSON::error(array('data' => array('message' => 'Encrypted files cannot be shared')));
		} else {
			OCP\Util::writeLog('files_sharing', 'File does not exist or is not readable :'.$source, OCP\Util::ERROR);
			OCP\JSON::error(array('data' => array('message' => 'File does not exist or is not readable')));
		}
	}
}

?>
