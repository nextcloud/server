<?php
/**
 * Copyright (c) 2014, Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCP\JSON::checkAdminUser();
OCP\JSON::callCheck();

\OC::$server->getSession()->close();

/**
 * Whether /dev/urandom is available to the PHP controller
 *
 * @return bool
 */
function isUrandomAvailable() {
	if(@file_exists('/dev/urandom')) {
		$file = fopen('/dev/urandom', 'rb');
		if($file) {
			fclose($file);
			return true;
		}
	}
	return false;
}

// no warning when has_internet_connection is false in the config
$hasInternet = true;
if (OC_Util::isInternetConnectionEnabled()) {
	$hasInternet = OC_Util::isInternetConnectionWorking();
}

OCP\JSON::success(
	array (
		'serverHasInternetConnection' => $hasInternet,
		'dataDirectoryProtected' => OC_Util::isHtaccessWorking(),
		'hasCurlInstalled' => function_exists('curl_init'),
		'isUrandomAvailable' => isUrandomAvailable(),
		'securityDocs' => \OC::$server->getURLGenerator()->linkToDocs('admin-security'),
	)
);
