<?php

require_once 'lib/base.php';

if (!\OC_User::isLoggedIn()) {
	header("HTTP/1.0 403 Forbidden");
	\OC_Template::printErrorPage("Permission denied");
}

$mode = \OC_Avatar::getMode();
if ($mode === "none") {
	exit();
}

if ($_SERVER['REQUEST_METHOD'] === "GET") {
	if (isset($_GET['user'])) {
		//SECURITY TODO does this fully eliminate directory traversals?
		$user = stripslashes($_GET['user']);
	} else {
		$user = false;
	}

	if (isset($_GET['size']) && ((int)$_GET['size'] > 0)) {
		$size = (int)$_GET['size'];
		if ($size > 2048) {
			$size = 2048;
		}
	} else {
		$size = 64;
	}

	$image = \OC_Avatar::get($user, $size);

	if ($image instanceof \OC_Image) {
		$image->show();
	} elseif (is_string($image)) { // Gravatar alike services
		header("Location: ".$image);
	} else {
		$image = \OC_Avatar::getDefaultAvatar($user, $size);
		$image->show();
	}
} elseif ($_SERVER['REQUEST_METHOD'] === "POST") {
	$user = OC_User::getUser();

	// Select an image from own files
	if (isset($_POST['path'])) {
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
} elseif ($_SERVER['REQUEST_METHOD'] === "DELETE") {
	$user = OC_User::getUser();

	try {
		\OC_Avatar::setLocalAvatar($user, false);
		OC_JSON::success();
	} catch (\Exception $e) {
		OC_JSON::error(array("data" => array ("message" => $e->getMessage()) ));
	}
}
