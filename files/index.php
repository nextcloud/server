<?php

/**
* ownCloud - ajax frontend
*
* @author Robin Appelman
* @copyright 2010 Robin Appelman icewind1991@gmail.com
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


// Init owncloud
require_once('../lib/base.php');

// Check if we are a user
if( !OC_User::isLoggedIn()){
	header( "Location: ".OC_Helper::linkTo( '', 'index.php' ));
	exit();
}

// Load the files we need
OC_Util::addStyle( "files", "files" );
OC_Util::addScript( "files", "files" );
OC_Util::addScript( 'files', 'filelist' );
OC_Util::addScript( 'files', 'fileactions' );
if(!isset($_SESSION['timezone'])){
	OC_Util::addScript( 'files', 'timezone' );
}
OC_App::setActiveNavigationEntry( "files_index" );
// Load the files
$dir = isset( $_GET['dir'] ) ? $_GET['dir'] : '';

$files = array();
foreach( OC_Files::getdirectorycontent( $dir ) as $i ){
	$i["date"] = OC_Util::formatDate($i["mtime"] );
	if($i['type']=='file'){
		$i['extention']=substr($i['name'],strrpos($i['name'],'.'));
		$i['basename']=substr($i['name'],0,strrpos($i['name'],'.'));
	}
	if($i['directory']=='/'){
		$i['directory']='';
	}
	$files[] = $i;
}

// Make breadcrumb
$breadcrumb = array();
$pathtohere = "";
foreach( explode( "/", $dir ) as $i ){
	if( $i != "" ){
		$pathtohere .= "/$i";
		$breadcrumb[] = array( "dir" => $pathtohere, "name" => $i );
	}
}

// make breadcrumb und filelist markup
$list = new OC_Template( "files", "part.list", "" );
$list->assign( "files", $files );
$list->assign( "baseURL", OC_Helper::linkTo("files", "index.php?dir="));
$list->assign( "downloadURL", OC_Helper::linkTo("files", "download.php?file="));
$breadcrumbNav = new OC_Template( "files", "part.breadcrumb", "" );
$breadcrumbNav->assign( "breadcrumb", $breadcrumb );
$breadcrumbNav->assign( "baseURL", OC_Helper::linkTo("files", "index.php?dir="));

$maxUploadFilesize = OC_Helper::computerFileSize(ini_get('upload_max_filesize'));

$tmpl = new OC_Template( "files", "index", "user" );
$tmpl->assign( "fileList", $list->fetchPage() );
$tmpl->assign( "breadcrumb", $breadcrumbNav->fetchPage() );
$tmpl->assign( 'dir', $dir);
$tmpl->assign( "files", $files );
$tmpl->assign( 'uploadMaxFilesize', $maxUploadFilesize);
$tmpl->assign( 'uploadMaxHumanFilesize', OC_Helper::humanFileSize($maxUploadFilesize));
$tmpl->printPage();

?>
