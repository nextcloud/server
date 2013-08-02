<?php

OC_JSON::checkLoggedIn();
OC_JSON::callCheck();
$user = OC_User::getUser();

if(isset($_POST['path'])) {
	if ($_POST['path'] === "false") { // delete avatar
		\OC_Avatar::setLocalAvatar($user, false);
	} else { // select an image from own files
		try {
			$path = OC::$SERVERROOT.'/data/'.$user.'/files'.$_POST['path'];
			\OC_Avatar::setLocalAvatar($user, $path);
			OC_JSON::success();
		} catch (Exception $e) {
			OC_JSON::error(array("msg" => $e->getMessage()));
		}
	}
} elseif (!empty($_FILES)) { // upload a new image
	$files = $_FILES['files'];
	if ($files['error'][0] === 0) {
		$data = file_get_contents($files['tmp_name'][0]);
		\OC_Avatar::setLocalAvatar($user, $data);
		unlink($files['tmp_name'][0]);
		OC_JSON::success();
	} else {
		OC_JSON::error();
	}
} else {
	OC_JSON::error();
}
