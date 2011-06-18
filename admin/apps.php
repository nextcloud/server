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
require( 'template.php' );
if( !OC_USER::isLoggedIn() || !OC_GROUP::inGroup( $_SESSION['user_id'], 'admin' )){
	header( "Location: ".OC_HELPER::linkTo( "", "index.php" ));
	exit();
}

// Load the files we need
OC_UTIL::addStyle( "admin", "apps" );


if(isset($_GET['id']))  $id=$_GET['id']; else $id=0;
if(isset($_GET['cat'])) $cat=$_GET['cat']; else $cat=0;

$categories=OC_OCSCLIENT::getCategories();
if($categories==NULL){
	OC_APP::setActiveNavigationEntry( "core_apps" );

	$tmpl = new OC_TEMPLATE( "admin", "app_noconn", "admin" );
	$tmpl->printPage();
	unset($tmpl);
	exit();
}
/*

All
Installed Apps



foreach($categories as $key=>$value) {
print_r($value);
}


*/

// OC_APP::setActiveNavigationEntry( "core_apps_installed" );


if($id==0) {
	OC_APP::setActiveNavigationEntry( "core_apps" );

	if($cat==0){
		$numcats=array();
		foreach($categories as $key=>$value) $numcats[]=$key;
		$apps=OC_OCSCLIENT::getApplications($numcats);
	}else{
		$apps=OC_OCSCLIENT::getApplications($cat);
	}

	// return template
	$tmpl = new OC_TEMPLATE( "admin", "apps", "admin" );

	$tmpl->assign( "categories", $categories );
	$tmpl->assign( "apps", $apps );
	$tmpl->printPage();
	unset($tmpl);

}else{
	OC_APP::setActiveNavigationEntry( "core_apps" );

	$app=OC_OCSCLIENT::getApplication($id);

	$tmpl = new OC_TEMPLATE( "admin", "app", "admin" );
	$tmpl->assign( "categories", $categories );
	$tmpl->assign( "app", $app );
	$tmpl->printPage();
	unset($tmpl);

}


?>

