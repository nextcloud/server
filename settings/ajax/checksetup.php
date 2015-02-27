<?php
/**
 * Copyright (c) 2014, Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCP\JSON::checkAdminUser();
OCP\JSON::callCheck();

\OC::$server->getSession()->close();

// no warning when has_internet_connection is false in the config
$hasInternet = true;
if (OC_Util::isInternetConnectionEnabled()) {
	$hasInternet = OC_Util::isInternetConnectionWorking();
}

OCP\JSON::success(
	array (
		'serverHasInternetConnection' => $hasInternet,
		'dataDirectoryProtected' => OC_Util::isHtaccessWorking()
	)
);
