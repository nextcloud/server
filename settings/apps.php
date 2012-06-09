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

$blacklist=array('files','files_imageviewer','files_textviewer');//we dont want to show configuration for these

foreach($registeredApps as $app){
	if(array_search($app,$blacklist)===false){
		$info=OC_App::getAppInfo($app);
		$active=(OC_Appconfig::getValue($app,'enabled','no')=='yes')?true:false;
		$info['active']=$active;
		if(isset($info['shipped']) and ($info['shipped']=='true')) {
			$info['internal']=true;
			$info['internallabel']='Internal App';
		}else{
			$info['internal']=false;
			$info['internallabel']='3rd Party App';
		}
		$info['preview']='trans.png';
		$apps[]=$info;
	}
}

function app_sort($a, $b){
	if ($a['active'] != $b['active']){
		return $b['active'] - $a['active'];
	}
	return strcmp($a['name'], $b['name']);
}
usort($apps, 'app_sort');

// apps from external repo via OCS
 $catagoryNames=OC_OCSClient::getCategories();
 if(is_array($catagoryNames)){
 	$categories=array_keys($catagoryNames);
	$page=0;
 	$externalApps=OC_OCSClient::getApplications($categories,$page);
 	foreach($externalApps as $app){
		// show only external apps that are not exist yet
		$local=false;
 		foreach($apps as $a){
			if($a['name']==$app['name']) $local=true;			
		}

		if(!$local) {
 			if($app['preview']=='') $pre='trans.png'; else $pre=$app['preview'];
	 		$apps[]=array(
 				'name'=>$app['name'],
 				'id'=>$app['id'],
 				'active'=>false,
 				'description'=>$app['description'],
 				'author'=>$app['personid'],
 				'license'=>$app['license'],
 				'preview'=>$pre,
 				'internal'=>false,
 				'internallabel'=>'3rd Party App',
 			);
		}
 	}
 }



$tmpl = new OC_Template( "settings", "apps", "user" );
$tmpl->assign('apps',$apps);

$tmpl->printPage();

?>
