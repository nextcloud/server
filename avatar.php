<?php

/**
 * * @todo work on hashing userstrings, so one can't guess usernames
 */

require_once 'lib/base.php';

if (!\OC_User::isLoggedIn()) {
	header("HTTP/1.0 403 Forbidden");
	\OC_Template::printErrorPage("Permission denied");
}

$mode = \OC_Avatar::getMode();
if ($mode === "none") {
	exit();
}

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
	$image = \OC_Avatar::getDefaultAvatar($size);
	$image->show();
}
