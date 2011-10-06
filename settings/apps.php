<?php

/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2010 Frank Karlitschek karlitschek@kde.org
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

require_once('../lib/base.php');
OC_Util::checkAdminUser();

// Load the files we need
OC_Util::addStyle( "settings", "settings" );
OC_Util::addScript( "settings", "apps" );
OC_App::setActiveNavigationEntry( "core_apps" );

$registeredApps=OC_App::getAllApps();
$apps=array();

$blacklist=array('files_imageviewer','files_textviewer');//we dont want to show configuration for these

foreach($registeredApps as $app){
	if(array_search($app,$blacklist)===false){
		$info=OC_App::getAppInfo($app);
		$active=(OC_Appconfig::getValue($app,'enabled','no')=='yes')?true:false;
		$info['active']=$active;
		$apps[]=$info;
	}
}

// dissabled for now
// $catagoryNames=OC_OCSClient::getCategories();
// if(is_array($catagoryNames)){
// 	$categories=array_keys($catagoryNames);
// 	$externalApps=OC_OCSClient::getApplications($categories);
// 	foreach($externalApps as $app){
// 		$apps[]=array(
// 			'name'=>$app['name'],
// 			'id'=>$app['id'],
// 			'active'=>false,
// 			'description'=>$app['description'],
// 			'author'=>$app['personid'],
// 			'license'=>$app['license'],
// 		);
// 	}
// }



$tmpl = new OC_Template( "settings", "apps", "user" );
$tmpl->assign('apps',$apps);

$tmpl->printPage();

?>
