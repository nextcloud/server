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
if( !OC_USER::isLoggedIn()){
	header( "Location: ".OC_HELPER::linkTo( '', 'index.php' ));
	exit();
}

// Load the files we need
OC_UTIL::addStyle( 'search', 'search' );

$query=(isset($_POST['query']))?$_POST['query']:'';
if($query){
	$results=OC_SEARCH::search($query);
}else{
	header("Location: ".$WEBROOT.'/'.OC_APPCONFIG::getValue("core", "defaultpage", "files/index.php"));
	exit();
}

$resultTypes=array();
foreach($results as $result){
	if(!isset($resultTypes[$result->type])){
		$resultTypes[$result->type]=array();
	}
	$resultTypes[$result->type][]=$result;
}

$tmpl = new OC_TEMPLATE( 'search', 'index', 'user' );
$tmpl->assign('resultTypes',$resultTypes);
$tmpl->printPage();

?>
