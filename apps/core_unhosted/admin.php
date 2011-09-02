<?php

/**
* ownCloud app for using your OwnCloud on the unhosted web
*
* @author Michiel de Jong
* Some parts are from the files_publiclink app, and thus:
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
require_once( 'lib_unhosted.php' );
require( 'template.php' );

// Check if we are a user
if( !OC_USER::isLoggedIn()){
//var_export($_COOKIE);
//var_export($_SESSION);
//die('get a cookie!');
	header( "Location: ".OC_HELPER::linkTo( "index.php" ));
	exit();
}

OC_APP::setActiveNavigationEntry( "unhosted_web_administration" );

OC_UTIL::addStyle( 'unhosted_web', 'admin' );
OC_UTIL::addScript( 'unhosted_web', 'admin' );

// return template
$tmpl = new OC_TEMPLATE( "unhosted_web", "admin", "admin" );
$tmpl->assign( 'tokens', OC_UnhostedWeb::getAllTokens());
$tmpl->printPage();

?>
