<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
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

