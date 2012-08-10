<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

require_once ("../../lib/base.php");
OC_Util::checkAdminUser();
OCP\JSON::callCheck();

$action=isset($_POST['action'])?$_POST['action']:$_GET['action'];
$result=false;
switch($action){
	case 'getValue':
		$result=OC_Appconfig::getValue($_GET['app'],$_GET['key'],$_GET['defaultValue']);
		break;
	case 'setValue':
		$result=OC_Appconfig::setValue($_POST['app'],$_POST['key'],$_POST['value']);
		break;
	case 'getApps':
		$result=OC_Appconfig::getApps();
		break;
	case 'getKeys':
		$result=OC_Appconfig::getKeys($_GET['app']);
		break;
	case 'hasKey':
		$result=OC_Appconfig::hasKey($_GET['app'],$_GET['key']);
		break;
	case 'deleteKey':
		$result=OC_Appconfig::deleteKey($_POST['app'],$_POST['key']);
		break;
	case 'deleteApp':
		$result=OC_Appconfig::deleteApp($_POST['app']);
		break;
}
OC_JSON::success(array('data'=>$result));
