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
	header( "Location: ".OC_HELPER::linkTo( "index.php" ));
	exit();
}

// We have some javascript foo!
OC_UTIL::addScript( "admin", "users" );
OC_APP::setActiveNavigationEntry( "core_users" );

$users = array();
$groups = array();

foreach( OC_USER::getUsers() as $i ){
	// Do some more work here soon
	$ingroups = array();
	foreach( OC_GROUP::getUserGroups( $i ) as $userGroup ){
		$ingroups[] = $userGroup;
	}
	$users[] = array( "name" => $i, "groups" => join( ", ", $ingroups ));
}

foreach( OC_GROUP::getGroups() as $i ){
	// Do some more work here soon
	$groups[] = array( "name" => $i["gid"] );
}

$tmpl = new OC_TEMPLATE( "admin", "users", "admin" );
$tmpl->assign( "users", $users );
$tmpl->assign( "groups", $groups );
$tmpl->printPage();

?>

