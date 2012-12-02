<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * filesync can be called with a PUT method.
 * PUT takes a stream starting with a 2 byte blocksize,
 *     followed by binary md5 of the blocks. Everything in big-endian.
 *     The return is a json encoded with:
 *       - 'transferid'
 *       - 'needed' chunks
 *       - 'last' checked chunk
 * The URL is made of 3 parts, the service url (remote.php/filesync/), the sync
 * type and the path in ownCloud.
 * At the moment the only supported sync type is 'oc_chunked'.
 * The final URL will look like http://.../remote.php/filesync/oc_chunked/path/to/file
 */

// load needed apps
$RUNTIME_APPTYPES=array('filesystem', 'authentication', 'logging');
OC_App::loadApps($RUNTIME_APPTYPES);
if(!OC_User::isLoggedIn()) {
	if(!isset($_SERVER['PHP_AUTH_USER'])) {
		header('WWW-Authenticate: Basic realm="ownCloud Server"');
		header('HTTP/1.0 401 Unauthorized');
		echo 'Valid credentials must be supplied';
		exit();
	} else {
		if(!OC_User::login($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"])) {
			exit();
		}
	}
}

list($type, $file) = explode('/', substr($path_info, 1+strlen($service)+1), 2);

if ($type != 'oc_chunked') {
	OC_Response::setStatus(OC_Response::STATUS_NOT_FOUND);
	die;
}

if (!\OC\Files\Filesystem::is_file($file)) {
	OC_Response::setStatus(OC_Response::STATUS_NOT_FOUND);
	die;
}

switch($_SERVER['REQUEST_METHOD']) {
	case 'PUT':
		$input = fopen("php://input", "r");
		$org_file = \OC\Files\Filesystem::fopen($file, 'rb');
		$info = array(
			'name' => basename($file),
		);
		$sync = new OC_FileChunking($info);
		$result = $sync->signature_split($org_file, $input);
		echo json_encode($result);
		break;
	default:
		OC_Response::setStatus(OC_Response::STATUS_NOT_FOUND);
}
