<?php

OC_JSON::checkLoggedIn();
OC_JSON::callCheck();
$user = OC_User::getUser();

if(isset($_POST['path'])) {
	$path = $_POST['path'];
	if ($path === "false") { // delete avatar
		\OC_Avatar::setLocalAvatar($user, false);
	} else { // select an image from own files
		$view = new \OC\Files\View('/'.$user.'/files');
		$img = $view->file_get_contents($path);

		$type = substr($path, -3);
		try {
			\OC_Avatar::setLocalAvatar($user, $img);
			OC_JSON::success();
		} catch (Exception $e) {
			OC_JSON::error();
		}
	}
} elseif (isset($_POST['image'])) { // upload a new image
	\OC_Avatar::setLocalAvatar($user, $_POST['image']);
	OC_JSON::success();
} else {
	OC_JSON::error();
}
