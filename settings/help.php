<?php
/**
 * 2012 Frank Karlitschek frank@owncloud.org
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC_Util::checkLoggedIn();
OC_App::loadApps();

// Load the files we need
OC_Util::addStyle( "settings", "settings" );
OC_App::setActiveNavigationEntry( "help" );


if(isset($_GET['mode']) and $_GET['mode']=='admin') {
	$url=OC_Helper::linkToAbsolute( 'core', 'docu/admin' );
}else{
	$url=OC_Helper::linkToAbsolute( 'core', 'docu/user' );
}

$url1=OC_Helper::linkToRoute( "settings_help" ).'?mode=user';
$url2=OC_Helper::linkToRoute( "settings_help" ).'?mode=admin';

$tmpl = new OC_Template( "settings", "help", "user" );
$tmpl->assign( "admin", OC_Group::inGroup(OC_User::getUser(), 'admin') );
$tmpl->assign( "url", $url );
$tmpl->assign( "url1", $url1 );
$tmpl->assign( "url2", $url2 );
$tmpl->printPage();
