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
require_once('../../lib/base.php');
require_once( 'lib_public.php' );


// Check if we are a user
if( !OC_USER::isLoggedIn()){
	header( "Location: ".OC_HELPER::linkTo( "index.php" ));
	exit();
}

OC_APP::setActiveNavigationEntry( "files_publiclink_administration" );

OC_UTIL::addStyle( 'files_publiclink', 'admin' );
OC_UTIL::addScript( 'files_publiclink', 'admin' );

if(isset($_SERVER['HTTPS'])) {
	$baseUrl= "https://". $_SERVER['SERVER_NAME'] . OC_HELPER::linkTo('files_publiclink','get.php');
}else{
	$baseUrl= "http://". $_SERVER['SERVER_NAME'] . OC_HELPER::linkTo('files_publiclink','get.php');
}


// return template
$tmpl = new OC_TEMPLATE( "files_publiclink", "admin", "admin" );
$tmpl->assign( 'links', OC_PublicLink::getLinks());
$tmpl->assign('baseUrl',$baseUrl);
$tmpl->printPage();

?>
