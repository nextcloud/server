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
	header( "Location: ".OC_Helper::linkTo( "index.php" ));
	exit();
}

// We have some javascript foo!
OC_Util::addScript( "admin", "users" );
OC_App::setActiveNavigationEntry( "core_users" );

$users = array();
$groups = array();

foreach( OC_User::getUsers() as $i ){
	// Do some more work here soon
	$ingroups = array();
	foreach( OC_Group::getUserGroups( $i ) as $userGroup ){
		$ingroups[] = $userGroup;
	}
	$users[] = array( "name" => $i, "groups" => join( ", ", $ingroups ));
}

foreach( OC_Group::getGroups() as $i ){
	// Do some more work here soon
	$groups[] = array( "name" => $i );
}

$tmpl = new OC_Template( "admin", "users", "user" );
$tmpl->assign( "users", $users );
$tmpl->assign( "groups", $groups );
$tmpl->printPage();

?>

