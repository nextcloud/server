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
	$app=OC_App::cleanAppId(isset($_POST['app'])?$_POST['app']:$_GET['app']);
}

// An admin should not be able to add remote and public services
// on its own. This should only be possible programmatically.
// This change is due the fact that an admin may not be expected 
// to execute arbitrary code in every environment.
if($app === 'core' && isset($_POST['key']) &&(substr($_POST['key'],0,7) === 'remote_' || substr($_POST['key'],0,7) === 'public_')) {
	OC_JSON::error(array('data' => array('message' => 'Unexpected error!')));
	return;
}

$result=false;
switch($action) {
	case 'getValue':
		$result=OC_Appconfig::getValue($app, $_GET['key'], $_GET['defaultValue']);
		break;
	case 'setValue':
		$result=OC_Appconfig::setValue($app, $_POST['key'], $_POST['value']);
		break;
	case 'getApps':
		$result=OC_Appconfig::getApps();
		break;
	case 'getKeys':
		$result=OC_Appconfig::getKeys($app);
		break;
	case 'hasKey':
		$result=OC_Appconfig::hasKey($app, $_GET['key']);
		break;
	case 'deleteKey':
		$result=OC_Appconfig::deleteKey($app, $_POST['key']);
		break;
	case 'deleteApp':
		$result=OC_Appconfig::deleteApp($app);
		break;
}
OC_JSON::success(array('data'=>$result));

