<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC_Util::checkAdminUser();
OCP\JSON::callCheck();

$action=isset($_POST['action'])?$_POST['action']:$_GET['action'];

if(isset($_POST['app']) || isset($_GET['app'])) {
	$app=OC_App::cleanAppId(isset($_POST['app'])? (string)$_POST['app']: (string)$_GET['app']);
}

// An admin should not be able to add remote and public services
// on its own. This should only be possible programmatically.
// This change is due the fact that an admin may not be expected 
// to execute arbitrary code in every environment.
if($app === 'core' && isset($_POST['key']) &&(substr((string)$_POST['key'],0,7) === 'remote_' || substr((string)$_POST['key'],0,7) === 'public_')) {
	OC_JSON::error(array('data' => array('message' => 'Unexpected error!')));
	return;
}

$result=false;
$appConfig = \OC::$server->getAppConfig();
switch($action) {
	case 'getValue':
		$result=$appConfig->getValue($app, (string)$_GET['key'], (string)$_GET['defaultValue']);
		break;
	case 'setValue':
		$result=$appConfig->setValue($app, (string)$_POST['key'], (string)$_POST['value']);
		break;
	case 'getApps':
		$result=$appConfig->getApps();
		break;
	case 'getKeys':
		$result=$appConfig->getKeys($app);
		break;
	case 'hasKey':
		$result=$appConfig->hasKey($app, (string)$_GET['key']);
		break;
	case 'deleteKey':
		$result=$appConfig->deleteKey($app, (string)$_POST['key']);
		break;
	case 'deleteApp':
		$result=$appConfig->deleteApp($app);
		break;
}
OC_JSON::success(array('data'=>$result));

