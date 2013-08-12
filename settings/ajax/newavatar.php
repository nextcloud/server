<?php

OC_JSON::checkLoggedIn();
OC_JSON::callCheck();
$user = OC_User::getUser();

// Delete avatar
if (isset($_POST['path']) && $_POST['path'] === "false") {
	$avatar = false;
}
// Select an image from own files
elseif (isset($_POST['path'])) {
	//SECURITY TODO FIXME possible directory traversal here
	$path = $_POST['path'];
	$avatar = OC::$SERVERROOT.'/data/'.$user.'/files'.$path;
}
// Upload a new image
elseif (!empty($_FILES)) {
	$files = $_FILES['files'];
	if ($files['error'][0] === 0) {
		$avatar = file_get_contents($files['tmp_name'][0]);
		unlink($files['tmp_name'][0]);
	}
} else {
	OC_JSON::error();
}

try {
	\OC_Avatar::setLocalAvatar($user, $avatar);
	OC_JSON::success();
} catch (\Exception $e) {
	OC_JSON::error(array("data" => array ("message" => $e->getMessage()) ));
}
