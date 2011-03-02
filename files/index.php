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
oc_require( 'template.php' );

// Check if we are a user
if( !OC_USER::isLoggedIn()){
	header( "Location: ".OC_HELPER::linkTo( "index.php" ));
	exit();
}

// Load the files we need
OC_UTIL::addStyle( "files", "files" );
OC_UTIL::addScript( "files", "files" );

// Load the files
$dir = isset( $_GET['dir'] ) ? $_GET['dir'] : '';

$files = array();
foreach( OC_FILES::getdirectorycontent( $dir ) as $i ){
	$i["date"] = date( $CONFIG_DATEFORMAT, $i["mtime"] );
	$files[] = $i;
}

// Make breadcrumb
$breadcrumb = array();
$pathtohere = "/";
foreach( explode( "/", $dir ) as $i ){
	if( $i != "" ){
		$pathtohere .= "$i/";
		$breadcrumb[] = array( "dir" => $pathtohere, "name" => $i );
	}
}

// return template
$tmpl = new OC_TEMPLATE( "files", "index", "user" );
$tmpl->assign( "files", $files );
$tmpl->assign( "breadcrumb", $breadcrumb );
$tmpl->printPage();

?>
