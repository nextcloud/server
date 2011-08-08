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
if( !OC_User::isLoggedIn() || !OC_Group::inGroup( OC_User::getUser(), 'admin' )){
	header( "Location: ".OC_Helper::linkTo( "", "index.php" ));
	exit();
}

// Load the files we need
OC_Util::addStyle( "admin", "apps" );
OC_Util::addScript( "admin", "apps" );


if(isset($_GET['id']))  $id=$_GET['id']; else $id=0;
if(isset($_GET['cat'])) $cat=$_GET['cat']; else $cat=0;
if(isset($_GET['installed'])) $installed=true; else $installed=false;

if($installed){
	global $SERVERROOT;
	OC_Installer::installShippedApps(false);
	$apps = OC_Appconfig::getApps();
	$records = array();

	OC_App::setActiveNavigationEntry( "core_apps" );
	foreach($apps as $app){
		$info=OC_App::getAppInfo("$SERVERROOT/apps/$app/appinfo/info.xml");
		$record = array( 'id' => $app,
				 'name' => $info['name'],
				 'version' => $info['version'],
				 'author' => $info['author'],
				 'enabled' => OC_App::isEnabled( $app ));
		$records[]=$record;
	}

	$tmpl = new OC_Template( "admin", "appsinst", "user" );
	$tmpl->assign( "apps", $records );
	$tmpl->printPage();
	unset($tmpl);
	exit();
}else{
	$categories=OC_OCSClient::getCategories();
	if($categories==NULL){
		OC_App::setActiveNavigationEntry( "core_apps" );

		$tmpl = new OC_Template( "admin", "app_noconn", "user" );
		$tmpl->printPage();
		unset($tmpl);
		exit();
	}


	if($id==0) {
		OC_App::setActiveNavigationEntry( "core_apps_get" );

		if($cat==0){
			$numcats=array();
			foreach($categories as $key=>$value) $numcats[]=$key;
			$apps=OC_OCSClient::getApplications($numcats);
		}else{
			$apps=OC_OCSClient::getApplications($cat);
		}

		// return template
		$tmpl = new OC_Template( "admin", "apps", "user" );

		$tmpl->assign( "categories", $categories );
		$tmpl->assign( "apps", $apps );
		$tmpl->printPage();
		unset($tmpl);

	}else{
		OC_App::setActiveNavigationEntry( "core_apps" );

		$app=OC_OCSClient::getApplication($id);

		$tmpl = new OC_Template( "admin", "app", "user" );
		$tmpl->assign( "categories", $categories );
		$tmpl->assign( "app", $app );
		$tmpl->printPage();
		unset($tmpl);

	}
}

?>
