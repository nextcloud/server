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

// We have some javascript foo!
OC_Util::addScript( 'settings', 'users' );
OC_Util::addStyle( 'settings', 'settings' );
OC_Util::addScript( '3rdparty', 'chosen/chosen.jquery.min' );
OC_Util::addStyle( '3rdparty', 'chosen' );
OC_App::setActiveNavigationEntry( 'core_users' );

$users = array();
$groups = array();

foreach( OC_User::getUsers() as $i ){
	$users[] = array( "name" => $i, "groups" => join( ", ", OC_Group::getUserGroups( $i ) ),'quota'=>OC_Helper::humanFileSize(OC_Preferences::getValue($i,'files','quota',0)));
}

foreach( OC_Group::getGroups() as $i ){
	// Do some more work here soon
	$groups[] = array( "name" => $i );
}

$tmpl = new OC_Template( "settings", "users", "user" );
$tmpl->assign( "users", $users );
$tmpl->assign( "groups", $groups );
$tmpl->printPage();

?>

